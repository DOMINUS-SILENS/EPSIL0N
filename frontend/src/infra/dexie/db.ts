import Dexie, { Table } from 'dexie';

export interface Command {
  id: string;            // UUID v7
  aggregateId: string;
  type: string;
  status: 'pending' | 'processed' | 'failed';
  payload: any;
  createdAt: Date;
}

export interface Event {
  id: string;            // UUID v7
  aggregateId: string;
  aggregateType: string;
  sequence: number;      // local sequence
  type: string;
  payload: any;
  occurredAt: Date;
  syncStatus: 'pending' | 'synced' | 'failed';
}

export interface Outbox {
  id: string;
  eventId: string;
  aggregateId: string;
  aggregateType: string;
  sequence: number;
  payload: any;
  status: 'pending' | 'sending' | 'acked' | 'failed' | 'dead';
  retryCount: number;
  nextRetryAt: Date | null;
  lastError: string | null;
  createdAt: Date;
  updatedAt: Date;
}

export interface AggregateVersion {
  aggregateId: string;
  aggregateType: string;
  version: number;
  updatedAt: Date;
}

export interface Idempotency {
  key: string;
  createdAt: Date;
}

export interface Conflict {
  id: string;
  aggregateId: string;
  type: string;
  serverReason: string;
  localEventId: string;
  status: 'pending' | 'resolved' | 'discarded';
  detectedAt: Date;
}

// Projections
export interface Customer {
  id: string;
  code: string;
  name: string;
  assignedTo: string;
  creditLimit: number;
  currentCredit: number;
  updatedAt: Date;
}

export interface Product {
  id: string;
  sku: string;
  name: string;
  price: number;
  updatedAt: Date;
}

export interface StockView {
  id: string;
  productId: string;
  warehouseId: string;
  serverConfirmedQty: number;
  localPendingReservations: number;
  availableQty: number;
  updatedAt: Date;
}

export interface Order {
  id: string;
  customerId: string;
  repId: string;
  status: 'draft_local' | 'synced' | 'confirmed' | 'rejected';
  totalAmount: number;
  createdAt: Date;
  updatedAt: Date;
}

export interface OrderLine {
  id: string;
  orderId: string;
  productId: string;
  quantity: number;
  unitPrice: number;
}

class AppDatabase extends Dexie {
  commands!: Table<Command, string>;
  events!: Table<Event, string>;
  outbox!: Table<Outbox, string>;
  aggregate_versions!: Table<AggregateVersion, string>;
  idempotency!: Table<Idempotency, string>;
  conflicts!: Table<Conflict, string>;

  customers!: Table<Customer, string>;
  products!: Table<Product, string>;
  stock_view!: Table<StockView, string>;
  orders!: Table<Order, string>;
  order_lines!: Table<OrderLine, string>;

  constructor() {
    super('SFA_DB');
    this.version(1).stores({
      commands: 'id, aggregateId, type, status, createdAt',
      events: 'id, aggregateId, aggregateType, sequence, type, syncStatus, occurredAt',
      outbox: 'id, eventId, aggregateId, status, nextRetryAt, createdAt',
      aggregate_versions: 'aggregateId, aggregateType, version',
      idempotency: 'key, createdAt',
      conflicts: 'id, aggregateId, status, detectedAt',

      customers: 'id, code, name, assignedTo, updatedAt',
      products: 'id, sku, name, updatedAt',
      stock_view: 'id, productId, warehouseId, updatedAt',
      orders: 'id, customerId, repId, status, updatedAt',
      order_lines: 'id, orderId, productId',
    });
  }
}

export const db = new AppDatabase();
