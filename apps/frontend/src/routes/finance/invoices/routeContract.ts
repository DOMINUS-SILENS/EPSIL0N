import { defineRouteContract } from "../../../semanticContracts";

export const routeContract = defineRouteContract({ manifest: "packages/ui/manifests/routes/invoice-detail.json", aggregate: "Invoice", route: "/finance/invoices/:id", roles: ["FinanceManager", "Approver", "Auditor"], states: ["ready", "pending", "accepted", "processing", "synced", "stale", "conflicted", "rejected", "failed", "archived"] });
