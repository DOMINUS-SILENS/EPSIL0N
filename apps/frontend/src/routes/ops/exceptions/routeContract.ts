import { defineRouteContract } from "../../../semanticContracts";

export const routeContract = defineRouteContract({ manifest: "packages/ui/manifests/routes/notification-exception-detail.json", aggregate: "NotificationException", route: "/ops/exceptions/:id", roles: ["Support", "Admin", "FieldAgent"], states: ["ready", "pending", "accepted", "processing", "synced", "stale", "conflicted", "rejected", "failed", "archived"] });
