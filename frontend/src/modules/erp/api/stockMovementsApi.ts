import { api } from '@/core/api/client';
import { PaginatedResponse } from '@/core/api/types';

export interface StockMovement {
  id: number;
  reference: string;
  product_id: number;
  product_name: string;
  product_sku: string;
  warehouse_id?: number;
  warehouse_name?: string;
  type: 'in' | 'out' | 'adjustment' | 'transfer';
  quantity: number;
  unit_cost?: number;
  reference_type: 'purchase' | 'sale' | 'adjustment' | 'transfer' | 'manual';
  reference_id?: number;
  reason?: string;
  created_at: string;
  created_by: string;
}

export interface CreateStockAdjustmentDto {
  product_id: number;
  warehouse_id?: number;
  quantity: number;
  reason: string;
  unit_cost?: number;
}

export const stockMovementsApi = {
  list: (params?: { 
    page?: number; 
    per_page?: number; 
    product_id?: number; 
    warehouse_id?: number; 
    type?: string; 
    from?: string; 
    to?: string 
  }) =>
    api.get<PaginatedResponse<StockMovement>>('/erp/stock/movements', { params }),

  detail: (id: number) =>
    api.get<StockMovement>(`/erp/stock/movements/${id}`),

  adjust: (data: CreateStockAdjustmentDto) =>
    api.post<StockMovement>('/erp/stock/adjust', data),

  getHistory: (productId: number, params?: { warehouse_id?: number; from?: string; to?: string }) =>
    api.get<PaginatedResponse<StockMovement>>(`/erp/stock/history/${productId}`, { params }),

  getWarehouseStats: (warehouseId?: number) =>
    api.get<any>('/erp/stock/stats', { params: { warehouse_id: warehouseId } }),
};
