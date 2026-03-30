import { api } from '@/core/api/client';
import { PaginatedResponse } from '@/core/api/types';

export interface Opportunity {
  id: number;
  reference: string;
  title: string;
  name?: string; // Alias for title used in some pages
  description?: string;
  lead_id: number;
  lead_name: string;
  customer_name?: string; // Alias for lead_name
  value: number;
  expected_revenue?: number; // Alias for value
  currency: string;
  stage: 'prospecting' | 'qualification' | 'proposal' | 'negotiation' | 'closed_won' | 'closed_lost';
  probability: number;
  expected_close_date: string;
  assigned_to?: number;
  assigned_to_name?: string;
  source?: string;
  campaign?: string;
  created_at: string;
  updated_at: string;
}

export interface CreateOpportunityDto {
  title: string;
  description?: string;
  lead_id: number;
  value: number;
  currency: string;
  stage: string;
  probability: number;
  expected_close_date: string;
  assigned_to?: number;
}

export interface UpdateOpportunityDto {
  title?: string;
  description?: string;
  value?: number;
  currency?: string;
  stage?: string;
  probability?: number;
  expected_close_date?: string;
  assigned_to?: number;
}

export const opportunitiesApi = {
  list: (params?: { page?: number; per_page?: number; stage?: string; search?: string }) =>
    api.get<PaginatedResponse<Opportunity>>('/crm/opportunities', { params }),

  detail: (id: number) =>
    api.get<Opportunity>(`/crm/opportunities/${id}`),

  create: (data: CreateOpportunityDto) =>
    api.post<Opportunity>('/crm/opportunities', data),

  update: (id: number, data: UpdateOpportunityDto) =>
    api.put<Opportunity>(`/crm/opportunities/${id}`, data),

  win: (id: number) =>
    api.post<Opportunity>(`/crm/opportunities/${id}/win`),

  lose: (id: number, reason?: string) =>
    api.post<Opportunity>(`/crm/opportunities/${id}/lose`, { reason }),

  updateStage: (id: number, stage: string) =>
    api.post<Opportunity>(`/crm/opportunities/${id}/stage`, { stage }),
};
