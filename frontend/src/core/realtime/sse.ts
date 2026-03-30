import { useQueryClient } from '@tanstack/react-query';
import { useEffect } from 'react';
import { toast } from 'sonner';

const SSE_URL = import.meta.env.VITE_SSE_URL || 'http://localhost:8000/api/events/stream';

interface ServerSentEvent {
  type: string;
  aggregate_type: string;
  aggregate_id: string;
  event_type: string;
  payload: unknown;
  timestamp: string;
}

export function useServerSentEvents(userId: string | number | null) {
  const queryClient = useQueryClient();

  useEffect(() => {
    if (!userId) return;

    const url = new URL(SSE_URL, window.location.origin);
    url.searchParams.append('user_id', String(userId));
    
    const eventSource = new EventSource(url.toString(), {
      withCredentials: true,
    });

    eventSource.onmessage = (event) => {
      try {
        const data: ServerSentEvent = JSON.parse(event.data);
        
        switch (data.type) {
          case 'aggregate_updated':
            handleAggregateUpdate(data, queryClient);
            break;
          case 'command_completed':
            handleCommandCompleted(data);
            break;
          case 'sync_required':
            handleSyncRequired(data, queryClient);
            break;
          default:
            break;
        }
      } catch {
        // Ignore parsing errors
      }
    };

    eventSource.onerror = () => {
      eventSource.close();
    };

    return () => {
      eventSource.close();
    };
  }, [userId, queryClient]);
}

function handleAggregateUpdate(
  event: ServerSentEvent,
  queryClient: ReturnType<typeof useQueryClient>
): void {
  const { aggregate_type, aggregate_id } = event;
  
  const queryKeys: Record<string, string[]> = {
    'delivery.tour': ['delivery', 'tours'],
    'delivery.stop': ['delivery', 'stops'],
    'fleet.vehicle': ['fleet', 'vehicles'],
    'crm.opportunity': ['crm', 'opportunities'],
    'crm.lead': ['crm', 'leads'],
    'erp.order': ['erp', 'orders'],
    'sfa.visit': ['sfa', 'visits'],
    'trademkt.execution': ['trademkt', 'executions'],
    'trademkt.planogram': ['trademkt', 'planograms'],
    'commercial.campaign': ['presales', 'campaigns'],
  };

  const baseKey = queryKeys[aggregate_type];
  if (baseKey) {
    queryClient.invalidateQueries({ queryKey: baseKey });
    queryClient.invalidateQueries({ queryKey: [...baseKey, aggregate_id] });
  }
}

function handleCommandCompleted(event: ServerSentEvent): void {
  const payload = event.payload as { command: string; success: boolean; message?: string };
  
  if (payload.success) {
    toast.success(`${payload.command} completed successfully`);
  } else {
    toast.error(payload.message || `${payload.command} failed`);
  }
}

function handleSyncRequired(
  event: ServerSentEvent,
  queryClient: ReturnType<typeof useQueryClient>
): void {
  const payload = event.payload as { tables: string[] };
  
  payload.tables?.forEach((table) => {
    queryClient.invalidateQueries({ queryKey: [table] });
  });
  
  toast.info('Data updated from server');
}

export function useOnlineStatus() {
  const queryClient = useQueryClient();

  useEffect(() => {
    const handleOnline = () => {
      toast.success('Back online - syncing data...');
      queryClient.invalidateQueries();
    };

    const handleOffline = () => {
      toast.warning('You are offline - changes will be synced when connection returns');
    };

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  }, [queryClient]);
}
