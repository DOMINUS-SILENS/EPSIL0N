import { ReconcilePaymentAction } from "../../../actions/accounts-payable/ReconcilePayment/ReconcilePaymentAction";
import type { PaymentProjection } from "../../../contracts/projections";
import { componentContract } from "./componentContract";
import { renderAggregateCard } from "../../renderAggregateCard";

type PaymentDetailViewProps = {
  readonly projection: PaymentProjection;
};

const primitiveBindings = ["RecordCard", "WorkflowHeader", "StateStrip", "ActionCluster", "AuditRail", "SyncBadge", "ConflictBanner"] as const;

function resolvePaymentStateBranch(viewState: PaymentProjection["viewState"]): string {
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

export function PaymentDetailView({ projection }: PaymentDetailViewProps): Record<string, unknown> {
  const { payment_reference, counterparty_name, amount, value_date } = projection;
  return renderAggregateCard(componentContract, {
    projection,
    stateBranch: resolvePaymentStateBranch(projection.viewState),
    identityFieldAcknowledgement: [payment_reference, counterparty_name, amount],
    criticalMetricAcknowledgement: [amount, value_date],
    primitiveBindings,
    actionBindings: [ReconcilePaymentAction],
  });
}
