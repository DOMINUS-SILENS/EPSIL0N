import { ApprovePurchaseRequestAction } from "../../../actions/procurement/ApprovePurchaseRequest/ApprovePurchaseRequestAction";
import type { PurchaseRequestProjection } from "../../../contracts/projections";
import { componentContract } from "./componentContract";
import { renderAggregateCard } from "../../renderAggregateCard";

type PurchaseRequestDetailViewProps = {
  readonly projection: PurchaseRequestProjection;
};

const primitiveBindings = ["RecordCard", "WorkflowHeader", "StateStrip", "ActionCluster", "AuditRail", "SyncBadge", "ConflictBanner"] as const;

function resolvePurchaseRequestStateBranch(viewState: PurchaseRequestProjection["viewState"]): string {
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

export function PurchaseRequestDetailView({ projection }: PurchaseRequestDetailViewProps): Record<string, unknown> {
  const { request_number, requester_name, estimated_total, needed_by_date } = projection;
  return renderAggregateCard(componentContract, {
    projection,
    stateBranch: resolvePurchaseRequestStateBranch(projection.viewState),
    identityFieldAcknowledgement: [request_number, requester_name, estimated_total],
    criticalMetricAcknowledgement: [estimated_total, needed_by_date],
    primitiveBindings,
    actionBindings: [ApprovePurchaseRequestAction],
  });
}
