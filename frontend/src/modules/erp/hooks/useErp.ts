import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { erpApi, Product, UpdateProductPriceCommand, CreateOrderCommand } from '../api/erpApi';
import { toast } from 'sonner';

export const erpQueryKeys = {
  products: {
    all: ['erp', 'products'] as const,
    list: (params?: any) => [...erpQueryKeys.products.all, 'list', params] as const,
    detail: (id: number) => [...erpQueryKeys.products.all, 'detail', id] as const,
  },
  orders: {
    all: ['erp', 'orders'] as const,
    list: (params?: any) => [...erpQueryKeys.orders.all, 'list', params] as const,
    detail: (id: number) => [...erpQueryKeys.orders.all, 'detail', id] as const,
  }
};

// --- PRODUCTS ---
export const useProducts = (params?: { page?: number; per_page?: number; search?: string }) => {
  return useQuery({
    queryKey: erpQueryKeys.products.list(params),
    queryFn: () => erpApi.products.list(params),
    select: (res) => res.data,
  });
};

export const useUpdateProductPrice = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: ({ id, cmd }: { id: number; cmd: UpdateProductPriceCommand }) => 
      erpApi.commands.updateProductPrice(id, cmd),
      
    onMutate: async ({ id, cmd }) => {
      await queryClient.cancelQueries({ queryKey: erpQueryKeys.products.detail(id) });
      const previous = queryClient.getQueryData(erpQueryKeys.products.detail(id));
      
      queryClient.setQueryData(erpQueryKeys.products.detail(id), (old: Product | undefined) => {
        if (!old) return old;
        return { ...old, price: cmd.price };
      });
      return { previous };
    },
    onError: (_err, { id }, context) => {
      queryClient.setQueryData(erpQueryKeys.products.detail(id), context?.previous);
      toast.error('Failed to dispatch UpdateProductPrice');
    },
    onSettled: (_data, _err, { id }) => {
      queryClient.invalidateQueries({ queryKey: erpQueryKeys.products.detail(id) });
      queryClient.invalidateQueries({ queryKey: erpQueryKeys.products.all });
    },
    onSuccess: () => {
      toast.success('UpdateProductPrice command dispatched');
    }
  });
};

// --- ORDERS ---
export const useOrders = (params?: { page?: number; per_page?: number; status?: string; search?: string }) => {
  return useQuery({
    queryKey: erpQueryKeys.orders.list(params),
    queryFn: () => erpApi.orders.list(params),
    select: (res) => res.data,
  });
};

export const useCreateOrder = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (cmd: CreateOrderCommand) => erpApi.commands.createOrder(cmd),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: erpQueryKeys.orders.all });
      toast.success('CreateOrder command dispatched');
    },
    onError: () => toast.error('Failed to dispatch CreateOrder')
  });
};

export const useOrderAction = (action: 'confirm' | 'deliver' | 'invoice' | 'cancel') => {
  const queryClient = useQueryClient();
  
  const fnMap = {
    confirm: erpApi.commands.confirmOrder,
    deliver: erpApi.commands.deliverOrder,
    invoice: erpApi.commands.invoiceOrder,
    cancel: erpApi.commands.cancelOrder,
  };
  
  return useMutation({
    mutationFn: (id: number) => fnMap[action](id),
    onSuccess: (_data, id) => {
      queryClient.invalidateQueries({ queryKey: erpQueryKeys.orders.detail(id) });
      queryClient.invalidateQueries({ queryKey: erpQueryKeys.orders.all });
      toast.success(`${action} command dispatched`);
    },
    onError: () => toast.error(`Failed to dispatch ${action} command`)
  });
};
