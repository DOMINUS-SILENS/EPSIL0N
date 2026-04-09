import { TaskDetailView } from "../../../components/aggregates/Task/TaskDetailView";
import { createTaskProjection } from "../../../contracts/projections";
import { routeContract } from "./routeContract";
import { renderRouteSurface } from "../../renderRouteSurface";

export type TaskRouteParams = {
  readonly id: string;
};

export function bindTaskRouteParams(params: TaskRouteParams): TaskRouteParams {
  return params;
}

export function TaskDetailRoute(params: TaskRouteParams): Record<string, unknown> {
  const routeParams = bindTaskRouteParams(params);
  return renderRouteSurface(routeContract, TaskDetailView({ projection: createTaskProjection(routeParams.id) }));
}
