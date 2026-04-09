import { defineRouteContract } from "../../../semanticContracts";

export const routeContract = defineRouteContract({ manifest: "packages/ui/manifests/routes/user-access-grant-detail.json", aggregate: "UserAccessGrant", route: "/security/access-grants/:id", roles: ["Admin", "Auditor", "Support"], states: ["ready", "pending", "accepted", "processing", "synced", "stale", "conflicted", "rejected", "failed", "archived"] });
