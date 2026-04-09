import { defineRouteContract } from "../../../semanticContracts";

export const routeContract = defineRouteContract({ manifest: "packages/ui/manifests/routes/approval-request-detail.json", aggregate: "ApprovalRequest", route: "/workflow/approvals/:id", roles: ["Approver", "Admin", "Auditor"], states: ["ready", "pending", "accepted", "processing", "synced", "stale", "conflicted", "rejected", "failed", "archived"] });
