import { api } from '@/core/api/client';
import { PaginatedResponse } from '@/core/api/types';

export interface Customer {
  id: number;
  reference: string;
  name: string;
  email: string;
  phone?: string;
  address?: string;
  city?: string;
  country?: string;
  tax_id?: string;
  payment_terms?: string;
  credit_limit?: number;
  is_active: boolean;
  total_orders: number;
  total_revenue: number;
  last_order_date?: string;
  created_at: string;
  updated_at: string;
}

export interface CreateCustomerDto {
  name: string;
  email: string;
  phone?: string;
  address?: string;
  city?: string;
  country?: string;
  tax_id?: string;
  payment_terms?: string;
  credit_limit?: number;
}

export interface UpdateCustomerDto {
  name?: string;
  email?: string;
  phone?: string;
  address?: string;
  city?: string;
  country?: string;
  tax_id?: string;
  payment_terms?: string;
  credit_limit?: number;
  is_active?: boolean;
}

export const customersApi = {
  list: (params?: { page?: number; per_page?: number; search?: string; is_active?: boolean }) =>
    api.get<PaginatedResponse<Customer>>('/crm/customers', { params }),

  detail: (id: number) =>
    api.get<Customer>(`/crm/customers/${id}`),

  create: (data: CreateCustomerDto) =>
    api.post<Customer>('/crm/customers', data),

  update: (id: number, data: UpdateCustomerDto) =>
    api.put<Customer>(`/crm/customers/${id}`, data),

  deactivate: (id: number) =>
    api.patch<Customer>(`/crm/customers/${id}/deactivate`),

  activate: (id: number) =>
    api.patch<Customer>(`/crm/customers/${id}/activate`),

  getOrders: (id: number, params?: { page?: number; per_page?: number }) =>
    api.get<PaginatedResponse<any>>(`/crm/customers/${id}/orders`, { params }),

  getStats: (id: number) =>
    api.get<any>(`/crm/customers/${id}/stats`),
};
