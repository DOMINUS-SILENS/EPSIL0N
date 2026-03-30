import { useState, useEffect } from 'react';
import { db, Conflict } from '../../infra/dexie/db';
import { optimizedSyncManager } from '../../core/offline/optimizedSyncManager';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { AlertTriangle, Check, X, GitMerge } from 'lucide-react';
import { toast } from 'sonner';

interface ConflictWithDetails extends Conflict {
  details?: {
    clientValue: unknown;
    serverValue: unknown;
    field: string;
  };
}

export function ConflictResolver() {
  const [conflicts, setConflicts] = useState<ConflictWithDetails[]>([]);
  const [selectedConflict, setSelectedConflict] = useState<ConflictWithDetails | null>(null);
  const [isOpen, setIsOpen] = useState(false);

  useEffect(() => {
    loadConflicts();

    // Subscribe to changes
    const interval = setInterval(loadConflicts, 5000);
    return () => clearInterval(interval);
  }, []);

  const loadConflicts = async () => {
    const pending = await db.conflicts.where('status').equals('pending').toArray();
    setConflicts(pending);

    // Auto-open if new conflicts
    if (pending.length > 0 && !isOpen) {
      setIsOpen(true);
    }
  };

  const handleResolve = async (strategy: 'client_wins' | 'server_wins' | 'merge') => {
    if (!selectedConflict) return;

    try {
      await optimizedSyncManager.resolveConflict(selectedConflict.id, strategy);

      toast.success(`Conflict resolved: ${strategy.replace('_', ' ')}`);
      await loadConflicts();
      setSelectedConflict(null);
    } catch (error) {
      toast.error('Failed to resolve conflict');
    }
  };

  const handleDismiss = async (conflictId: string) => {
    await db.conflicts.update(conflictId, { status: 'discarded' });
    await loadConflicts();
  };

  if (conflicts.length === 0) return null;

  return (
    <>
      {/* Floating indicator */}
      {conflicts.length > 0 && !isOpen && (
        <button
          onClick={() => setIsOpen(true)}
          className="fixed bottom-4 right-4 bg-red-500 text-white px-4 py-2 rounded-full shadow-lg hover:bg-red-600 flex items-center gap-2 z-50"
        >
          <AlertTriangle className="w-4 h-4" />
          {conflicts.length} conflict{conflicts.length > 1 ? 's' : ''}
        </button>
      )}

      {/* Conflict dialog */}
      <Dialog open={isOpen} onOpenChange={setIsOpen}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <AlertTriangle className="w-5 h-5 text-red-500" />
              Sync Conflicts ({conflicts.length})
            </DialogTitle>
            <DialogDescription>
              Some of your changes conflicted with server data. Please resolve each conflict.
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4 mt-4">
            {!selectedConflict ? (
              // List view
              conflicts.map((conflict) => (
                <div
                  key={conflict.id}
                  className="border rounded-lg p-4 hover:bg-gray-50 cursor-pointer"
                  onClick={() => setSelectedConflict(conflict)}
                >
                  <div className="flex justify-between items-start">
                    <div>
                      <h4 className="font-medium capitalize">{conflict.type.replace(/_/g, ' ')}</h4>
                      <p className="text-sm text-gray-500">
                        {conflict.serverReason}
                      </p>
                      <p className="text-xs text-gray-400 mt-1">
                        Detected {new Date(conflict.detectedAt).toLocaleString()}
                      </p>
                    </div>
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={(e) => {
                        e.stopPropagation();
                        handleDismiss(conflict.id);
                      }}
                    >
                      <X className="w-4 h-4" />
                    </Button>
                  </div>
                </div>
              ))
            ) : (
              // Detail view
              <div className="space-y-4">
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => setSelectedConflict(null)}
                  className="mb-2"
                >
                  ← Back to list
                </Button>

                <div className="border rounded-lg p-4 bg-yellow-50">
                  <h4 className="font-medium capitalize mb-2">
                    {selectedConflict.type.replace(/_/g, ' ')}
                  </h4>
                  <p className="text-sm text-gray-600">{selectedConflict.serverReason}</p>
                </div>

                {/* Resolution options */}
                <div className="grid grid-cols-3 gap-3">
                  <Button
                    variant="outline"
                    onClick={() => handleResolve('client_wins')}
                    className="flex flex-col items-center p-4 h-auto"
                  >
                    <Check className="w-6 h-6 mb-2 text-green-500" />
                    <span className="text-sm font-medium">Keep my changes</span>
                    <span className="text-xs text-gray-500">Overwrite server</span>
                  </Button>

                  <Button
                    variant="outline"
                    onClick={() => handleResolve('server_wins')}
                    className="flex flex-col items-center p-4 h-auto"
                  >
                    <X className="w-6 h-6 mb-2 text-red-500" />
                    <span className="text-sm font-medium">Use server version</span>
                    <span className="text-xs text-gray-500">Discard my changes</span>
                  </Button>

                  <Button
                    variant="outline"
                    onClick={() => handleResolve('merge')}
                    className="flex flex-col items-center p-4 h-auto"
                  >
                    <GitMerge className="w-6 h-6 mb-2 text-blue-500" />
                    <span className="text-sm font-medium">Merge changes</span>
                    <span className="text-xs text-gray-500">Combine both</span>
                  </Button>
                </div>
              </div>
            )}
          </div>
        </DialogContent>
      </Dialog>
    </>
  );
}
