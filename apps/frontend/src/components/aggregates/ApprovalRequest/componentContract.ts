import { defineComponentContract } from "../../../semanticContracts";

export const componentContract = defineComponentContract({ manifest: "packages/ui/manifests/components/approval-request-record-card.json", component: "ApprovalRequestRecordCard", aggregate: "ApprovalRequest", states: ["loading", "ready", "pending", "accepted", "processing", "synced", "stale", "conflicted", "rejected", "failed", "archived"], primitives: ["RecordCard", "WorkflowHeader", "StateStrip", "ActionCluster", "AuditRail", "SyncBadge", "ConflictBanner", "ApprovalStack"], roles: ["Approver", "Admin", "Auditor"] });
