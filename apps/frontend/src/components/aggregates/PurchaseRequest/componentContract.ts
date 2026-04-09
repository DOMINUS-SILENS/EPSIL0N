import { defineComponentContract } from "../../../semanticContracts";

export const componentContract = defineComponentContract({ manifest: "packages/ui/manifests/components/purchase-request-record-card.json", component: "PurchaseRequestRecordCard", aggregate: "PurchaseRequest", states: ["loading", "ready", "pending", "accepted", "processing", "synced", "stale", "conflicted", "rejected", "failed", "archived"], primitives: ["RecordCard", "WorkflowHeader", "StateStrip", "ActionCluster", "AuditRail", "SyncBadge", "ConflictBanner"], roles: ["OperationsLead", "Approver"] });
