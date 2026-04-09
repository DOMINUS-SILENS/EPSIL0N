import { AuditEntryDetailView } from "../../../components/aggregates/AuditEntry/AuditEntryDetailView";
import { createAuditEntryProjection } from "../../../contracts/projections";
import { routeContract } from "./routeContract";
import { renderRouteSurface } from "../../renderRouteSurface";

export type AuditEntryRouteParams = {
  readonly id: string;
};

export function bindAuditEntryRouteParams(params: AuditEntryRouteParams): AuditEntryRouteParams {
  return params;
}

export function AuditEntryDetailRoute(params: AuditEntryRouteParams): Record<string, unknown> {
  const routeParams = bindAuditEntryRouteParams(params);
  return renderRouteSurface(routeContract, AuditEntryDetailView({ projection: createAuditEntryProjection(routeParams.id) }));
}
