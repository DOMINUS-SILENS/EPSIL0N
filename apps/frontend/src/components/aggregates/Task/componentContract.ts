import { defineComponentContract } from "../../../semanticContracts";

export const componentContract = defineComponentContract({ manifest: "packages/ui/manifests/components/task-record-card.json", component: "TaskRecordCard", aggregate: "Task", states: ["loading", "ready", "pending", "accepted", "processing", "synced", "stale", "conflicted", "rejected", "failed", "archived"], primitives: ["RecordCard", "WorkflowHeader", "StateStrip", "ActionCluster", "AuditRail", "SyncBadge", "ConflictBanner"], roles: ["OperationsLead", "FieldAgent", "Support"] });
