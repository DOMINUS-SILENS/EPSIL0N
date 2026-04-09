import { CustomerDetailView } from "../../../components/aggregates/Customer/CustomerDetailView";
import { createCustomerProjection } from "../../../contracts/projections";
import { routeContract } from "./routeContract";
import { renderRouteSurface } from "../../renderRouteSurface";

export type CustomerRouteParams = {
  readonly id: string;
};

export function bindCustomerRouteParams(params: CustomerRouteParams): CustomerRouteParams {
  return params;
}

export function CustomerDetailRoute(params: CustomerRouteParams): Record<string, unknown> {
  const routeParams = bindCustomerRouteParams(params);
  return renderRouteSurface(routeContract, CustomerDetailView({ projection: createCustomerProjection(routeParams.id) }));
}
