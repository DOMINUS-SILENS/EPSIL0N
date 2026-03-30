import { db } from '../../infra/dexie/db';
import { processBatchOutbox, getSyncStatus, SyncConflictError } from '../../infra/sync/batchSync';
import { syncEntities, clearAllCheckpoints } from '../../infra/sync/deltaSync';
import { toast } from 'sonner';

export interface SyncOptions {
  syncMode?: 'delta' | 'full';
  entities?: string[];
  onProgress?: (stage: string, progress: number) => void;
  onConflict?: (conflicts: any[]) => void;
}

export interface SyncResult {
  success: boolean;
  pushed: number;
  pulled: number;
  conflicts: number;
  duration: number;
  errors?: string[];
}

/**
 * Optimized Sync Manager with batch operations and delta sync
 */
export class OptimizedSyncManager {
  private isSyncing = false;
  private abortController: AbortController | null = null;

  constructor() {
    this.setupListeners();
  }

  private setupListeners() {
    // Attempt sync when browser comes online
    window.addEventListener('online', () => {
      toast.success('Back online - syncing data...');
      this.sync({ syncMode: 'delta' });
    });

    // Pause sync when going offline
    window.addEventListener('offline', () => {
      toast.warning('You are offline - changes will be synced when connection returns');
      this.abortController?.abort();
    });
  }

  /**
   * Perform full sync (push outbox + pull deltas)
   */
  public async sync(options: SyncOptions = {}): Promise<SyncResult> {
    const startTime = Date.now();
    const result: SyncResult = {
      success: true,
      pushed: 0,
      pulled: 0,
      conflicts: 0,
      duration: 0,
      errors: [],
    };

    if (this.isSyncing) {
      return { ...result, success: false, errors: ['Sync already in progress'] };
    }

    if (!navigator.onLine) {
      return { ...result, success: false, errors: ['Device is offline'] };
    }

    this.isSyncing = true;
    this.abortController = new AbortController();

    try {
      // Stage 1: Push pending changes to server
      if (options.onProgress) {
        options.onProgress('push', 0);
      }

      const pushResult = await this.pushChanges();
      result.pushed = pushResult.processed;
      result.conflicts = pushResult.conflicts;

      if (options.onProgress) {
        options.onProgress('push', 100);
      }

      // Stage 2: Pull changes from server (delta sync)
      if (options.onProgress) {
        options.onProgress('pull', 0);
      }

      const pullResult = await this.pullChanges(options);
      result.pulled = pullResult.pulled;

      if (options.onProgress) {
        options.onProgress('pull', 100);
      }

      // Handle conflicts if any
      if (result.conflicts > 0 && options.onConflict) {
        const conflicts = await this.getPendingConflicts();
        options.onConflict(conflicts);
      }

      result.duration = Date.now() - startTime;

      // Show success message
      if (result.pushed > 0 || result.pulled > 0) {
        toast.success(
          `Sync complete: ${result.pushed} pushed, ${result.pulled} pulled`,
          { duration: 3000 }
        );
      }

      return result;

    } catch (error) {
      result.success = false;
      result.duration = Date.now() - startTime;

      if (error instanceof SyncConflictError) {
        result.conflicts = error.conflicts.length;
        if (options.onConflict) {
          options.onConflict(error.conflicts);
        }
      } else {
        const errorMsg = error instanceof Error ? error.message : 'Sync failed';
        result.errors!.push(errorMsg);
        toast.error(errorMsg);
      }

      return result;

    } finally {
      this.isSyncing = false;
      this.abortController = null;

      // Invalidate query cache
      this.invalidateQueryCache();
    }
  }

  /**
   * Push pending outbox changes to server
   */
  private async pushChanges(): Promise<{ processed: number; conflicts: number }> {
    const stats = await processBatchOutbox();

    return {
      processed: stats.processed,
      conflicts: stats.conflicts,
    };
  }

  /**
   * Pull changes from server using delta sync
   */
  private async pullChanges(options: SyncOptions): Promise<{ pulled: number }> {
    let totalPulled = 0;

    // Default entities to sync
    const entities = options.entities || [
      'customers',
      'products',
      'orders',
      'stock_view',
    ];

    // Get sync plan if doing full sync
    if (options.syncMode === 'full') {
      clearAllCheckpoints();
    }

    // Sync each entity
    for (const entity of entities) {
      if (this.abortController?.signal.aborted) {
        break;
      }

      try {
        await syncEntities([entity], (entityName, progress) => {
          if (options.onProgress) {
            // Calculate overall progress
            const entityIndex = entities.indexOf(entityName);
            const overallProgress = ((entityIndex + progress / 100) / entities.length) * 100;
            options.onProgress('pull', overallProgress);
          }
        });

        // Get count for this entity
        const count = await (db[entity as keyof typeof db] as any).count();
        totalPulled += count;

      } catch (error) {
        console.error(`Failed to sync ${entity}:`, error);
      }
    }

    return { pulled: totalPulled };
  }

  /**
   * Quick sync - only push pending changes, don't pull
   */
  public async quickSync(): Promise<{ processed: number; failed: number }> {
    if (!navigator.onLine || this.isSyncing) {
      return { processed: 0, failed: 0 };
    }

    this.isSyncing = true;

    try {
      const stats = await processBatchOutbox();

      if (stats.processed > 0) {
        toast.success(`${stats.processed} changes synced`);
      }

      return {
        processed: stats.processed,
        failed: stats.failed,
      };
    } catch (error) {
      console.error('Quick sync failed:', error);
      return { processed: 0, failed: 0 };
    } finally {
      this.isSyncing = false;
    }
  }

  /**
   * Get pending conflicts from local DB
   */
  private async getPendingConflicts(): Promise<any[]> {
    return await db.conflicts.where('status').equals('pending').toArray();
  }

  /**
   * Resolve a conflict
   */
  public async resolveConflict(conflictId: string, strategy: 'client_wins' | 'server_wins' | 'merge'): Promise<void> {
    const conflict = await db.conflicts.get(conflictId);
    if (!conflict) return;

    // Send resolution to server
    const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
    const res = await fetch(`${API_URL}/sync/resolve-conflicts`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({
        resolutions: [{
          eventId: conflict.localEventId,
          strategy,
        }],
      }),
    });

    if (res.ok) {
      await db.conflicts.update(conflictId, { status: 'resolved' });
    }
  }

  /**
   * Get current sync status
   */
  public async getStatus(): Promise<{
    isSyncing: boolean;
    isOnline: boolean;
    pendingChanges: number;
    failedChanges: number;
    lastSyncAt: string | null;
  }> {
    const [syncStats, isOnline] = await Promise.all([
      getSyncStatus(),
      navigator.onLine,
    ]);

    return {
      isSyncing: this.isSyncing,
      isOnline,
      pendingChanges: syncStats.pending,
      failedChanges: syncStats.failed,
      lastSyncAt: syncStats.lastSyncAt,
    };
  }

  /**
   * Cancel current sync
   */
  public cancel(): void {
    this.abortController?.abort();
    this.isSyncing = false;
  }

  /**
   * Invalidate query cache after sync
   */
  private invalidateQueryCache(): void {
    // Dispatch custom event for components to listen
    window.dispatchEvent(new CustomEvent('sync-completed'));

    // Access query client if available
    const { queryClient } = window as any;
    if (queryClient) {
      queryClient.invalidateQueries();
    }
  }

  /**
   * Retry a failed command
   */
  public async retryFailed(outboxId: string): Promise<void> {
    await db.outbox.update(outboxId, {
      status: 'pending',
      retryCount: 0,
      lastError: undefined,
      nextRetryAt: undefined,
    });

    // Trigger sync
    await this.quickSync();
  }

  /**
   * Clear all local data and checkpoints (for full resync)
   */
  public async clearLocalData(): Promise<void> {
    // Clear all tables
    await Promise.all([
      db.outbox.clear(),
      db.events.clear(),
      db.conflicts.clear(),
      db.customers.clear(),
      db.products.clear(),
      db.orders.clear(),
      db.order_lines.clear(),
      db.stock_view.clear(),
    ]);

    // Clear checkpoints
    clearAllCheckpoints();
    localStorage.removeItem('sfa_last_sync_at');
  }
}

// Export singleton instance
export const optimizedSyncManager = new OptimizedSyncManager();

// React hook for sync status
export function useSyncStatus() {
  const checkStatus = async () => {
    return await optimizedSyncManager.getStatus();
  };

  const sync = async (options?: SyncOptions) => {
    return await optimizedSyncManager.sync(options);
  };

  const quickSync = async () => {
    return await optimizedSyncManager.quickSync();
  };

  return {
    checkStatus,
    sync,
    quickSync,
    syncManager: optimizedSyncManager,
  };
}
