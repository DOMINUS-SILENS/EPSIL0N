import { ReleaseOrderAction } from "../../../actions/order-fulfillment/ReleaseOrder/ReleaseOrderAction";
import type { OrderProjection } from "../../../contracts/projections";
import { componentContract } from "./componentContract";
import { renderAggregateCard } from "../../renderAggregateCard";

type OrderDetailViewProps = {
  readonly projection: OrderProjection;
};

const primitiveBindings = ["RecordCard", "WorkflowHeader", "StateStrip", "ActionCluster", "AuditRail", "SyncBadge", "ConflictBanner"] as const;

function resolveOrderStateBranch(viewState: OrderProjection["viewState"]): string {
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

export function OrderDetailView({ projection }: OrderDetailViewProps): Record<string, unknown> {
  const { order_number, customer_name, order_total, promised_date } = projection;
  return renderAggregateCard(componentContract, {
    projection,
    stateBranch: resolveOrderStateBranch(projection.viewState),
    identityFieldAcknowledgement: [order_number, customer_name, order_total],
    criticalMetricAcknowledgement: [order_total, promised_date],
    primitiveBindings,
    actionBindings: [ReleaseOrderAction],
  });
}
