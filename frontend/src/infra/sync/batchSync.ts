import { db, Outbox } from '../dexie/db';
import { Table } from 'dexie';
import { compressPayload } from './compression';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
const BATCH_SIZE = 100; // Match backend limit

/**
 * Helper to bulk update records in Dexie (native bulkUpdate doesn't exist)
 */
async function bulkUpdate<T>(table: Table<T, string>, updates: Array<{ key: string; changes: Partial<T> }>): Promise<void> {
  await db.transaction('rw', table, async () => {
    for (const update of updates) {
      await table.update(update.key, update.changes);
    }
  });
}

export interface BatchSyncResult {
  acked: boolean;
  processed: number;
  correlation_id?: string;
  results: Array<{
    eventId: string;
    status: 'ACCEPTED' | 'ALREADY_ACKNOWLEDGED' | 'CAUSALITY_VIOLATION' | 'SCHEMA_INVALID' | 'BATCH_FAILED';
    reason?: string;
  }>;
}

export interface SyncStats {
  pending: number;
  failed: number;
  lastSyncAt: string | null;
}

/**
 * Send a batch of events to the server using optimized batch endpoint
 */
export async function sendBatchToServer(events: Outbox[]): Promise<BatchSyncResult> {
  const deviceId = localStorage.getItem('sfa_device_id') || crypto.randomUUID();
  const userId = localStorage.getItem('sfa_user_id') || 'unknown';
  const lastSyncAt = localStorage.getItem('sfa_last_sync_at');

  const payload = {
    deviceId,
    userId,
    batchId: crypto.randomUUID(),
    events: events.map(e => ({
      eventId: e.eventId,
      aggregateId: e.aggregateId,
      aggregateType: e.aggregateType,
      sequence: e.sequence,
      type: e.payload.type || e.payload.eventType || 'Unknown',
      version: e.payload.version || 1,
      occurredAt: e.payload.occurredAt || new Date().toISOString(),
      payload: e.payload,
      causationId: e.payload.causationId || null,
      correlationId: e.payload.correlationId || null,
    })),
    lastSyncAt,
  };

  // Compress payload if large
  const payloadStr = JSON.stringify(payload);
  const shouldCompress = payloadStr.length > 10 * 1024; // Compress if >10KB

  const headers: Record<string, string> = {
    'Accept': 'application/json',
  };

  let body: string | Blob = payloadStr;

  if (shouldCompress && typeof Blob !== 'undefined') {
    const compressed = await compressPayload(payloadStr);
    body = compressed;
    headers['Content-Encoding'] = 'gzip';
    headers['Content-Type'] = 'application/octet-stream';
  } else {
    headers['Content-Type'] = 'application/json';
  }

  const res = await fetch(`${API_URL}/sync/ingest`, {
    method: 'POST',
    headers,
    body,
    credentials: 'include',
  });

  if (!res.ok) {
    // Handle conflict response
    if (res.status === 409) {
      const conflictData = await res.json();
      throw new SyncConflictError('conflict_detected', conflictData.conflicts);
    }

    const error = await res.json().catch(() => ({ message: res.statusText }));
    throw new Error(error.message || `Sync failed: ${res.status}`);
  }

  const result: BatchSyncResult = await res.json();

  // Update last sync timestamp
  if (result.acked) {
    localStorage.setItem('sfa_last_sync_at', new Date().toISOString());
  }

  return result;
}

/**
 * Process outbox using batch operations instead of individual sends
 */
export async function processBatchOutbox(): Promise<{
  processed: number;
  failed: number;
  conflicts: number;
}> {
  const stats = { processed: 0, failed: 0, conflicts: 0 };

  // Get pending events
  const now = new Date();
  const pending = await db.outbox
    .where('status')
    .anyOf(['pending', 'failed'])
    .filter(row => !row.nextRetryAt || row.nextRetryAt <= now)
    .limit(BATCH_SIZE)
    .toArray();

  if (pending.length === 0) return stats;

  // Group by aggregate for causal ordering
  const groups = groupByAggregate(pending);

  // Process each aggregate group
  for (const [, events] of groups) {
    try {
      // Mark as sending
      await bulkUpdate(db.outbox,
        events.map(e => ({
          key: e.id,
          changes: { status: 'sending', updatedAt: new Date() },
        }))
      );

      // Send batch
      const response = await sendBatchToServer(events);

      // Process results
      const updates: Array<{ key: string; changes: Partial<Outbox> }> = [];
      const eventSyncUpdates: Array<{ key: string; changes: { syncStatus: 'synced' | 'failed' } }> = [];

      for (const result of response.results) {
        const event = events.find(e => e.eventId === result.eventId);
        if (!event) continue;

        switch (result.status) {
          case 'ACCEPTED':
          case 'ALREADY_ACKNOWLEDGED':
            updates.push({ key: event.id, changes: { status: 'acked', updatedAt: new Date() } });
            eventSyncUpdates.push({ key: event.eventId, changes: { syncStatus: 'synced' } });
            stats.processed++;
            break;

          case 'CAUSALITY_VIOLATION':
            // Will retry later
            updates.push({
              key: event.id,
              changes: {
                status: 'failed',
                retryCount: (event.retryCount || 0) + 1,
                nextRetryAt: new Date(Date.now() + 30000), // Retry in 30s
                lastError: 'Causality violation - will retry',
                updatedAt: new Date(),
              },
            });
            stats.failed++;
            break;

          default:
            updates.push({
              key: event.id,
              changes: {
                status: 'failed',
                retryCount: (event.retryCount || 0) + 1,
                lastError: result.reason || result.status,
                updatedAt: new Date(),
              },
            });
            stats.failed++;
        }
      }

      // Bulk update
      if (updates.length > 0) {
        await bulkUpdate(db.outbox, updates);
      }
      if (eventSyncUpdates.length > 0) {
        await bulkUpdate(db.events, eventSyncUpdates);
      }

    } catch (error) {
      if (error instanceof SyncConflictError) {
        // Handle conflicts
        await handleBatchConflicts(events, error.conflicts);
        stats.conflicts += events.length;
      } else {
        // Mark all as failed
        await bulkUpdate(db.outbox,
          events.map(e => ({
            key: e.id,
            changes: {
              status: 'failed',
              retryCount: (e.retryCount || 0) + 1,
              nextRetryAt: new Date(Date.now() + getRetryDelay(e.retryCount || 0)),
              lastError: error instanceof Error ? error.message : 'Unknown error',
              updatedAt: new Date(),
            },
          }))
        );
        stats.failed += events.length;
      }
    }
  }

  return stats;
}

/**
 * Group events by aggregate for causal ordering
 */
function groupByAggregate(events: Outbox[]): Map<string, Outbox[]> {
  const groups = new Map<string, Outbox[]>();

  for (const event of events) {
    const key = `${event.aggregateType}:${event.aggregateId}`;
    if (!groups.has(key)) groups.set(key, []);
    groups.get(key)!.push(event);
  }

  // Sort each group by sequence
  for (const [, group] of groups) {
    group.sort((a, b) => a.sequence - b.sequence);
  }

  return groups;
}

/**
 * Handle batch conflicts
 */
async function handleBatchConflicts(events: Outbox[], conflicts: any[]): Promise<void> {
  await db.transaction('rw', db.conflicts, db.outbox, db.events, async () => {
    for (const event of events) {
      const conflict = conflicts.find((c: any) => c.event_id === event.eventId);

      await db.outbox.update(event.id, { status: 'dead', lastError: 'Conflict detected' });
      await db.events.update(event.eventId, { syncStatus: 'failed' });

      if (conflict) {
        await db.conflicts.add({
          id: crypto.randomUUID(),
          aggregateId: event.aggregateId,
          type: conflict.conflict_type || 'unknown',
          serverReason: conflict.reason || 'conflict_detected',
          localEventId: event.eventId,
          status: 'pending',
          detectedAt: new Date(),
        });
      }
    }
  });
}

/**
 * Get retry delay with exponential backoff
 */
function getRetryDelay(attempt: number): number {
  const delays = [1000, 5000, 30000, 120000, 300000, 600000];
  return delays[Math.min(attempt, delays.length - 1)];
}

/**
 * Custom error class for sync conflicts
 */
export class SyncConflictError extends Error {
  constructor(
    message: string,
    public conflicts: any[]
  ) {
    super(message);
    this.name = 'SyncConflictError';
  }
}

/**
 * Get current sync status
 */
export async function getSyncStatus(): Promise<SyncStats> {
  const [pending, failed] = await Promise.all([
    db.outbox.where('status').anyOf(['pending', 'sending']).count(),
    db.outbox.where('status').equals('failed').count(),
  ]);

  return {
    pending,
    failed,
    lastSyncAt: localStorage.getItem('sfa_last_sync_at'),
  };
}
