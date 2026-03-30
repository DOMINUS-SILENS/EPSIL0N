import { useQueryClient } from '@tanstack/react-query';
import { useEffect, useRef, useCallback } from 'react';
import { toast } from 'sonner';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
const POLL_INTERVAL = 30000; // 30 seconds base polling
const LONG_POLL_TIMEOUT = 25000; // 25 seconds

interface ServerEvent {
  id: number;
  type: string;
  aggregate_type: string;
  aggregate_id: string;
  payload: unknown;
  timestamp: string;
}

interface PollResponse {
  events: ServerEvent[];
  meta: {
    last_event_id: number;
    has_more: boolean;
  };
}

/**
 * Optimized SSE using long-polling (more efficient than blocking SSE)
 * Falls back to regular polling if long-poll fails
 */
export function useOptimizedServerSentEvents(userId: string | number | null) {
  const queryClient = useQueryClient();
  const lastEventIdRef = useRef<number>(0);
  const isActiveRef = useRef(true);
  const abortControllerRef = useRef<AbortController | null>(null);

  const poll = useCallback(async () => {
    if (!userId || !isActiveRef.current) return;

    try {
      // Use long-polling endpoint
      const params = new URLSearchParams({
        last_event_id: String(lastEventIdRef.current),
        timeout: String(LONG_POLL_TIMEOUT / 1000), // seconds
      });

      abortControllerRef.current = new AbortController();
      const timeoutId = setTimeout(() => abortControllerRef.current?.abort(), LONG_POLL_TIMEOUT + 5000);

      const res = await fetch(`${API_URL}/events/long-poll?${params.toString()}`, {
        method: 'GET',
        credentials: 'include',
        signal: abortControllerRef.current.signal,
      });

      clearTimeout(timeoutId);

      if (!res.ok) {
        throw new Error(`Poll failed: ${res.status}`);
      }

      const data: PollResponse = await res.json();

      // Process events
      if (data.events.length > 0) {
        for (const event of data.events) {
          handleServerEvent(event, queryClient);
          lastEventIdRef.current = event.id;
        }
      }

      // If no events, wait before next poll
      if (!data.meta.has_more && data.events.length === 0) {
        await new Promise(resolve => setTimeout(resolve, POLL_INTERVAL));
      }

    } catch (error) {
      // Ignore abort errors
      if (error instanceof Error && error.name === 'AbortError') {
        return;
      }

      console.warn('Long-poll failed, falling back to regular polling:', error);

      // Fall back to regular polling
      await new Promise(resolve => setTimeout(resolve, POLL_INTERVAL));
    }
  }, [userId, queryClient]);

  useEffect(() => {
    if (!userId) return;

    isActiveRef.current = true;

    // Poll loop
    const pollLoop = async () => {
      while (isActiveRef.current) {
        await poll();
      }
    };

    pollLoop();

    return () => {
      isActiveRef.current = false;
      abortControllerRef.current?.abort();
    };
  }, [poll, userId]);
}

/**
 * Simple polling hook (alternative to SSE)
 */
export function useEventPolling(userId: string | number | null, interval = POLL_INTERVAL) {
  const queryClient = useQueryClient();
  const lastEventIdRef = useRef<number>(0);

  useEffect(() => {
    if (!userId) return;

    const poll = async () => {
      try {
        const params = new URLSearchParams({
          last_event_id: String(lastEventIdRef.current),
          limit: '50',
        });

        const res = await fetch(`${API_URL}/events/poll?${params.toString()}`, {
          method: 'GET',
          credentials: 'include',
        });

        if (!res.ok) return;

        const data: PollResponse = await res.json();

        if (data.events.length > 0) {
          for (const event of data.events) {
            handleServerEvent(event, queryClient);
            lastEventIdRef.current = event.id;
          }
        }
      } catch (error) {
        console.error('Poll error:', error);
      }
    };

    // Initial poll
    poll();

    // Set up interval
    const intervalId = setInterval(poll, interval);

    return () => clearInterval(intervalId);
  }, [userId, interval, queryClient]);
}

/**
 * Handle server events and update cache
 */
function handleServerEvent(
  event: ServerEvent,
  queryClient: ReturnType<typeof useQueryClient>
): void {
  switch (event.type) {
    case 'aggregate_updated':
      handleAggregateUpdate(event, queryClient);
      break;

    case 'command_completed':
      handleCommandCompleted(event);
      break;

    case 'sync_required':
      handleSyncRequired(event, queryClient);
      break;

    case 'stock_critical':
      handleStockCritical(event);
      break;

    default:
      // Unknown event type
      break;
  }
}

/**
 * Handle aggregate update events
 */
function handleAggregateUpdate(
  event: ServerEvent,
  queryClient: ReturnType<typeof useQueryClient>
): void {
  const { aggregate_type, aggregate_id } = event;

  const queryKeyMap: Record<string, string[]> = {
    'Order': ['erp', 'orders'],
    'Customer': ['crm', 'customers'],
    'Article': ['erp', 'products'],
    'Stock': ['erp', 'stock'],
  };

  const baseKey = queryKeyMap[aggregate_type];
  if (baseKey) {
    queryClient.invalidateQueries({ queryKey: baseKey });
    queryClient.invalidateQueries({ queryKey: [...baseKey, aggregate_id] });
  }
}

/**
 * Handle command completed events
 */
function handleCommandCompleted(event: ServerEvent): void {
  const payload = event.payload as { command: string; success: boolean; message?: string };

  if (payload.success) {
    toast.success(`${payload.command} completed`, { duration: 3000 });
  } else {
    toast.error(payload.message || `${payload.command} failed`);
  }
}

/**
 * Handle sync required events
 */
function handleSyncRequired(
  event: ServerEvent,
  queryClient: ReturnType<typeof useQueryClient>
): void {
  const payload = event.payload as { tables: string[]; reason?: string };

  payload.tables?.forEach((table) => {
    queryClient.invalidateQueries({ queryKey: [table] });
  });

  if (payload.reason) {
    toast.info(`Data updated: ${payload.reason}`, { duration: 5000 });
  }
}

/**
 * Handle critical stock events
 */
function handleStockCritical(event: ServerEvent): void {
  const payload = event.payload as { product: string; warehouse: string; level: number };

  toast.warning(
    `Critical stock level for ${payload.product} in ${payload.warehouse}: ${payload.level}`,
    { duration: 10000 }
  );
}

/**
 * Legacy SSE hook - kept for backward compatibility
 * Uses EventSource but with new optimized endpoint
 */
export function useLegacySSE(userId: string | number | null) {
  const queryClient = useQueryClient();

  useEffect(() => {
    if (!userId) return;

    // Use the new stream endpoint (now non-blocking)
    const url = `${API_URL}/events/stream?user_id=${userId}`;
    const eventSource = new EventSource(url, { withCredentials: true });

    eventSource.onmessage = (event) => {
      try {
        const data: ServerEvent = JSON.parse(event.data);
        handleServerEvent(data, queryClient);
      } catch {
        // Ignore parsing errors
      }
    };

    eventSource.onerror = () => {
      // Close and let reconnect logic handle it
      eventSource.close();
    };

    return () => {
      eventSource.close();
    };
  }, [userId, queryClient]);
}
