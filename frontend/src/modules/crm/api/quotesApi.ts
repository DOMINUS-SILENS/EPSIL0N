import { api, ProjectionResponse, CommandResponse } from '@/core/api/client';

export interface QuoteItem {
  id: number;
  quote_id: number;
  product_id: number;
  product_name: string;
  quantity: number;
  unit_price: number;
  discount_percent: number;
  tax_percent: number;
  total: number;
}

export interface Quote {
  id: number;
  reference: string;
  customer_id: number;
  customer_name: string;
  opportunity_id?: number;
  state: 'draft' | 'sent' | 'accepted' | 'rejected' | 'expired';
  issue_date: string;
  expiry_date: string;
  currency: string;
  subtotal: number;
  discount_total: number;
  tax_total: number;
  total: number;
  notes?: string;
  created_by: number;
  created_at: string;
  updated_at: string;
  items: QuoteItem[];
}

export interface CreateQuoteDto {
  customer_id: number;
  opportunity_id?: number;
  issue_date: string;
  expiry_date: string;
  currency: string;
  notes?: string;
  items: {
    product_id: number;
    quantity: number;
    unit_price: number;
    discount_percent?: number;
    tax_percent?: number;
  }[];
}

export interface UpdateQuoteDto {
  issue_date?: string;
  expiry_date?: string;
  notes?: string;
}

export const quotesApi = {
  list: async (params?: {
    customer_id?: number;
    opportunity_id?: number;
    state?: string;
    page?: number;
    per_page?: number;
  }): Promise<ProjectionResponse<{ data: Quote[]; meta: { total: number } }>> => {
    const response = await api.get('/projections/quotes', { params });
    return response.data;
  },

  detail: async (id: number): Promise<ProjectionResponse<Quote>> => {
    const response = await api.get(`/projections/quotes/${id}`);
    return response.data;
  },

  create: async (data: CreateQuoteDto): Promise<CommandResponse<Quote>> => {
    const response = await api.post('/commands/quotes', data);
    return response.data;
  },

  update: async (id: number, data: UpdateQuoteDto): Promise<CommandResponse<Quote>> => {
    const response = await api.put(`/commands/quotes/${id}`, data);
    return response.data;
  },

  send: async (id: number): Promise<CommandResponse<Quote>> => {
    const response = await api.post(`/commands/quotes/${id}/send`);
    return response.data;
  },

  accept: async (id: number): Promise<CommandResponse<{ order_id: number }>> => {
    const response = await api.post(`/commands/quotes/${id}/accept`);
    return response.data;
  },

  reject: async (id: number, reason?: string): Promise<CommandResponse<Quote>> => {
    const response = await api.post(`/commands/quotes/${id}/reject`, { reason });
    return response.data;
  },

  expire: async (id: number): Promise<CommandResponse<Quote>> => {
    const response = await api.post(`/commands/quotes/${id}/expire`);
    return response.data;
  },

  addItem: async (quoteId: number, item: CreateQuoteDto['items'][0]): Promise<CommandResponse<QuoteItem>> => {
    const response = await api.post(`/commands/quotes/${quoteId}/items`, item);
    return response.data;
  },

  removeItem: async (quoteId: number, itemId: number): Promise<CommandResponse<void>> => {
    const response = await api.delete(`/commands/quotes/${quoteId}/items/${itemId}`);
    return response.data;
  },
};
