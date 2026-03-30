import { api } from '@/core/api/client';
import { PaginatedResponse } from '@/core/api/types';
import { dispatchCommand } from '@/core/api/commandClient';

export interface Product {
  id: number;
  sku: string;
  name: string;
  stock: number;
  price: number;
  image_url?: string;
}

export interface Order {
  id: number;
  reference: string;
  customer_id: number;
  customer_name?: string;
  status: 'draft' | 'confirmed' | 'delivered' | 'invoiced' | 'cancelled';
  totalAmount: number;
  created_at: string;
}

export interface UpdateProductPriceCommand {
  price: number;
}

export interface CreateOrderCommand {
  customer_id: number;
  items: Array<{ product_id: number; quantity: number }>;
}

export const erpApi = {
  // Projections (Queries)
  products: {
    list: (params?: { page?: number; per_page?: number; search?: string }) => 
      api.get<PaginatedResponse<Product>>('/erp/products', { params }),
    detail: (id: number) => 
      api.get<Product>(`/erp/products/${id}`),
  },
  
  orders: {
    list: (params?: { page?: number; per_page?: number; status?: string; search?: string }) => 
      api.get<PaginatedResponse<Order>>('/erp/orders', { params }),
    detail: (id: number) => 
      api.get<Order>(`/erp/orders/${id}`),
  },

  // Commands (Mutations)
  commands: {
    updateProductPrice: (id: number, cmd: UpdateProductPriceCommand) =>
      dispatchCommand(`/api/erp/products/${id}/command`, { type: 'UpdateProductPrice', payload: cmd }),
      
    createOrder: (cmd: CreateOrderCommand) =>
      dispatchCommand<{ id: number }>('/api/erp/orders/command', { type: 'CreateOrder', payload: cmd }),
      
    confirmOrder: (id: number) =>
      dispatchCommand(`/api/erp/orders/${id}/command`, { type: 'ConfirmOrder', payload: {} }),
      
    deliverOrder: (id: number) =>
      dispatchCommand(`/api/erp/orders/${id}/command`, { type: 'DeliverOrder', payload: {} }),
      
    invoiceOrder: (id: number) =>
      dispatchCommand(`/api/erp/orders/${id}/command`, { type: 'InvoiceOrder', payload: {} }),
      
    cancelOrder: (id: number) =>
      dispatchCommand(`/api/erp/orders/${id}/command`, { type: 'CancelOrder', payload: {} }),
  }
};
