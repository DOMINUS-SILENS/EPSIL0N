import { StockMovementDetailView } from "../../../components/aggregates/StockMovement/StockMovementDetailView";
import { createStockMovementProjection } from "../../../contracts/projections";
import { routeContract } from "./routeContract";
import { renderRouteSurface } from "../../renderRouteSurface";

export type StockMovementRouteParams = {
  readonly id: string;
};

export function bindStockMovementRouteParams(params: StockMovementRouteParams): StockMovementRouteParams {
  return params;
}

export function StockMovementDetailRoute(params: StockMovementRouteParams): Record<string, unknown> {
  const routeParams = bindStockMovementRouteParams(params);
  return renderRouteSurface(routeContract, StockMovementDetailView({ projection: createStockMovementProjection(routeParams.id) }));
}
