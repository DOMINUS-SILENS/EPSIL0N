import { defineRouteContract } from "../../../semanticContracts";

export const routeContract = defineRouteContract({ manifest: "packages/ui/manifests/routes/task-detail.json", aggregate: "Task", route: "/operations/tasks/:id", roles: ["OperationsLead", "FieldAgent", "Support"], states: ["ready", "pending", "accepted", "processing", "synced", "stale", "conflicted", "rejected", "failed", "archived"] });
