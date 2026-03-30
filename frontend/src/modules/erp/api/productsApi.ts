import { api } from '@/core/api/client';
import { PaginatedResponse } from '@/core/api/types';

export interface Product {
  id: number;
  reference: string;
  name: string;
  sku: string;
  barcode?: string;
  category_id?: number;
  purchase_price: number;
  sale_price: number;
  stock_qty: number; // derived from moves
  active: boolean;
}

export interface CreateProductDto {
  reference: string;
  name: string;
  sku: string;
  barcode?: string;
  category_id?: number;
  purchase_price: number;
  sale_price: number;
}

export const productsApi = {
  list: (params?: { page?: number; per_page?: number; search?: string; active?: boolean }) =>
    api.get<PaginatedResponse<Product>>('/erp/products', { params }),

  detail: (id: number) =>
    api.get<Product>(`/erp/products/${id}`),

  create: (data: CreateProductDto) =>
    api.post<Product>('/erp/products', data),

  update: (id: number, data: Partial<CreateProductDto>) =>
    api.put<Product>(`/erp/products/${id}`, data),

  stockHistory: (productId: number, params?: { warehouse_id?: number; from?: string; to?: string }) =>
    api.get(`/erp/stock/history/${productId}`, { params }),
};
