import { defineActionContract } from "../../../semanticContracts";

export const actionContract = defineActionContract({ manifest: "packages/ui/manifests/actions/trace-audit-entry.json", action: "TraceAuditEntry", aggregate: "AuditEntry", command: "TraceAuditEntryQuery", visibleIf: ["role:Auditor", "role:Admin", "role:FinanceManager"], enabledIf: ["audit_state:visible"], requiresSecondaryAuth: false, requiresJustification: false, truthOutcomes: { "200": "CommittedState", "403": "PermissionBlock", "409": "ConflictBanner", "422": "ExceptionDrawer" } });
