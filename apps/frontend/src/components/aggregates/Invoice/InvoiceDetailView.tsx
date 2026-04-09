import { ApproveInvoiceAction } from "../../../actions/accounts-payable/ApproveInvoice/ApproveInvoiceAction";
import type { InvoiceProjection } from "../../../contracts/projections";
import { componentContract } from "./componentContract";
import { renderAggregateCard } from "../../renderAggregateCard";

type InvoiceDetailViewProps = {
  readonly projection: InvoiceProjection;
};

const primitiveBindings = ["RecordCard", "WorkflowHeader", "StateStrip", "ActionCluster", "AuditRail", "SyncBadge", "ConflictBanner"] as const;

function resolveInvoiceStateBranch(viewState: InvoiceProjection["viewState"]): string {
  switch (viewState) {
    case "loading":
    case "ready":
    case "pending":
    case "accepted":
    case "processing":
    case "synced":
    case "stale":
    case "conflicted":
    case "rejected":
    case "failed":
    case "archived":
      return viewState;
    default:
      return "archived";
  }
}

export function InvoiceDetailView({ projection }: InvoiceDetailViewProps): Record<string, unknown> {
  const { invoice_number, vendor_name, total_amount, due_date, outstanding_balance } = projection;
  return renderAggregateCard(componentContract, {
    projection,
    stateBranch: resolveInvoiceStateBranch(projection.viewState),
    identityFieldAcknowledgement: [invoice_number, vendor_name, total_amount],
    criticalMetricAcknowledgement: [total_amount, due_date, outstanding_balance],
    primitiveBindings,
    actionBindings: [ApproveInvoiceAction],
  });
}
