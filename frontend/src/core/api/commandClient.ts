import { api } from './client';
import { v4 as uuidv4 } from 'uuid';
import { offlineDb } from '../offline/db';

/**
 * Dispatcher for all state-mutating commands in the system.
 * Generates an idempotency_key for every request to guarantee EXACTLY-ONCE processing 
 * in the Event Sourced backend, and stores in Dexie if offline.
 */
export const dispatchCommand = async <T = unknown>(
  url: string,
  payload: Record<string, unknown>,
  options?: { throwOnOffline?: boolean }
): Promise<T | void> => {
  const idempotency_key = uuidv4();
  
  const enrichedPayload = {
    ...payload,
    idempotency_key,
  };

  try {
    // Attempt standard network dispatch first
    const response = await api.post<T>(url, enrichedPayload, {
      headers: {
        'Idempotency-Key': idempotency_key
      }
    });
    return response.data;
  } catch (error: unknown) {
    // If the error is network related (offline, timeout)
    if (error && typeof error === 'object' && ('code' in error || !('response' in error))) {
      const err = error as { response?: unknown; code?: string };
      if (!err.response || err.code === 'ERR_NETWORK') {
        if (options?.throwOnOffline) {
          throw error;
        }

        // Save to Dexie for Background Sync Replay
        await offlineDb.pendingCommands.add({
          idempotency_key,
          url,
          payload: enrichedPayload, // payload includes the generated key
          status: 'pending',
          created_at: new Date().toISOString(),
        });

        // Return void to signal that it was queued optimistically
        return undefined;
      }
    }

    // If it's a 4xx or 5xx from the server, let it throw normally
    throw error;
  }
};
