import { defineActionContract } from "../../../semanticContracts";

export const actionContract = defineActionContract({ manifest: "packages/ui/manifests/actions/assign-task.json", action: "AssignTask", aggregate: "Task", command: "AssignTaskCommand", visibleIf: ["role:OperationsLead", "role:Support"], enabledIf: ["assignment_state:unassigned"], requiresSecondaryAuth: false, requiresJustification: false, truthOutcomes: { "200": "CommittedState", "202": "PendingState", "403": "PermissionBlock", "409": "ConflictBanner", "422": "ExceptionDrawer" } });
