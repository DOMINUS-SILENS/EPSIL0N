import { api } from '@/core/api/client';
import { PaginatedResponse } from '@/core/api/types';

export interface PurchaseOrder {
  id: number;
  reference: string;
  supplier_id: number;
  supplier_name: string;
  status: 'draft' | 'sent' | 'confirmed' | 'partial' | 'received' | 'cancelled';
  order_date: string;
  expected_date?: string;
  received_date?: string;
  total_amount: number;
  currency: string;
  items: PurchaseOrderItem[];
  notes?: string;
  created_at: string;
  updated_at: string;
}

export interface PurchaseOrderItem {
  id: number;
  product_id: number;
  product_name: string;
  product_sku: string;
  quantity: number;
  received_quantity: number;
  unit_price: number;
  total_price: number;
}

export interface CreatePurchaseOrderDto {
  supplier_id: number;
  expected_date?: string;
  items: {
    product_id: number;
    quantity: number;
    unit_price: number;
  }[];
  notes?: string;
}

export interface UpdatePurchaseOrderDto {
  expected_date?: string;
  notes?: string;
  items?: {
    id?: number;
    product_id: number;
    quantity: number;
    unit_price: number;
  }[];
}

export const purchaseOrdersApi = {
  list: (params?: { 
    page?: number; 
    per_page?: number; 
    supplier_id?: number; 
    status?: string; 
    search?: string 
  }) =>
    api.get<PaginatedResponse<PurchaseOrder>>('/erp/purchase-orders', { params }),

  detail: (id: number) =>
    api.get<PurchaseOrder>(`/erp/purchase-orders/${id}`),

  create: (data: CreatePurchaseOrderDto) =>
    api.post<PurchaseOrder>('/erp/purchase-orders', data),

  update: (id: number, data: UpdatePurchaseOrderDto) =>
    api.put<PurchaseOrder>(`/erp/purchase-orders/${id}`, data),

  confirm: (id: number) =>
    api.post<PurchaseOrder>(`/erp/purchase-orders/${id}/confirm`),

  cancel: (id: number) =>
    api.post<PurchaseOrder>(`/erp/purchase-orders/${id}/cancel`),

  receive: (id: number, items: { item_id: number; received_quantity: number }[]) =>
    api.post<PurchaseOrder>(`/erp/purchase-orders/${id}/receive`, { items }),

  getSuppliers: () =>
    api.get<any[]>('/erp/suppliers'),
};
