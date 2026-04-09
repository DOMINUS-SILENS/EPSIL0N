import { EscalateNotificationExceptionAction } from "../../../actions/reporting/EscalateNotificationException/EscalateNotificationExceptionAction";
import type { NotificationExceptionProjection } from "../../../contracts/projections";
import { componentContract } from "./componentContract";
import { renderAggregateCard } from "../../renderAggregateCard";

type NotificationExceptionDetailViewProps = {
  readonly projection: NotificationExceptionProjection;
};

const primitiveBindings = ["RecordCard", "WorkflowHeader", "StateStrip", "ActionCluster", "AuditRail", "ExceptionDrawer", "ConflictBanner", "SyncBadge"] as const;

function resolveNotificationExceptionStateBranch(viewState: NotificationExceptionProjection["viewState"]): string {
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

export function NotificationExceptionDetailView({ projection }: NotificationExceptionDetailViewProps): Record<string, unknown> {
  const { exception_id, source_context, severity, occurred_at, retry_count } = projection;
  return renderAggregateCard(componentContract, {
    projection,
    stateBranch: resolveNotificationExceptionStateBranch(projection.viewState),
    identityFieldAcknowledgement: [exception_id, source_context, severity],
    criticalMetricAcknowledgement: [occurred_at, retry_count],
    primitiveBindings,
    actionBindings: [EscalateNotificationExceptionAction],
  });
}
