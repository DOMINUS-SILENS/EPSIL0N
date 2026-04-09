import { defineActionContract } from "../../../semanticContracts";

export const actionContract = defineActionContract({ manifest: "packages/ui/manifests/actions/release-order.json", action: "ReleaseOrder", aggregate: "Order", command: "ReleaseOrderCommand", visibleIf: ["role:OperationsLead"], enabledIf: ["lifecycle_state:approved"], requiresSecondaryAuth: false, requiresJustification: false, truthOutcomes: { "200": "CommittedState", "202": "PendingState", "403": "PermissionBlock", "409": "ConflictBanner", "422": "ExceptionDrawer" } });
