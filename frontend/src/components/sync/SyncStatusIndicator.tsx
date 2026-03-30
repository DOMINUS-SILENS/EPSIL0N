import { useEffect, useState } from 'react';
import { useSyncStatus } from '../../core/offline/optimizedSyncManager';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { Cloud, CloudOff, RefreshCw, AlertCircle } from 'lucide-react';
import { toast } from 'sonner';

export function SyncStatusIndicator() {
  const { checkStatus, sync, quickSync } = useSyncStatus();
  const [status, setStatus] = useState<{
    isOnline: boolean;
    pendingChanges: number;
    failedChanges: number;
    lastSyncAt: string | null;
    isSyncing: boolean;
  } | null>(null);

  useEffect(() => {
    const updateStatus = async () => {
      const newStatus = await checkStatus();
      setStatus(newStatus);
    };

    updateStatus();
    const interval = setInterval(updateStatus, 10000); // Update every 10 seconds

    return () => clearInterval(interval);
  }, [checkStatus]);

  const handleQuickSync = async () => {
    const result = await quickSync();
    if (result.processed > 0) {
      toast.success(`${result.processed} changes synced`);
    }
    // Refresh status
    const newStatus = await checkStatus();
    setStatus(newStatus);
  };

  const handleFullSync = async () => {
    await sync({ syncMode: 'delta' });
    // Refresh status
    const newStatus = await checkStatus();
    setStatus(newStatus);
  };

  if (!status) return null;

  const getIcon = () => {
    if (!status.isOnline) return <CloudOff className="w-4 h-4" />;
    if (status.pendingChanges > 0) return <AlertCircle className="w-4 h-4 text-yellow-500" />;
    return <Cloud className="w-4 h-4 text-green-500" />;
  };

  const getStatusText = () => {
    if (!status.isOnline) return 'Offline';
    if (status.isSyncing) return 'Syncing...';
    if (status.pendingChanges > 0) return `${status.pendingChanges} pending`;
    if (status.lastSyncAt) {
      const lastSync = new Date(status.lastSyncAt);
      const minutesAgo = Math.floor((Date.now() - lastSync.getTime()) / 60000);
      if (minutesAgo < 1) return 'Just now';
      if (minutesAgo < 60) return `${minutesAgo}m ago`;
      return `${Math.floor(minutesAgo / 60)}h ago`;
    }
    return 'Never synced';
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" size="sm" className="flex items-center gap-2">
          {getIcon()}
          <span className="text-sm">{getStatusText()}</span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-64">
        <div className="p-2">
          <div className="text-sm font-medium mb-1">Sync Status</div>
          <div className="text-xs text-gray-500">
            {status.isOnline ? 'Connected' : 'Offline mode'}
          </div>
          {status.pendingChanges > 0 && (
            <div className="text-xs text-yellow-600 mt-1">
              {status.pendingChanges} changes waiting to sync
            </div>
          )}
          {status.failedChanges > 0 && (
            <div className="text-xs text-red-600 mt-1">
              {status.failedChanges} failed changes need attention
            </div>
          )}
        </div>

        <DropdownMenuItem onClick={handleQuickSync} disabled={!status.isOnline || status.isSyncing}>
          <RefreshCw className={`w-4 h-4 mr-2 ${status.isSyncing ? 'animate-spin' : ''}`} />
          Quick Sync
        </DropdownMenuItem>

        <DropdownMenuItem onClick={handleFullSync} disabled={!status.isOnline || status.isSyncing}>
          <Cloud className="w-4 h-4 mr-2" />
          Full Sync
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
