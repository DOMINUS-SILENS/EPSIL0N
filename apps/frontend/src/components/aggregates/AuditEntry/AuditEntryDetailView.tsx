import { TraceAuditEntryAction } from "../../../actions/reporting/TraceAuditEntry/TraceAuditEntryAction";
import type { AuditEntryProjection } from "../../../contracts/projections";
import { componentContract } from "./componentContract";
import { renderAggregateCard } from "../../renderAggregateCard";

type AuditEntryDetailViewProps = {
  readonly projection: AuditEntryProjection;
};

const primitiveBindings = ["RecordCard", "WorkflowHeader", "StateStrip", "AuditRail", "ConflictBanner", "SyncBadge"] as const;

function resolveAuditEntryStateBranch(viewState: AuditEntryProjection["viewState"]): string {
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

export function AuditEntryDetailView({ projection }: AuditEntryDetailViewProps): Record<string, unknown> {
  const { event_id, aggregate_type, actor_id, occurred_at, event_count } = projection;
  return renderAggregateCard(componentContract, {
    projection,
    stateBranch: resolveAuditEntryStateBranch(projection.viewState),
    identityFieldAcknowledgement: [event_id, aggregate_type, actor_id],
    criticalMetricAcknowledgement: [occurred_at, event_count],
    primitiveBindings,
    actionBindings: [TraceAuditEntryAction],
  });
}
