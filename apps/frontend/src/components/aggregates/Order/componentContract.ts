import { defineComponentContract } from "../../../semanticContracts";

export const componentContract = defineComponentContract({ manifest: "packages/ui/manifests/components/order-record-card.json", component: "OrderRecordCard", aggregate: "Order", states: ["loading", "ready", "pending", "accepted", "processing", "synced", "stale", "conflicted", "rejected", "failed", "archived"], primitives: ["RecordCard", "WorkflowHeader", "StateStrip", "ActionCluster", "AuditRail", "SyncBadge", "ConflictBanner"], roles: ["OperationsLead", "FieldAgent"] });
