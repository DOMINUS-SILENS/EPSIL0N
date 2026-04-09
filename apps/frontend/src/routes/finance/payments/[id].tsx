import { PaymentDetailView } from "../../../components/aggregates/Payment/PaymentDetailView";
import { createPaymentProjection } from "../../../contracts/projections";
import { routeContract } from "./routeContract";
import { renderRouteSurface } from "../../renderRouteSurface";

export type PaymentRouteParams = {
  readonly id: string;
};

export function bindPaymentRouteParams(params: PaymentRouteParams): PaymentRouteParams {
  return params;
}

export function PaymentDetailRoute(params: PaymentRouteParams): Record<string, unknown> {
  const routeParams = bindPaymentRouteParams(params);
  return renderRouteSurface(routeContract, PaymentDetailView({ projection: createPaymentProjection(routeParams.id) }));
}
