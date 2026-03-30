import { api } from '@/core/api/client';
import { PaginatedResponse } from '@/core/api/types';

export interface Order {
  id: number;
  reference: string;
  customer_id: number;
  state: 'draft' | 'confirmed' | 'processing' | 'completed' | 'cancelled';
  order_date: string;
  subtotal: number;
  tax_amount: number;
  total_amount: number;
  notes?: string;
  created_at: string;
  updated_at: string;
  items?: OrderItem[];
}

export interface OrderItem {
  id: number;
  product_id: number;
  qty: number;
  unit_price: number;
  tax_rate: number;
  discount: number;
  subtotal: number;
}

export interface CreateOrderDto {
  customer_id: number;
  items: Array<{
    product_id: number;
    qty: number;
    unit_price: number;
    tax_rate?: number;
  }>;
  notes?: string;
}

export interface UpdateOrderDto {
  notes?: string;
}

export const ordersApi = {
  list: (params?: { page?: number; per_page?: number; state?: string; customer_id?: number; search?: string }) =>
    api.get<PaginatedResponse<Order>>('/erp/orders', { params }),

  detail: (id: number) =>
    api.get<Order>(`/erp/orders/${id}`),

  create: (data: CreateOrderDto) =>
    api.post<Order>('/erp/orders', data),

  update: (id: number, data: UpdateOrderDto) =>
    api.put<Order>(`/erp/orders/${id}`, data),

  confirm: (id: number) =>
    api.post<Order>(`/erp/orders/${id}/confirm`),

  cancel: (id: number) =>
    api.patch<Order>(`/erp/orders/${id}/cancel`),
};
