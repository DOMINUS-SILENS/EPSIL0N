import { defineRouteContract } from "../../../semanticContracts";

export const routeContract = defineRouteContract({ manifest: "packages/ui/manifests/routes/customer-detail.json", aggregate: "Customer", route: "/crm/customers/:id", roles: ["FinanceManager", "Support", "Auditor"], states: ["ready", "pending", "accepted", "processing", "synced", "stale", "conflicted", "rejected", "failed", "archived"] });
