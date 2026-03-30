import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { ordersApi } from '../api/ordersApi';
import { Order, OrderListParams, UpdateOrderDto } from '../api/types';
import { toast } from 'sonner';

export const queryKeys = {
  orders: {
    all: ['orders'] as const,
    list: (params?: OrderListParams) => [...queryKeys.orders.all, 'list', params] as const,
    detail: (id: string) => [...queryKeys.orders.all, 'detail', id] as const,
  },
};

export const useOrders = (params?: OrderListParams) => {
  return useQuery({
    queryKey: queryKeys.orders.list(params),
    queryFn: () => ordersApi.list(params),
    select: (response) => response.data,
  });
};

export const useOrder = (id: string) => {
  return useQuery({
    queryKey: queryKeys.orders.detail(id),
    queryFn: () => ordersApi.detail(id),
    select: (response) => response.data,
    enabled: !!id,
  });
};

export const useCreateOrder = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ordersApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.orders.all });
      toast.success('Order created successfully');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to create order');
    },
  });
};

export const useUpdateOrder = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: string; data: UpdateOrderDto }) =>
      ordersApi.update(id, data),

    // Optimistic update
    onMutate: async ({ id, data }) => {
      await queryClient.cancelQueries({ queryKey: queryKeys.orders.detail(id) });
      const previousOrder = queryClient.getQueryData(queryKeys.orders.detail(id));

      queryClient.setQueryData(queryKeys.orders.detail(id), (old: Order | undefined) => {
        if (!old) return old;
        return { ...old, ...data };
      });

      // Also update the list if needed (optional)
      queryClient.setQueriesData({ queryKey: queryKeys.orders.all }, (old: unknown) => {
        // careful: we need to find the order in the paginated list
        // this is a simplified version; you might want to update the list as well
        return old;
      });

      return { previousOrder };
    },

    onError: (_err: any, { id }, context) => {
      queryClient.setQueryData(queryKeys.orders.detail(id), context?.previousOrder);
      toast.error('Failed to update order');
    },

    onSettled: (_data, _error, { id }) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.orders.detail(id) });
      queryClient.invalidateQueries({ queryKey: queryKeys.orders.all });
    },

    onSuccess: () => {
      toast.success('Order updated successfully');
    },
  });
};

export const useConfirmOrder = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => ordersApi.confirm(id),
    onSuccess: (_data, id) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.orders.detail(id) });
      queryClient.invalidateQueries({ queryKey: queryKeys.orders.all });
      toast.success('Order confirmed');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to confirm order');
    },
  });
};
