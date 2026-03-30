import { Outbox } from '../dexie/db';

export async function sendBatchToServer(events: Outbox[]) {
  // In a real app, deviceId and userId would come from context/auth
  const deviceId = localStorage.getItem('sfa_device_id') || crypto.randomUUID();
  const userId = localStorage.getItem('sfa_user_id') || 'unknown';
  
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
      occurredAt: e.payload.occurredAt || new Date().toISOString(),
      payload: e.payload,
    })),
  };

  const res = await fetch('/api/sync/events', {
    method: 'POST',
    headers: { 
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      // 'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify(payload),
  });

  if (!res.ok) {
    const error = await res.json().catch(() => ({ message: res.statusText }));
    throw new Error(error.message || 'Sync failed');
  }

  return await res.json();
}
