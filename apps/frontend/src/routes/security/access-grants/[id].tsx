import { UserAccessGrantDetailView } from "../../../components/aggregates/UserAccessGrant/UserAccessGrantDetailView";
import { createUserAccessGrantProjection } from "../../../contracts/projections";
import { routeContract } from "./routeContract";
import { renderRouteSurface } from "../../renderRouteSurface";

export type UserAccessGrantRouteParams = {
  readonly id: string;
};

export function bindUserAccessGrantRouteParams(params: UserAccessGrantRouteParams): UserAccessGrantRouteParams {
  return params;
}

export function UserAccessGrantDetailRoute(params: UserAccessGrantRouteParams): Record<string, unknown> {
  const routeParams = bindUserAccessGrantRouteParams(params);
  return renderRouteSurface(routeContract, UserAccessGrantDetailView({ projection: createUserAccessGrantProjection(routeParams.id) }));
}
