import { defineRouteContract } from "../../../semanticContracts";

export const routeContract = defineRouteContract({ manifest: "packages/ui/manifests/routes/order-detail.json", aggregate: "Order", route: "/operations/orders/:id", roles: ["OperationsLead", "FieldAgent"], states: ["ready", "pending", "accepted", "processing", "synced", "stale", "conflicted", "rejected", "failed", "archived"] });
