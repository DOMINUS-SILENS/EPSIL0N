import { Dexie, type Table } from 'dexie';
import { syncManager } from './syncManager';

export interface PendingCommand {
  id?: number;
  idempotency_key: string;
  url: string;
  payload: any;
  status: 'pending' | 'failed';
  error?: string;
  created_at: string;
}

export class OfflineDB extends Dexie {
  pendingCommands!: Table<PendingCommand, number>;

  constructor() {
    super('EpsilonOfflineSync');

    (this as any).version(1).stores({
      pendingCommands: '++id, idempotency_key, status, created_at'
    });
  }
}

export const offlineDb = new OfflineDB();

export async function queueCommand(module: string, resource: string, command: string, payload: any) {
  const url = `/api/${module}/${resource}/command`;
  const idempotency_key = crypto.randomUUID();
  
  return await offlineDb.pendingCommands.add({
    idempotency_key,
    url,
    payload: { command, payload },
    status: 'pending',
    created_at: new Date().toISOString()
  });
}

export async function syncCommands() {
  return await syncManager.sync();
}

export async function getPendingCommands() {
  return await offlineDb.pendingCommands.orderBy('id').toArray();
}

export async function retryFailedCommand(id: number) {
  return await offlineDb.pendingCommands.update(id, { status: 'pending', error: undefined });
}

export async function getCachedProjection<T>(key: string): Promise<T | null> {
  const cached = localStorage.getItem(`cache:${key}`);
  return cached ? JSON.parse(cached) : null;
}

export async function cacheProjection(key: string, data: any) {
  localStorage.setItem(`cache:${key}`, JSON.stringify(data));
}
