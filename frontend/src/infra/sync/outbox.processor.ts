import { db, Outbox } from '../dexie/db';
import { sendBatchToServer } from './sync.api';

const BATCH_SIZE = 50;
const RETRY_DELAYS = [1000, 5000, 30000, 120000, 300000]; // milliseconds

export async function processOutbox() {
  const now = new Date();

  // 1. Fetch pending events
  const pending = await db.outbox
    .where('status')
    .anyOf(['pending', 'failed'])
    .filter(row => !row.nextRetryAt || row.nextRetryAt <= now)
    .limit(BATCH_SIZE)
    .toArray();

  if (pending.length === 0) return;

  // 2. Group by aggregate for strict causal ordering
  const groups = new Map<string, Outbox[]>();
  for (const row of pending) {
    const key = `${row.aggregateType}:${row.aggregateId}`;
    if (!groups.has(key)) groups.set(key, []);
    groups.get(key)!.push(row);
  }

  // 3. Dispatch sequentially per aggregate
  for (const [_, group] of Array.from(groups.entries())) {
    const sorted = group.sort((a, b) => a.sequence - b.sequence);

    for (const item of sorted) {
      try {
        await db.outbox.update(item.id, { status: 'sending', updatedAt: new Date() });

        const response = await sendBatchToServer([item]);

        if (response.acked) {
          await db.outbox.update(item.id, { status: 'acked', updatedAt: new Date() });
          await db.events.update(item.eventId, { syncStatus: 'synced' });
        } else if (response.conflict) {
          await handleConflict(item, response);
        } else {
          throw new Error(response.error || 'Server rejected event without explicit conflict');
        }
      } catch (err: any) {
        const currentRetries = item.retryCount || 0;
        const nextDelay = RETRY_DELAYS[currentRetries] || 600000;
        
        await db.outbox.update(item.id, {
          status: 'failed',
          retryCount: currentRetries + 1,
          nextRetryAt: new Date(Date.now() + nextDelay),
          lastError: err.message,
          updatedAt: new Date()
        });
      }
    }
  }
}

async function handleConflict(outboxItem: Outbox, response: any) {
  await db.transaction('rw', db.conflicts, db.outbox, db.events, async () => {
    await db.outbox.update(outboxItem.id, { status: 'dead', lastError: response.reason });
    await db.events.update(outboxItem.eventId, { syncStatus: 'failed' });

    await db.conflicts.add({
      id: crypto.randomUUID(),
      aggregateId: outboxItem.aggregateId,
      type: response.type || 'unknown_conflict',
      serverReason: response.reason || 'conflict_detected',
      localEventId: outboxItem.eventId,
      status: 'pending',
      detectedAt: new Date(),
    });

    // In a full implementation, we would dispatch a Redux action or event to notify the UI
    // and potentially refetch the latest aggregate projection from the server.
  });
}
