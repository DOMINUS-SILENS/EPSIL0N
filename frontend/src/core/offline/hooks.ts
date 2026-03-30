import { useMutation, useQuery, useQueryClient, UseQueryOptions } from '@tanstack/react-query';
import { queueCommand, syncCommands, getPendingCommands, retryFailedCommand, getCachedProjection, cacheProjection } from './db';
import { api } from '@/core/api/client';
import { toast } from 'sonner';

export interface CommandPayload {
  command: string;
  payload: unknown;
  idempotency_key: string;
}

export interface CommandResponse {
  success: boolean;
  events?: unknown[];
  error?: string;
  queuedId?: number | null;
}
export function useCommand(): any {
  const queryClient = useQueryClient();

  return useMutation<
    CommandResponse,
    Error,
    {
      module: string;
      resource: string;
      command: string;
      payload: unknown;
      immediate?: boolean;
    }
  >({
    mutationFn: async ({
      module,
      resource,
      command,
      payload,
      immediate = false,
    }: {
      module: string;
      resource: string;
      command: string;
      payload: unknown;
      immediate?: boolean;
    }): Promise<CommandResponse> => {
      if (!navigator.onLine) {
        await queueCommand(module, resource, command, payload);
        toast.info('Command queued for sync when online');
        return { success: true, events: [], queuedId: null };
      }

      if (immediate) {
        const response = await api.post<CommandResponse>(
          `/api/${module}/${resource}/command`,
          { command, payload }
        );
        return response.data;
      }

      const queuedId = await queueCommand(module, resource, command, payload);
      await syncCommands();
      return { success: true, events: [], queuedId: queuedId ?? null };
    },
    onSuccess: function (
      _: CommandResponse,
      variables: {
        module: string;
        resource: string;
        command: string;
        payload: unknown;
        immediate?: boolean;
      }
    ): void {
        queryClient.invalidateQueries({ queryKey: [variables.module, variables.resource] });
    },
    onError: function (error: Error): void {
      toast.error(
        `Command failed: ${error instanceof Error ? error.message : 'Unknown error'}`
      );
    },
  });
}


export function usePendingCommands() {
  return useQuery({
    queryKey: ['offline', 'pending-commands'],
    queryFn: getPendingCommands,
    refetchInterval: 5000,
  });
}

export function useSyncCommands() {
  return useMutation({
    mutationFn: syncCommands,
    onSuccess: () => {
      toast.success('Commands synced successfully');
    },
    onError: (error: Error) => {
      toast.error(`Sync failed: ${error instanceof Error ? error.message : 'Unknown error'}`);
    },
  });
}

export function useRetryCommand() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: retryFailedCommand,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['offline', 'pending-commands'] });
      toast.success('Command queued for retry');
    },
  });
}

export function useOfflineQuery<TData>(
  key: string[],
  fetcher: () => Promise<TData>,
  options?: Omit<UseQueryOptions<TData>, 'queryKey' | 'queryFn'>
) {
  const queryFn = async (): Promise<TData> => {
    const cacheKey = key.join(':');
    
    if (!navigator.onLine) {
      const cached = await getCachedProjection<TData>(cacheKey);
      if (cached) {
        return cached;
      }
      throw new Error('No cached data available offline');
    }

    const data = await fetcher();
    await cacheProjection(cacheKey, data);
    return data;
  };

  return useQuery({
    queryKey: key,
    queryFn,
    ...options,
  });
}
