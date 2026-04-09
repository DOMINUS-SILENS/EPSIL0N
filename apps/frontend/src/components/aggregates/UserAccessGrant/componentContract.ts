import { defineComponentContract } from "../../../semanticContracts";

export const componentContract = defineComponentContract({ manifest: "packages/ui/manifests/components/user-access-grant-record-card.json", component: "UserAccessGrantRecordCard", aggregate: "UserAccessGrant", states: ["loading", "ready", "pending", "accepted", "processing", "synced", "stale", "conflicted", "rejected", "failed", "archived"], primitives: ["RecordCard", "WorkflowHeader", "StateStrip", "ActionCluster", "AuditRail", "SyncBadge", "ConflictBanner", "DecisionPanel"], roles: ["Admin", "Auditor", "Support"] });
