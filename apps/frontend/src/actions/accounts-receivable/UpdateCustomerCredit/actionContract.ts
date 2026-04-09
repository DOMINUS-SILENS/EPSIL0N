import { defineActionContract } from "../../../semanticContracts";

export const actionContract = defineActionContract({ manifest: "packages/ui/manifests/actions/update-customer-credit.json", action: "UpdateCustomerCredit", aggregate: "Customer", command: "UpdateCustomerCreditCommand", visibleIf: ["role:FinanceManager", "role:Support"], enabledIf: ["lifecycle_state:active"], requiresSecondaryAuth: true, requiresJustification: true, truthOutcomes: { "200": "CommittedState", "202": "PendingState", "403": "PermissionBlock", "409": "ConflictBanner", "422": "ExceptionDrawer" } });
