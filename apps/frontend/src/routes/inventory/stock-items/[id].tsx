import { StockItemDetailView } from "../../../components/aggregates/StockItem/StockItemDetailView";
import { createStockItemProjection } from "../../../contracts/projections";
import { routeContract } from "./routeContract";
import { renderRouteSurface } from "../../renderRouteSurface";

export type StockItemRouteParams = {
  readonly id: string;
};

export function bindStockItemRouteParams(params: StockItemRouteParams): StockItemRouteParams {
  return params;
}

export function StockItemDetailRoute(params: StockItemRouteParams): Record<string, unknown> {
  const routeParams = bindStockItemRouteParams(params);
  return renderRouteSurface(routeContract, StockItemDetailView({ projection: createStockItemProjection(routeParams.id) }));
}
