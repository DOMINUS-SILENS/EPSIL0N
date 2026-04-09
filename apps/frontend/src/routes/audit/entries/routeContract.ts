import { defineRouteContract } from "../../../semanticContracts";

export const routeContract = defineRouteContract({ manifest: "packages/ui/manifests/routes/audit-entry-detail.json", aggregate: "AuditEntry", route: "/audit/entries/:id", roles: ["Auditor", "Admin", "FinanceManager"], states: ["ready", "pending", "accepted", "processing", "synced", "stale", "conflicted", "rejected", "failed", "archived"] });
