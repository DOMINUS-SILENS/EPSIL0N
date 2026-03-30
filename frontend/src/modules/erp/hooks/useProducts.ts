import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { productsApi, Product, CreateProductDto } from '../api/productsApi';
import { toast } from 'sonner';

export const queryKeys = {
  products: {
    all: ['products'] as const,
    list: (params?: any) => [...queryKeys.products.all, 'list', params] as const,
    detail: (id: number) => [...queryKeys.products.all, 'detail', id] as const,
  },
};

export const useProducts = (params?: { page?: number; per_page?: number; search?: string; active?: boolean }) => {
  return useQuery({
    queryKey: queryKeys.products.list(params),
    queryFn: () => productsApi.list(params),
    select: (response) => response.data,
  });
};

export const useProduct = (id: number) => {
  return useQuery({
    queryKey: queryKeys.products.detail(id),
    queryFn: () => productsApi.detail(id),
    select: (response) => response.data,
    enabled: !!id,
  });
};

export const useCreateProduct = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: productsApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.products.all });
      toast.success('Product created successfully');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to create product');
    },
  });
};

export const useUpdateProduct = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<CreateProductDto> }) =>
      productsApi.update(id, data),

    onMutate: async ({ id, data }) => {
      await queryClient.cancelQueries({ queryKey: queryKeys.products.detail(id) });
      const previousProduct = queryClient.getQueryData(queryKeys.products.detail(id));

      queryClient.setQueryData(queryKeys.products.detail(id), (old: Product | undefined) => {
        if (!old) return old;
        return { ...old, ...data };
      });

      return { previousProduct };
    },

    onError: (_err, { id }, context) => {
      queryClient.setQueryData(queryKeys.products.detail(id), context?.previousProduct);
      toast.error('Failed to update product');
    },

    onSettled: (_data, _error, { id }) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.products.detail(id) });
      queryClient.invalidateQueries({ queryKey: queryKeys.products.all });
    },

    onSuccess: () => {
      toast.success('Product updated successfully');
    },
  });
};

export const useStockHistory = (productId: number, params?: { warehouse_id?: number; from?: string; to?: string }) => {
  return useQuery({
    queryKey: ['stock-history', productId, params],
    queryFn: () => productsApi.stockHistory(productId, params),
    select: (response) => response.data,
    enabled: !!productId,
  });
};
