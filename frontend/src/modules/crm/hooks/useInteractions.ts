import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { interactionsApi, Interaction, UpdateInteractionDto } from '../api/interactionsApi';
import { toast } from 'sonner';

export const queryKeys = {
  interactions: {
    all: ['interactions'] as const,
    list: (params?: any) => [...queryKeys.interactions.all, 'list', params] as const,
    detail: (id: number) => [...queryKeys.interactions.all, 'detail', id] as const,
    byCustomer: (customerId: number) => [...queryKeys.interactions.all, 'customer', customerId] as const,
    byLead: (leadId: number) => [...queryKeys.interactions.all, 'lead', leadId] as const,
    byOpportunity: (opportunityId: number) => [...queryKeys.interactions.all, 'opportunity', opportunityId] as const,
  },
};

export const useInteractions = (params?: {
  customer_id?: number;
  lead_id?: number;
  opportunity_id?: number;
  type?: string;
  page?: number;
  per_page?: number;
}) => {
  return useQuery({
    queryKey: queryKeys.interactions.list(params),
    queryFn: () => interactionsApi.list(params),
    select: (response) => response.data,
    refetchInterval: 30000, // Refresh every 30 seconds
  });
};

export const useInteraction = (id: number) => {
  return useQuery({
    queryKey: queryKeys.interactions.detail(id),
    queryFn: () => interactionsApi.detail(id),
    select: (response) => response.data,
    enabled: !!id,
  });
};

export const useCreateInteraction = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: interactionsApi.create,
    onSuccess: (_response) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.interactions.all });
      toast.success('Interaction created successfully');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to create interaction');
    },
  });
};

export const useUpdateInteraction = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: UpdateInteractionDto }) =>
      interactionsApi.update(id, data),

    onMutate: async ({ id, data }) => {
      await queryClient.cancelQueries({ queryKey: queryKeys.interactions.detail(id) });
      const previousInteraction = queryClient.getQueryData(queryKeys.interactions.detail(id));

      queryClient.setQueryData(queryKeys.interactions.detail(id), (old: Interaction | undefined) => {
        if (!old) return old;
        return { ...old, ...data };
      });

      return { previousInteraction };
    },

    onError: (_err, { id }, context) => {
      queryClient.setQueryData(queryKeys.interactions.detail(id), context?.previousInteraction);
      toast.error('Failed to update interaction');
    },

    onSettled: (_data, _error, { id }) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.interactions.detail(id) });
      queryClient.invalidateQueries({ queryKey: queryKeys.interactions.all });
    },

    onSuccess: () => {
      toast.success('Interaction updated successfully');
    },
  });
};

export const useCompleteInteraction = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: interactionsApi.complete,
    onSuccess: (_response, id) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.interactions.detail(id) });
      queryClient.invalidateQueries({ queryKey: queryKeys.interactions.all });
      toast.success('Interaction marked as completed');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to complete interaction');
    },
  });
};

export const useDeleteInteraction = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: interactionsApi.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.interactions.all });
      toast.success('Interaction deleted successfully');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to delete interaction');
    },
  });
};
