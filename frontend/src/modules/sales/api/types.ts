export interface Order {
  id: string;
  reference: string;
  customer_id: number;
  customer_name: string;
  status: string;
  total_ht: number;
  total_ttc: number;
  created_at: string;
  updated_at: string;
}

export interface OrderItem {
  id: number;
  order_id: number;
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
  // other fields you allow editing
}

export interface OrderListParams {
  page?: number;
  per_page?: number;
  state?: string;
  customer_id?: number;
  search?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    from: number;
    to: number;
  };
}
