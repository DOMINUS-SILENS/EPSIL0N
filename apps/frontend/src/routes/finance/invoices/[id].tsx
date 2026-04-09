import { InvoiceDetailView } from "../../../components/aggregates/Invoice/InvoiceDetailView";
import { createInvoiceProjection } from "../../../contracts/projections";
import { routeContract } from "./routeContract";
import { renderRouteSurface } from "../../renderRouteSurface";

export type InvoiceRouteParams = {
  readonly id: string;
};

export function bindInvoiceRouteParams(params: InvoiceRouteParams): InvoiceRouteParams {
  return params;
}

export function InvoiceDetailRoute(params: InvoiceRouteParams): Record<string, unknown> {
  const routeParams = bindInvoiceRouteParams(params);
  return renderRouteSurface(routeContract, InvoiceDetailView({ projection: createInvoiceProjection(routeParams.id) }));
}
