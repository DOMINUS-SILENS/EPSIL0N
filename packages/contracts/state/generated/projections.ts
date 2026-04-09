import type { CanonicalViewState } from "./state";

type ProjectionBase = {
  readonly id: string;
  readonly viewState: CanonicalViewState;
};

export type ApprovalRequestProjection = ProjectionBase & {
  readonly request_id: string;
  readonly request_type: string;
  readonly submitted_by: string;
  readonly approval_count: number;
  readonly deadline_at: string;
};

export type AuditEntryProjection = ProjectionBase & {
  readonly event_id: string;
  readonly aggregate_type: string;
  readonly actor_id: string;
  readonly occurred_at: string;
  readonly event_count: number;
};

export type CustomerProjection = ProjectionBase & {
  readonly customer_number: string;
  readonly display_name: string;
  readonly credit_status: string;
  readonly credit_limit: number;
  readonly outstanding_balance: number;
};

export type InvoiceProjection = ProjectionBase & {
  readonly invoice_number: string;
  readonly vendor_name: string;
  readonly total_amount: number;
  readonly due_date: string;
  readonly outstanding_balance: number;
};

export type NotificationExceptionProjection = ProjectionBase & {
  readonly exception_id: string;
  readonly source_context: string;
  readonly severity: string;
  readonly occurred_at: string;
  readonly retry_count: number;
};

export type OrderProjection = ProjectionBase & {
  readonly order_number: string;
  readonly customer_name: string;
  readonly order_total: number;
  readonly promised_date: string;
};

export type PaymentProjection = ProjectionBase & {
  readonly payment_reference: string;
  readonly counterparty_name: string;
  readonly amount: number;
  readonly value_date: string;
};

export type PurchaseRequestProjection = ProjectionBase & {
  readonly request_number: string;
  readonly requester_name: string;
  readonly estimated_total: number;
  readonly needed_by_date: string;
};

export type StockItemProjection = ProjectionBase & {
  readonly sku: string;
  readonly description: string;
  readonly site: string;
  readonly on_hand: number;
  readonly allocated: number;
  readonly available_to_promise: number;
};

export type StockMovementProjection = ProjectionBase & {
  readonly movement_number: string;
  readonly sku: string;
  readonly quantity: number;
  readonly source_location: string;
  readonly target_location: string;
};

export type TaskProjection = ProjectionBase & {
  readonly task_number: string;
  readonly assignee_name: string;
  readonly task_type: string;
  readonly due_at: string;
  readonly priority: string;
};

export type UserAccessGrantProjection = ProjectionBase & {
  readonly user_name: string;
  readonly role_name: string;
  readonly scope_name: string;
  readonly expires_at: string;
  readonly scope_count: number;
};
