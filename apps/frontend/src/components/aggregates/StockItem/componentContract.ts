import { defineComponentContract } from "../../../semanticContracts";

export const componentContract = defineComponentContract({ manifest: "packages/ui/manifests/components/stock-item-record-card.json", component: "StockItemRecordCard", aggregate: "StockItem", states: ["loading", "ready", "pending", "accepted", "processing", "synced", "stale", "conflicted", "rejected", "failed", "archived"], primitives: ["RecordCard", "WorkflowHeader", "StateStrip", "ActionCluster", "AuditRail", "SyncBadge", "ConflictBanner"], roles: ["WarehouseOperator", "OperationsLead"] });
