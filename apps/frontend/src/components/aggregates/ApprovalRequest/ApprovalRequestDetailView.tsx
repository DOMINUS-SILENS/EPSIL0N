import { ResolveApprovalRequestAction } from "../../../actions/procurement/ResolveApprovalRequest/ResolveApprovalRequestAction";
import type { ApprovalRequestProjection } from "../../../contracts/projections";
import { componentContract } from "./componentContract";
import { renderAggregateCard } from "../../renderAggregateCard";

type ApprovalRequestDetailViewProps = {
  readonly projection: ApprovalRequestProjection;
};

const primitiveBindings = ["RecordCard", "WorkflowHeader", "StateStrip", "ActionCluster", "AuditRail", "SyncBadge", "ConflictBanner", "ApprovalStack"] as const;

function resolveApprovalRequestStateBranch(viewState: ApprovalRequestProjection["viewState"]): string {
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

export function ApprovalRequestDetailView({ projection }: ApprovalRequestDetailViewProps): Record<string, unknown> {
  const { request_id, request_type, submitted_by, approval_count, deadline_at } = projection;
  return renderAggregateCard(componentContract, {
    projection,
    stateBranch: resolveApprovalRequestStateBranch(projection.viewState),
    identityFieldAcknowledgement: [request_id, request_type, submitted_by],
    criticalMetricAcknowledgement: [approval_count, deadline_at],
    primitiveBindings,
    actionBindings: [ResolveApprovalRequestAction],
  });
}
