import { AdjustStockItemAction } from "../../../actions/inventory/AdjustStockItem/AdjustStockItemAction";
import type { StockItemProjection } from "../../../contracts/projections";
import { componentContract } from "./componentContract";
import { renderAggregateCard } from "../../renderAggregateCard";

type StockItemDetailViewProps = {
  readonly projection: StockItemProjection;
};

const primitiveBindings = ["RecordCard", "WorkflowHeader", "StateStrip", "ActionCluster", "AuditRail", "SyncBadge", "ConflictBanner"] as const;

function resolveStockItemStateBranch(viewState: StockItemProjection["viewState"]): string {
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

export function StockItemDetailView({ projection }: StockItemDetailViewProps): Record<string, unknown> {
  const { sku, description, site, on_hand, allocated, available_to_promise } = projection;
  return renderAggregateCard(componentContract, {
    projection,
    stateBranch: resolveStockItemStateBranch(projection.viewState),
    identityFieldAcknowledgement: [sku, description, site],
    criticalMetricAcknowledgement: [on_hand, allocated, available_to_promise],
    primitiveBindings,
    actionBindings: [AdjustStockItemAction],
  });
}
