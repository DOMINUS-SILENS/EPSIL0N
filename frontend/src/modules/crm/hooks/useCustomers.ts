import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { customersApi, Customer, UpdateCustomerDto } from '../api/customersApi';
import { toast } from 'sonner';

export const queryKeys = {
  customers: {
    all: ['customers'] as const,
    list: (params?: any) => [...queryKeys.customers.all, 'list', params] as const,
    detail: (id: number) => [...queryKeys.customers.all, 'detail', id] as const,
  },
};

export const useCustomers = (params?: { page?: number; per_page?: number; search?: string; is_active?: boolean }) => {
  return useQuery({
    queryKey: queryKeys.customers.list(params),
    queryFn: () => customersApi.list(params),
    select: (response) => response.data,
  });
};

export const useCustomer = (id: number) => {
  return useQuery({
    queryKey: queryKeys.customers.detail(id),
    queryFn: () => customersApi.detail(id),
    select: (response) => response.data,
    enabled: !!id,
  });
};

export const useCreateCustomer = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: customersApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.customers.all });
      toast.success('Customer created successfully');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to create customer');
    },
  });
};

export const useUpdateCustomer = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: UpdateCustomerDto }) =>
      customersApi.update(id, data),

    onMutate: async ({ id, data }) => {
      await queryClient.cancelQueries({ queryKey: queryKeys.customers.detail(id) });
      const previousCustomer = queryClient.getQueryData(queryKeys.customers.detail(id));

      queryClient.setQueryData(queryKeys.customers.detail(id), (old: Customer | undefined) => {
        if (!old) return old;
        return { ...old, ...data };
      });

      return { previousCustomer };
    },

    onError: (_err, { id }, context) => {
      queryClient.setQueryData(queryKeys.customers.detail(id), context?.previousCustomer);
      toast.error('Failed to update customer');
    },

    onSettled: (_data, _error, { id }) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.customers.detail(id) });
      queryClient.invalidateQueries({ queryKey: queryKeys.customers.all });
    },

    onSuccess: () => {
      toast.success('Customer updated successfully');
    },
  });
};

export const useDeactivateCustomer = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: customersApi.deactivate,
    onSuccess: (_data, id) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.customers.detail(id) });
      queryClient.invalidateQueries({ queryKey: queryKeys.customers.all });
      toast.success('Customer deactivated');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to deactivate customer');
    },
  });
};

export const useActivateCustomer = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: customersApi.activate,
    onSuccess: (_data, id) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.customers.detail(id) });
      queryClient.invalidateQueries({ queryKey: queryKeys.customers.all });
      toast.success('Customer activated');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to activate customer');
    },
  });
};
