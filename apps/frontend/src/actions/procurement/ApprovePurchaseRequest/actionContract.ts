import { defineActionContract } from "../../../semanticContracts";

export const actionContract = defineActionContract({ manifest: "packages/ui/manifests/actions/approve-purchase-request.json", action: "ApprovePurchaseRequest", aggregate: "PurchaseRequest", command: "ApprovePurchaseRequestCommand", visibleIf: ["role:Approver", "role:OperationsLead"], enabledIf: ["approval_state:pending"], requiresSecondaryAuth: false, requiresJustification: false, truthOutcomes: { "200": "CommittedState", "202": "PendingState", "403": "PermissionBlock", "409": "ConflictBanner", "422": "ExceptionDrawer" } });
