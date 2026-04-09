import { defineRouteContract } from "../../../semanticContracts";

export const routeContract = defineRouteContract({ manifest: "packages/ui/manifests/routes/purchase-request-detail.json", aggregate: "PurchaseRequest", route: "/procurement/purchase-requests/:id", roles: ["OperationsLead", "Approver"], states: ["ready", "pending", "accepted", "processing", "synced", "stale", "conflicted", "rejected", "failed", "archived"] });
