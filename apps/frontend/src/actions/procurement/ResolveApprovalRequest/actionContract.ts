import { defineActionContract } from "../../../semanticContracts";

export const actionContract = defineActionContract({ manifest: "packages/ui/manifests/actions/resolve-approval-request.json", action: "ResolveApprovalRequest", aggregate: "ApprovalRequest", command: "ResolveApprovalRequestCommand", visibleIf: ["role:Approver", "role:Admin"], enabledIf: ["approval_state:pending"], requiresSecondaryAuth: false, requiresJustification: true, truthOutcomes: { "200": "CommittedState", "202": "PendingState", "403": "PermissionBlock", "409": "ConflictBanner", "422": "ExceptionDrawer" } });
