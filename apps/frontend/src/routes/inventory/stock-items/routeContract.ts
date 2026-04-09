import { defineRouteContract } from "../../../semanticContracts";

export const routeContract = defineRouteContract({ manifest: "packages/ui/manifests/routes/stock-item-detail.json", aggregate: "StockItem", route: "/inventory/stock-items/:id", roles: ["WarehouseOperator", "OperationsLead"], states: ["ready", "pending", "accepted", "processing", "synced", "stale", "conflicted", "rejected", "failed", "archived"] });
