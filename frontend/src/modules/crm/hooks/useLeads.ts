import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { leadsApi, Lead, CreateLeadCommand, UpdateLeadCommand } from '../api/leadsApi';
import { toast } from 'sonner';

export const queryKeys = {
  leads: {
    all: ['crm', 'leads'] as const,
    list: (params?: any) => [...queryKeys.leads.all, 'list', params] as const,
    detail: (id: number) => [...queryKeys.leads.all, 'detail', id] as const,
  },
};

export const useLeads = (params?: { page?: number; per_page?: number; state?: string; search?: string }) => {
  return useQuery({
    queryKey: queryKeys.leads.list(params),
    queryFn: () => leadsApi.list(params),
    select: (response) => response.data,
  });
};

export const useLead = (id: number) => {
  return useQuery({
    queryKey: queryKeys.leads.detail(id),
    queryFn: () => leadsApi.detail(id),
    select: (response) => response.data,
    enabled: !!id,
  });
};

export const useCreateLead = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (cmd: CreateLeadCommand) => leadsApi.create(cmd),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.leads.all });
      toast.success('CreateLead command dispatched successfully');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to dispatch CreateLead');
    },
  });
};

export const useUpdateLead = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: UpdateLeadCommand }) =>
      leadsApi.update(id, data),

    onMutate: async ({ id, data }) => {
      await queryClient.cancelQueries({ queryKey: queryKeys.leads.detail(id) });
      const previousLead = queryClient.getQueryData(queryKeys.leads.detail(id));

      queryClient.setQueryData(queryKeys.leads.detail(id), (old: Lead | undefined) => {
        if (!old) return old;
        return { ...old, ...data };
      });

      return { previousLead };
    },

    onError: (_err, { id }, context) => {
      queryClient.setQueryData(queryKeys.leads.detail(id), context?.previousLead);
      toast.error('Failed to dispatch UpdateLead command');
    },

    onSettled: (_data, _error, { id }) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.leads.detail(id) });
      queryClient.invalidateQueries({ queryKey: queryKeys.leads.all });
    },

    onSuccess: () => {
      toast.success('UpdateLead command dispatched successfully');
    },
  });
};

export const useConvertLead = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => leadsApi.convert(id),
    onSuccess: (_, id) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.leads.detail(id) });
      queryClient.invalidateQueries({ queryKey: queryKeys.leads.all });
      toast.success('ConvertLead command dispatched successfully');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to dispatch ConvertLead');
    },
  });
};
