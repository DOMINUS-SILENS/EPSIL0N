import { defineRouteContract } from "../../../semanticContracts";

export const routeContract = defineRouteContract({ manifest: "packages/ui/manifests/routes/payment-detail.json", aggregate: "Payment", route: "/finance/payments/:id", roles: ["FinanceManager", "Approver", "Auditor"], states: ["ready", "pending", "accepted", "processing", "synced", "stale", "conflicted", "rejected", "failed", "archived"] });
