import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { quotesApi, Quote, UpdateQuoteDto } from '../api/quotesApi';
import { toast } from 'sonner';

export const queryKeys = {
  quotes: {
    all: ['quotes'] as const,
    list: (params?: any) => [...queryKeys.quotes.all, 'list', params] as const,
    detail: (id: number) => [...queryKeys.quotes.all, 'detail', id] as const,
    byCustomer: (customerId: number) => [...queryKeys.quotes.all, 'customer', customerId] as const,
    byOpportunity: (opportunityId: number) => [...queryKeys.quotes.all, 'opportunity', opportunityId] as const,
  },
};

export const useQuotes = (params?: {
  customer_id?: number;
  opportunity_id?: number;
  state?: string;
  page?: number;
  per_page?: number;
}) => {
  return useQuery({
    queryKey: queryKeys.quotes.list(params),
    queryFn: () => quotesApi.list(params),
    select: (response) => response.data,
    refetchInterval: 30000,
  });
};

export const useQuote = (id: number) => {
  return useQuery({
    queryKey: queryKeys.quotes.detail(id),
    queryFn: () => quotesApi.detail(id),
    select: (response) => response.data,
    enabled: !!id,
  });
};

export const useCreateQuote = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: quotesApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.quotes.all });
      toast.success('Quote created successfully');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to create quote');
    },
  });
};

export const useUpdateQuote = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: UpdateQuoteDto }) =>
      quotesApi.update(id, data),

    onMutate: async ({ id, data }) => {
      await queryClient.cancelQueries({ queryKey: queryKeys.quotes.detail(id) });
      const previousQuote = queryClient.getQueryData(queryKeys.quotes.detail(id));

      queryClient.setQueryData(queryKeys.quotes.detail(id), (old: Quote | undefined) => {
        if (!old) return old;
        return { ...old, ...data };
      });

      return { previousQuote };
    },

    onError: (_err, { id }, context) => {
      queryClient.setQueryData(queryKeys.quotes.detail(id), context?.previousQuote);
      toast.error('Failed to update quote');
    },

    onSettled: (_data, _error, { id }) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.quotes.detail(id) });
      queryClient.invalidateQueries({ queryKey: queryKeys.quotes.all });
    },

    onSuccess: () => {
      toast.success('Quote updated successfully');
    },
  });
};

export const useSendQuote = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: quotesApi.send,
    onSuccess: (_, id) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.quotes.detail(id) });
      queryClient.invalidateQueries({ queryKey: queryKeys.quotes.all });
      toast.success('Quote sent successfully');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to send quote');
    },
  });
};

export const useAcceptQuote = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: quotesApi.accept,
    onSuccess: (_, id) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.quotes.detail(id) });
      queryClient.invalidateQueries({ queryKey: queryKeys.quotes.all });
      toast.success('Quote accepted. Order created successfully.');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to accept quote');
    },
  });
};

export const useRejectQuote = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, reason }: { id: number; reason?: string }) => quotesApi.reject(id, reason),
    onSuccess: (_, { id }) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.quotes.detail(id) });
      queryClient.invalidateQueries({ queryKey: queryKeys.quotes.all });
      toast.success('Quote rejected');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to reject quote');
    },
  });
};

export const useAddQuoteItem = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ quoteId, item }: { quoteId: number; item: Parameters<typeof quotesApi.addItem>[1] }) =>
      quotesApi.addItem(quoteId, item),
    onSuccess: (_, { quoteId }) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.quotes.detail(quoteId) });
      toast.success('Item added to quote');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to add item');
    },
  });
};

export const useRemoveQuoteItem = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ quoteId, itemId }: { quoteId: number; itemId: number }) =>
      quotesApi.removeItem(quoteId, itemId),
    onSuccess: (_, { quoteId }) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.quotes.detail(quoteId) });
      toast.success('Item removed from quote');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to remove item');
    },
  });
};
