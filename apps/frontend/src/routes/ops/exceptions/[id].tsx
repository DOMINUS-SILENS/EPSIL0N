import { NotificationExceptionDetailView } from "../../../components/aggregates/NotificationException/NotificationExceptionDetailView";
import { createNotificationExceptionProjection } from "../../../contracts/projections";
import { routeContract } from "./routeContract";
import { renderRouteSurface } from "../../renderRouteSurface";

export type NotificationExceptionRouteParams = {
  readonly id: string;
};

export function bindNotificationExceptionRouteParams(params: NotificationExceptionRouteParams): NotificationExceptionRouteParams {
  return params;
}

export function NotificationExceptionDetailRoute(params: NotificationExceptionRouteParams): Record<string, unknown> {
  const routeParams = bindNotificationExceptionRouteParams(params);
  return renderRouteSurface(routeContract, NotificationExceptionDetailView({ projection: createNotificationExceptionProjection(routeParams.id) }));
}
