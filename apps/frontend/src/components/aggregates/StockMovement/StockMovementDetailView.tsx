import { ApproveStockMovementAction } from "../../../actions/inventory/ApproveStockMovement/ApproveStockMovementAction";
import type { StockMovementProjection } from "../../../contracts/projections";
import { componentContract } from "./componentContract";
import { renderAggregateCard } from "../../renderAggregateCard";

type StockMovementDetailViewProps = {
  readonly projection: StockMovementProjection;
};

const primitiveBindings = ["RecordCard", "WorkflowHeader", "StateStrip", "ActionCluster", "AuditRail", "SyncBadge", "ConflictBanner"] as const;

function resolveStockMovementStateBranch(viewState: StockMovementProjection["viewState"]): string {
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

export function StockMovementDetailView({ projection }: StockMovementDetailViewProps): Record<string, unknown> {
  const { movement_number, sku, quantity, source_location, target_location } = projection;
  return renderAggregateCard(componentContract, {
    projection,
    stateBranch: resolveStockMovementStateBranch(projection.viewState),
    identityFieldAcknowledgement: [movement_number, sku, quantity],
    criticalMetricAcknowledgement: [quantity, source_location, target_location],
    primitiveBindings,
    actionBindings: [ApproveStockMovementAction],
  });
}
