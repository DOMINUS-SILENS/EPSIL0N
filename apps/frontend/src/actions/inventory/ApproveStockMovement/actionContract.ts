import { defineActionContract } from "../../../semanticContracts";

export const actionContract = defineActionContract({ manifest: "packages/ui/manifests/actions/approve-stock-movement.json", action: "ApproveStockMovement", aggregate: "StockMovement", command: "ApproveStockMovementCommand", visibleIf: ["role:OperationsLead", "role:Approver"], enabledIf: ["approval_state:pending"], requiresSecondaryAuth: false, requiresJustification: false, truthOutcomes: { "200": "CommittedState", "202": "PendingState", "403": "PermissionBlock", "409": "ConflictBanner", "422": "ExceptionDrawer" } });
