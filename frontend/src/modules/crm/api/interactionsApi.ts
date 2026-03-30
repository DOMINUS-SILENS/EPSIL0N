import { api, ProjectionResponse, CommandResponse } from '@/core/api/client';

export interface Interaction {
  id: number;
  customer_id?: number;
  lead_id?: number;
  opportunity_id?: number;
  type: 'call' | 'email' | 'meeting' | 'note' | 'task';
  subject: string;
  content: string;
  scheduled_at?: string;
  completed_at?: string;
  created_by: number;
  created_at: string;
  updated_at: string;
}

export interface CreateInteractionDto {
  customer_id?: number;
  lead_id?: number;
  opportunity_id?: number;
  type: 'call' | 'email' | 'meeting' | 'note' | 'task';
  subject: string;
  content: string;
  scheduled_at?: string;
}

export interface UpdateInteractionDto {
  subject?: string;
  content?: string;
  scheduled_at?: string;
  completed_at?: string;
}

export const interactionsApi = {
  list: async (params?: {
    customer_id?: number;
    lead_id?: number;
    opportunity_id?: number;
    type?: string;
    page?: number;
    per_page?: number;
  }): Promise<ProjectionResponse<{ data: Interaction[]; meta: { total: number } }>> => {
    const response = await api.get('/projections/interactions', { params });
    return response.data;
  },

  detail: async (id: number): Promise<ProjectionResponse<Interaction>> => {
    const response = await api.get(`/projections/interactions/${id}`);
    return response.data;
  },

  create: async (data: CreateInteractionDto): Promise<CommandResponse<Interaction>> => {
    const response = await api.post('/commands/interactions', data);
    return response.data;
  },

  update: async (id: number, data: UpdateInteractionDto): Promise<CommandResponse<Interaction>> => {
    const response = await api.put(`/commands/interactions/${id}`, data);
    return response.data;
  },

  complete: async (id: number): Promise<CommandResponse<Interaction>> => {
    const response = await api.post(`/commands/interactions/${id}/complete`);
    return response.data;
  },

  delete: async (id: number): Promise<CommandResponse<void>> => {
    const response = await api.delete(`/commands/interactions/${id}`);
    return response.data;
  },
};
