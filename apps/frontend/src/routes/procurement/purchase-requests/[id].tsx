import { PurchaseRequestDetailView } from "../../../components/aggregates/PurchaseRequest/PurchaseRequestDetailView";
import { createPurchaseRequestProjection } from "../../../contracts/projections";
import { routeContract } from "./routeContract";
import { renderRouteSurface } from "../../renderRouteSurface";

export type PurchaseRequestRouteParams = {
  readonly id: string;
};

export function bindPurchaseRequestRouteParams(params: PurchaseRequestRouteParams): PurchaseRequestRouteParams {
  return params;
}

export function PurchaseRequestDetailRoute(params: PurchaseRequestRouteParams): Record<string, unknown> {
  const routeParams = bindPurchaseRequestRouteParams(params);
  return renderRouteSurface(routeContract, PurchaseRequestDetailView({ projection: createPurchaseRequestProjection(routeParams.id) }));
}
