import { OrderDetailView } from "../../../components/aggregates/Order/OrderDetailView";
import { createOrderProjection } from "../../../contracts/projections";
import { routeContract } from "./routeContract";
import { renderRouteSurface } from "../../renderRouteSurface";

export type OrderRouteParams = {
  readonly id: string;
};

export function bindOrderRouteParams(params: OrderRouteParams): OrderRouteParams {
  return params;
}

export function OrderDetailRoute(params: OrderRouteParams): Record<string, unknown> {
  const routeParams = bindOrderRouteParams(params);
  return renderRouteSurface(routeContract, OrderDetailView({ projection: createOrderProjection(routeParams.id) }));
}
