import { AssignTaskAction } from "../../../actions/order-fulfillment/AssignTask/AssignTaskAction";
import type { TaskProjection } from "../../../contracts/projections";
import { componentContract } from "./componentContract";
import { renderAggregateCard } from "../../renderAggregateCard";

type TaskDetailViewProps = {
  readonly projection: TaskProjection;
};

const primitiveBindings = ["RecordCard", "WorkflowHeader", "StateStrip", "ActionCluster", "AuditRail", "SyncBadge", "ConflictBanner"] as const;

function resolveTaskStateBranch(viewState: TaskProjection["viewState"]): string {
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

export function TaskDetailView({ projection }: TaskDetailViewProps): Record<string, unknown> {
  const { task_number, assignee_name, task_type, due_at, priority } = projection;
  return renderAggregateCard(componentContract, {
    projection,
    stateBranch: resolveTaskStateBranch(projection.viewState),
    identityFieldAcknowledgement: [task_number, assignee_name, task_type],
    criticalMetricAcknowledgement: [due_at, priority],
    primitiveBindings,
    actionBindings: [AssignTaskAction],
  });
}
