import type { RouteContract } from "../semanticContracts";

export function renderRouteSurface(
  contract: RouteContract,
  aggregateSurface: Record<string, unknown>,
): Record<string, unknown> {
  return {
    contract,
    aggregateSurface,
  };
}
