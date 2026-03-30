import { api } from '@/core/api/client';
import { PaginatedResponse } from '@/core/api/types';
import { dispatchCommand } from '@/core/api/commandClient';

export interface Lead {
  id: number;
  reference: string;
  first_name: string;
  last_name: string;
  email: string;
  phone?: string;
  source?: string;
  state: 'new' | 'contacted' | 'qualified' | 'lost';
  assigned_to?: number;
  created_at: string;
  updated_at: string;
}

export interface CreateLeadCommand {
  first_name: string;
  last_name: string;
  email: string;
  phone?: string;
  source?: string;
}

export interface UpdateLeadCommand {
  first_name?: string;
  last_name?: string;
  email?: string;
  phone?: string;
  source?: string;
  state?: string;
  assigned_to?: number;
}

export const leadsApi = {
  // Projections (Queries)
  list: (params?: { page?: number; per_page?: number; state?: string; search?: string }) =>
    api.get<PaginatedResponse<Lead>>('/crm/leads', { params }),

  detail: (id: number) =>
    api.get<Lead>(`/crm/leads/${id}`),

  // Commands (Mutations)
  create: (data: CreateLeadCommand) =>
    dispatchCommand<{ id: number }>('/api/crm/leads/command', { type: 'CreateLead', payload: data }),

  update: (id: number, data: UpdateLeadCommand) =>
    dispatchCommand(`/api/crm/leads/${id}/command`, { type: 'UpdateLead', payload: data }),

  convert: (id: number) =>
    dispatchCommand<{ customer_id: number }>(`/api/crm/leads/${id}/command`, { type: 'ConvertLead', payload: {} }),
};
