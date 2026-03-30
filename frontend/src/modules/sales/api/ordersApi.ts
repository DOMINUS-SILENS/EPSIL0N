import { api } from '@/core/api/client';
import { Order, CreateOrderDto, UpdateOrderDto, OrderListParams, PaginatedResponse } from './types';

export const ordersApi = {
  list: (params?: OrderListParams) =>
    api.get<PaginatedResponse<Order>>('/erp/orders', { params }),

  detail: (id: string) =>
    api.get<Order>(`/erp/orders/${id}`),

  create: (data: CreateOrderDto) =>
    api.post<Order>('/erp/orders', data),

  update: (id: string, data: UpdateOrderDto) =>
    api.put<Order>(`/erp/orders/${id}`, data),

  confirm: (id: string) =>
    api.post<Order>(`/erp/orders/${id}/confirm`),

  cancel: (id: string) =>
    api.patch<Order>(`/erp/orders/${id}/cancel`),
};
