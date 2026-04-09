import { defineActionContract } from "../../../semanticContracts";

export const actionContract = defineActionContract({ manifest: "packages/ui/manifests/actions/approve-user-access-grant.json", action: "ApproveUserAccessGrant", aggregate: "UserAccessGrant", command: "ApproveUserAccessGrantCommand", visibleIf: ["role:Admin"], enabledIf: ["grant_state:pending"], requiresSecondaryAuth: true, requiresJustification: true, truthOutcomes: { "200": "CommittedState", "202": "PendingState", "403": "PermissionBlock", "409": "ConflictBanner", "422": "ExceptionDrawer" } });
