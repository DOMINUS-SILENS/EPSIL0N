import { defineComponentContract } from "../../../semanticContracts";

export const componentContract = defineComponentContract({ manifest: "packages/ui/manifests/components/audit-entry-record-card.json", component: "AuditEntryRecordCard", aggregate: "AuditEntry", states: ["loading", "ready", "pending", "accepted", "processing", "synced", "stale", "conflicted", "rejected", "failed", "archived"], primitives: ["RecordCard", "WorkflowHeader", "StateStrip", "AuditRail", "ConflictBanner", "SyncBadge"], roles: ["Auditor", "Admin", "FinanceManager"] });
