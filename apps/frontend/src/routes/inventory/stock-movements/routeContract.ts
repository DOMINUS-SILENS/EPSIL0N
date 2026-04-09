import { defineRouteContract } from "../../../semanticContracts";

export const routeContract = defineRouteContract({ manifest: "packages/ui/manifests/routes/stock-movement-detail.json", aggregate: "StockMovement", route: "/inventory/stock-movements/:id", roles: ["WarehouseOperator", "OperationsLead", "Approver"], states: ["ready", "pending", "accepted", "processing", "synced", "stale", "conflicted", "rejected", "failed", "archived"] });
