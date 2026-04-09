import { UpdateCustomerCreditAction } from "../../../actions/accounts-receivable/UpdateCustomerCredit/UpdateCustomerCreditAction";
import type { CustomerProjection } from "../../../contracts/projections";
import { componentContract } from "./componentContract";
import { renderAggregateCard } from "../../renderAggregateCard";

type CustomerDetailViewProps = {
  readonly projection: CustomerProjection;
};

const primitiveBindings = ["RecordCard", "WorkflowHeader", "StateStrip", "ActionCluster", "AuditRail", "SyncBadge", "ConflictBanner"] as const;

function resolveCustomerStateBranch(viewState: CustomerProjection["viewState"]): string {
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

export function CustomerDetailView({ projection }: CustomerDetailViewProps): Record<string, unknown> {
  const { customer_number, display_name, credit_status, credit_limit, outstanding_balance } = projection;
  return renderAggregateCard(componentContract, {
    projection,
    stateBranch: resolveCustomerStateBranch(projection.viewState),
    identityFieldAcknowledgement: [customer_number, display_name, credit_status],
    criticalMetricAcknowledgement: [credit_limit, outstanding_balance],
    primitiveBindings,
    actionBindings: [UpdateCustomerCreditAction],
  });
}
