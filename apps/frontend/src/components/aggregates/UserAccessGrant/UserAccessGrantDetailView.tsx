import { ApproveUserAccessGrantAction } from "../../../actions/organization/ApproveUserAccessGrant/ApproveUserAccessGrantAction";
import type { UserAccessGrantProjection } from "../../../contracts/projections";
import { componentContract } from "./componentContract";
import { renderAggregateCard } from "../../renderAggregateCard";

type UserAccessGrantDetailViewProps = {
  readonly projection: UserAccessGrantProjection;
};

const primitiveBindings = ["RecordCard", "WorkflowHeader", "StateStrip", "ActionCluster", "AuditRail", "SyncBadge", "ConflictBanner", "DecisionPanel"] as const;

function resolveUserAccessGrantStateBranch(viewState: UserAccessGrantProjection["viewState"]): string {
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

export function UserAccessGrantDetailView({ projection }: UserAccessGrantDetailViewProps): Record<string, unknown> {
  const { user_name, role_name, scope_name, expires_at, scope_count } = projection;
  return renderAggregateCard(componentContract, {
    projection,
    stateBranch: resolveUserAccessGrantStateBranch(projection.viewState),
    identityFieldAcknowledgement: [user_name, role_name, scope_name],
    criticalMetricAcknowledgement: [expires_at, scope_count],
    primitiveBindings,
    actionBindings: [ApproveUserAccessGrantAction],
  });
}
