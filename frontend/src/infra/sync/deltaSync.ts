import { db } from '../dexie/db';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

export interface DeltaSyncOptions {
  entities: string[]; // orders, customers, articles, etc.
  lastSyncAt?: string;
  limit?: number;
  cursor?: string;
}

export interface DeltaSyncResult {
  data: Record<string, any[]>;
  meta: {
    sync_timestamp: string;
    has_more: boolean;
    next_cursors?: Record<string, string>;
  };
}

export interface SyncCheckpoint {
  entity: string;
  lastSyncAt: string;
  cursor?: string;
  hasMore: boolean;
}

/**
 * Perform delta sync - only fetch changed data since last sync
 */
export async function deltaSync(options: DeltaSyncOptions): Promise<DeltaSyncResult> {
  const { entities, lastSyncAt, limit = 500, cursor } = options;

  const params = new URLSearchParams();
  params.append('entities', entities.join(','));
  params.append('limit', String(limit));

  if (lastSyncAt) {
    params.append('last_sync_at', lastSyncAt);
  }
  if (cursor) {
    params.append('cursor', cursor);
  }

  const res = await fetch(`${API_URL}/sync/delta?${params.toString()}`, {
    method: 'GET',
    credentials: 'include',
    headers: {
      'Accept': 'application/json',
    },
  });

  if (!res.ok) {
    const error = await res.json().catch(() => ({ message: res.statusText }));
    throw new Error(error.message || 'Delta sync failed');
  }

  return res.json();
}

/**
 * Sync multiple entities with automatic pagination
 */
export async function syncEntities(
  entities: string[],
  onProgress?: (entity: string, progress: number) => void
): Promise<void> {
  for (const entity of entities) {
    await syncEntity(entity, onProgress);
  }
}

/**
 * Sync a single entity with pagination support
 */
export async function syncEntity(
  entity: string,
  onProgress?: (entity: string, progress: number) => void
): Promise<void> {
  const checkpoint = await getCheckpoint(entity);
  let cursor = checkpoint?.cursor;
  let hasMore = true;
  let totalProcessed = 0;

  while (hasMore) {
    const result = await deltaSync({
      entities: [entity],
      lastSyncAt: checkpoint?.lastSyncAt,
      cursor,
    });

    // Store data in local DB
    await storeDeltaData(entity, result.data[entity] || []);

    totalProcessed += result.data[entity]?.length || 0;

    // Update progress
    if (onProgress) {
      onProgress(entity, hasMore ? totalProcessed : 100);
    }

    // Update checkpoint
    hasMore = result.meta.has_more;
    cursor = result.meta.next_cursors?.[entity];

    await saveCheckpoint({
      entity,
      lastSyncAt: result.meta.sync_timestamp,
      cursor,
      hasMore,
    });

    // Break if no more data
    if (!hasMore) break;
  }

  if (onProgress) {
    onProgress(entity, 100);
  }
}

/**
 * Store delta data in local IndexedDB
 */
async function storeDeltaData(entity: string, items: any[]): Promise<void> {
  if (items.length === 0) return;

  await db.transaction('rw', db[entity as keyof typeof db] as any, async () => {
    for (const item of items) {
      // Handle tombstones (deleted items)
      if (item.tombstone) {
        await (db[entity as keyof typeof db] as any).delete(item.id);
      } else {
        // Upsert item
        await (db[entity as keyof typeof db] as any).put({
          ...item,
          syncedAt: new Date(),
        });
      }
    }
  });
}

/**
 * Get sync checkpoint for an entity
 */
export async function getCheckpoint(entity: string): Promise<SyncCheckpoint | null> {
  const key = `sync_checkpoint:${entity}`;
  const stored = localStorage.getItem(key);

  if (stored) {
    return JSON.parse(stored);
  }

  return null;
}

/**
 * Save sync checkpoint
 */
export async function saveCheckpoint(checkpoint: SyncCheckpoint): Promise<void> {
  const key = `sync_checkpoint:${checkpoint.entity}`;
  localStorage.setItem(key, JSON.stringify(checkpoint));
}

/**
 * Get sync plan - which entities need syncing
 */
export async function getSyncPlan(lastFullSync: string): Promise<{
  plan: Array<{
    entity: string;
    action: 'delta' | 'full' | 'skip';
    reason?: string;
    estimatedCount?: number;
  }>;
}> {
  const res = await fetch(`${API_URL}/sync/plan?last_full_sync=${lastFullSync}`, {
    method: 'GET',
    credentials: 'include',
    headers: {
      'Accept': 'application/json',
    },
  });

  if (!res.ok) {
    throw new Error('Failed to get sync plan');
  }

  return res.json();
}

/**
 * Get entity summary (for initial sync planning)
 */
export async function getEntitySummary(entity: string): Promise<{
  total_count: number;
  last_modified: string;
  estimated_sync_time_sec: number;
}> {
  const res = await fetch(`${API_URL}/sync/summary/${entity}`, {
    method: 'GET',
    credentials: 'include',
    headers: {
      'Accept': 'application/json',
    },
  });

  if (!res.ok) {
    throw new Error('Failed to get entity summary');
  }

  return res.json();
}

/**
 * Resume sync from last checkpoint
 */
export async function resumeSync(
  lastEventId: number,
  onEvent?: (event: any) => void
): Promise<number> {
  let currentId = lastEventId;
  let hasMore = true;
  let totalEvents = 0;

  while (hasMore) {
    const res = await fetch(`${API_URL}/sync/resume?last_event_id=${currentId}&limit=500`, {
      method: 'GET',
      credentials: 'include',
      headers: {
        'Accept': 'application/json',
      },
    });

    if (!res.ok) {
      throw new Error('Resume sync failed');
    }

    const result = await res.json();

    // Process events
    for (const event of result.events) {
      if (onEvent) {
        onEvent(event);
      }
      await processResumedEvent(event);
    }

    totalEvents += result.events.length;
    hasMore = result.meta.has_more;
    currentId = result.meta.last_event_id;

    if (!hasMore) break;
  }

  return totalEvents;
}

/**
 * Process a resumed event
 */
async function processResumedEvent(event: any): Promise<void> {
  // Store event in events table
  await db.events.put({
    id: event.id,
    aggregateId: event.aggregate_id,
    aggregateType: event.aggregate_type,
    sequence: 0, // Server sequence
    type: event.type,
    payload: event.payload,
    occurredAt: new Date(event.timestamp),
    syncStatus: 'synced',
  });

  // Update local projections based on event type
  // This would be customized based on your event handlers
}

/**
 * Clear all sync checkpoints (for full resync)
 */
export function clearAllCheckpoints(): void {
  const keys = Object.keys(localStorage).filter(k => k.startsWith('sync_checkpoint:'));
  keys.forEach(k => localStorage.removeItem(k));
}

/**
 * Get sync statistics
 */
export async function getSyncStats(): Promise<{
  totalLocalEvents: number;
  pendingOutbox: number;
  failedOutbox: number;
  lastSyncAt: string | null;
}> {
  const [totalLocalEvents, pendingOutbox, failedOutbox] = await Promise.all([
    db.events.count(),
    db.outbox.where('status').anyOf(['pending', 'sending']).count(),
    db.outbox.where('status').equals('failed').count(),
  ]);

  return {
    totalLocalEvents,
    pendingOutbox,
    failedOutbox,
    lastSyncAt: localStorage.getItem('sfa_last_sync_at'),
  };
}
