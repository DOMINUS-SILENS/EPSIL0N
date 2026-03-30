import { offlineDb } from './db';
import { api } from '../api/client';
import { toast } from 'sonner';

export class SyncManager {
  private isSyncing = false;

  constructor() {
    this.setupListeners();
  }

  private setupListeners() {
    // Attempt sync when browser comes online
    window.addEventListener('online', () => {
      this.sync();
    });
  }

  public async sync() {
    if (this.isSyncing) return;
    if (!navigator.onLine) return;

    this.isSyncing = true;
    
    try {
      // Get chronological pending commands to ensure deterministic replay
      const pendingCommands = (await offlineDb.pendingCommands
        .orderBy('id')
        .toArray()
      ).filter((c: any) => c.status === 'pending');

      if (pendingCommands.length === 0) {
        this.isSyncing = false;
        return;
      }

      toast.info(`Syncing ${pendingCommands.length} offline operations...`);

      for (const cmd of pendingCommands) {
        try {
          await api.post(cmd.url, cmd.payload, {
            headers: {
              'Idempotency-Key': cmd.idempotency_key
            }
          });
          
          await offlineDb.pendingCommands.delete(cmd.id!);
        } catch (error: any) {
          // If the error is a 4xx validation error, it's a permanent failure
          if (error.response && error.response.status >= 400 && error.response.status < 500) {
            await offlineDb.pendingCommands.update(cmd.id!, { 
              status: 'failed', 
              error: JSON.stringify(error.response.data) 
            });
            toast.error(`Command failed permanently: ${error.response.data.message || 'Validation error'}`);
          } 
          // Otherwise, it might be a temporary network issue or 5xx, so we leave it pending
          else {
            console.error('Temporal sync failure for command', cmd, error);
            break; // Stop syncing to maintain chronologic order
          }
        }
      }

      // Check if any remain
      const remaining = (await offlineDb.pendingCommands.toArray()).filter((c: any) => c.status === 'pending').length;
      if (remaining === 0 && pendingCommands.length > 0) {
        toast.success('Offline sync completed successfully.');
      }
    } finally {
      this.isSyncing = false;
      // Invalidate the generic global query cache so projections update matching the replayed commands
      const { queryClient } = window as any; // Assuming global attachment for ease, or trigger a custom event
      if (queryClient) {
        queryClient.invalidateQueries();
      }
    }
  }
}

export const syncManager = new SyncManager();
