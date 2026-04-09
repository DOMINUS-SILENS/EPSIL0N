import { ApprovalRequestDetailView } from "../../../components/aggregates/ApprovalRequest/ApprovalRequestDetailView";
import { createApprovalRequestProjection } from "../../../contracts/projections";
import { routeContract } from "./routeContract";
import { renderRouteSurface } from "../../renderRouteSurface";

export type ApprovalRequestRouteParams = {
  readonly id: string;
};

export function bindApprovalRequestRouteParams(params: ApprovalRequestRouteParams): ApprovalRequestRouteParams {
  return params;
}

export function ApprovalRequestDetailRoute(params: ApprovalRequestRouteParams): Record<string, unknown> {
  const routeParams = bindApprovalRequestRouteParams(params);
  return renderRouteSurface(routeContract, ApprovalRequestDetailView({ projection: createApprovalRequestProjection(routeParams.id) }));
}
