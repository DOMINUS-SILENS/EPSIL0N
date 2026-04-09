import { defineComponentContract } from "../../../semanticContracts";

export const componentContract = defineComponentContract({ manifest: "packages/ui/manifests/components/invoice-record-card.json", component: "InvoiceRecordCard", aggregate: "Invoice", states: ["loading", "ready", "pending", "accepted", "processing", "synced", "stale", "conflicted", "rejected", "failed", "archived"], primitives: ["RecordCard", "WorkflowHeader", "StateStrip", "ActionCluster", "AuditRail", "SyncBadge", "ConflictBanner"], roles: ["FinanceManager", "Approver", "Auditor"] });
