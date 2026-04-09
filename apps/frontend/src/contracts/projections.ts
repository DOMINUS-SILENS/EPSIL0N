export type {
  ApprovalRequestProjection,
  AuditEntryProjection,
  CustomerProjection,
  InvoiceProjection,
  NotificationExceptionProjection,
  OrderProjection,
  PaymentProjection,
  PurchaseRequestProjection,
  StockItemProjection,
  StockMovementProjection,
  TaskProjection,
  UserAccessGrantProjection,
} from "../../../../packages/contracts/state/generated/projections";

import type {
  ApprovalRequestProjection,
  AuditEntryProjection,
  CustomerProjection,
  InvoiceProjection,
  NotificationExceptionProjection,
  OrderProjection,
  PaymentProjection,
  PurchaseRequestProjection,
  StockItemProjection,
  StockMovementProjection,
  TaskProjection,
  UserAccessGrantProjection,
} from "../../../../packages/contracts/state/generated/projections";

export function createApprovalRequestProjection(id: string): ApprovalRequestProjection {
  return { id, viewState: "ready", request_id: id, request_type: "approval", submitted_by: "system", approval_count: 1, deadline_at: "2026-04-09" };
}

export function createAuditEntryProjection(id: string): AuditEntryProjection {
  return { id, viewState: "ready", event_id: id, aggregate_type: "AuditEntry", actor_id: "system", occurred_at: "2026-04-09T00:00:00Z", event_count: 1 };
}

export function createCustomerProjection(id: string): CustomerProjection {
  return { id, viewState: "ready", customer_number: id, display_name: "Customer", credit_status: "active", credit_limit: 1000, outstanding_balance: 100 };
}

export function createInvoiceProjection(id: string): InvoiceProjection {
  return { id, viewState: "ready", invoice_number: id, vendor_name: "Vendor", total_amount: 1000, due_date: "2026-04-09", outstanding_balance: 250 };
}

export function createNotificationExceptionProjection(id: string): NotificationExceptionProjection {
  return { id, viewState: "ready", exception_id: id, source_context: "reporting", severity: "blocking", occurred_at: "2026-04-09T00:00:00Z", retry_count: 1 };
}

export function createOrderProjection(id: string): OrderProjection {
  return { id, viewState: "ready", order_number: id, customer_name: "Customer", order_total: 1000, promised_date: "2026-04-09" };
}

export function createPaymentProjection(id: string): PaymentProjection {
  return { id, viewState: "ready", payment_reference: id, counterparty_name: "Vendor", amount: 500, value_date: "2026-04-09" };
}

export function createPurchaseRequestProjection(id: string): PurchaseRequestProjection {
  return { id, viewState: "ready", request_number: id, requester_name: "Requester", estimated_total: 750, needed_by_date: "2026-04-09" };
}

export function createStockItemProjection(id: string): StockItemProjection {
  return { id, viewState: "ready", sku: id, description: "Stock Item", site: "Main", on_hand: 10, allocated: 2, available_to_promise: 8 };
}

export function createStockMovementProjection(id: string): StockMovementProjection {
  return { id, viewState: "ready", movement_number: id, sku: "SKU", quantity: 5, source_location: "A1", target_location: "B1" };
}

export function createTaskProjection(id: string): TaskProjection {
  return { id, viewState: "ready", task_number: id, assignee_name: "Agent", task_type: "FollowUp", due_at: "2026-04-09T00:00:00Z", priority: "high" };
}

export function createUserAccessGrantProjection(id: string): UserAccessGrantProjection {
  return { id, viewState: "ready", user_name: "User", role_name: "Admin", scope_name: "Global", expires_at: "2026-04-09T00:00:00Z", scope_count: 1 };
}
