import { defineActionContract } from "../../../semanticContracts";

export const actionContract = defineActionContract({ manifest: "packages/ui/manifests/actions/adjust-stock-item.json", action: "AdjustStockItem", aggregate: "StockItem", command: "AdjustStockItemCommand", visibleIf: ["role:WarehouseOperator", "role:OperationsLead"], enabledIf: ["availability_state:active"], requiresSecondaryAuth: true, requiresJustification: true, truthOutcomes: { "200": "CommittedState", "202": "PendingState", "403": "PermissionBlock", "409": "ConflictBanner", "422": "ExceptionDrawer" } });
