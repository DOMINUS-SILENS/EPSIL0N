import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { opportunitiesApi, Opportunity, UpdateOpportunityDto } from '../api/opportunitiesApi';
import { toast } from 'sonner';

export const queryKeys = {
  opportunities: {
    all: ['opportunities'] as const,
    list: (params?: OpportunitiesParams) => [...queryKeys.opportunities.all, 'list', params] as const,
    detail: (id: number) => [...queryKeys.opportunities.all, 'detail', id] as const,
  },
};

interface OpportunitiesParams {
  page?: number;
  per_page?: number;
  stage?: string;
  search?: string;
}

interface ApiError {
  response?: {
    data?: {
      message?: string;
    };
  };
}

export const useOpportunities = (params?: OpportunitiesParams) => {
  return useQuery({
    queryKey: queryKeys.opportunities.list(params),
    queryFn: () => opportunitiesApi.list(params),
    select: (response) => response.data,
  });
};

export const useOpportunity = (id: number) => {
  return useQuery({
    queryKey: queryKeys.opportunities.detail(id),
    queryFn: () => opportunitiesApi.detail(id),
    select: (response) => response.data,
    enabled: !!id,
  });
};

export const useCreateOpportunity = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: opportunitiesApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.opportunities.all });
      toast.success('Opportunity created successfully');
    },
    onError: (error: ApiError) => {
      toast.error(error.response?.data?.message || 'Failed to create opportunity');
    },
  });
};

interface UpdateOpportunityVariables {
  id: number;
  data: UpdateOpportunityDto;
}

interface MutationContext {
  previousOpportunity: Opportunity | undefined;
}

export const useUpdateOpportunity = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: UpdateOpportunityVariables) =>
      opportunitiesApi.update(id, data),

    onMutate: async ({ id, data }: UpdateOpportunityVariables): Promise<MutationContext> => {
      await queryClient.cancelQueries({ queryKey: queryKeys.opportunities.detail(id) });
      const previousOpportunity = queryClient.getQueryData<Opportunity>(queryKeys.opportunities.detail(id));

      queryClient.setQueryData<Opportunity>(queryKeys.opportunities.detail(id), (old: Opportunity | undefined) => {
        if (!old) return old;
        return { ...old, ...data } as Opportunity;
      });

      return { previousOpportunity: previousOpportunity };
    },

    onError: (_err: ApiError, { id }: UpdateOpportunityVariables, context?: MutationContext) => {
      queryClient.setQueryData(queryKeys.opportunities.detail(id), context?.previousOpportunity);
      toast.error('Failed to update opportunity');
    },

    onSettled: (_data: unknown, _error: ApiError | null, { id }: UpdateOpportunityVariables) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.opportunities.detail(id) });
      queryClient.invalidateQueries({ queryKey: queryKeys.opportunities.all });
    },

    onSuccess: () => {
      toast.success('Opportunity updated successfully');
    },
  });
};

export const useWinOpportunity = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: opportunitiesApi.win,
    onSuccess: (_response: unknown, id: number) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.opportunities.detail(id) });
      queryClient.invalidateQueries({ queryKey: queryKeys.opportunities.all });
      toast.success('Opportunity marked as won!');
    },
    onError: (error: ApiError) => {
      toast.error(error.response?.data?.message || 'Failed to win opportunity');
    },
  });
};

interface LoseOpportunityVariables {
  id: number;
  reason?: string;
}

export const useLoseOpportunity = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, reason }: LoseOpportunityVariables) =>
      opportunitiesApi.lose(id, reason),
    onSuccess: (_response: unknown, { id }: LoseOpportunityVariables) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.opportunities.detail(id) });
      queryClient.invalidateQueries({ queryKey: queryKeys.opportunities.all });
      toast.success('Opportunity marked as lost');
    },
    onError: (error: ApiError) => {
      toast.error(error.response?.data?.message || 'Failed to lose opportunity');
    },
  });
};

interface UpdateStageVariables {
  id: number;
  stage: string;
}

export const useUpdateOpportunityStage = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, stage }: UpdateStageVariables) =>
      opportunitiesApi.updateStage(id, stage),

    onMutate: async ({ id, stage }: UpdateStageVariables): Promise<MutationContext> => {
      await queryClient.cancelQueries({ queryKey: queryKeys.opportunities.detail(id) });
      const previousOpportunity = queryClient.getQueryData<Opportunity>(queryKeys.opportunities.detail(id));

      queryClient.setQueryData<Opportunity>(queryKeys.opportunities.detail(id), (old: Opportunity | undefined) => {
        if (!old) return old;
        return { ...old, stage: stage as Opportunity['stage'] };
      });

      return { previousOpportunity: previousOpportunity };
    },

    onError: (_err: ApiError, { id }: UpdateStageVariables, context?: MutationContext) => {
      queryClient.setQueryData(queryKeys.opportunities.detail(id), context?.previousOpportunity);
      toast.error('Failed to update stage');
    },

    onSettled: (_data: unknown, _error: ApiError | null, { id }: UpdateStageVariables) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.opportunities.detail(id) });
      queryClient.invalidateQueries({ queryKey: queryKeys.opportunities.all });
    },

    onSuccess: () => {
      toast.success('Stage updated successfully');
    },
  });
};
