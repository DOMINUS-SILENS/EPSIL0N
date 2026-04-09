import { defineActionContract } from "../../../semanticContracts";

export const actionContract = defineActionContract({ manifest: "packages/ui/manifests/actions/reconcile-payment.json", action: "ReconcilePayment", aggregate: "Payment", command: "ReconcilePaymentCommand", visibleIf: ["role:FinanceManager", "role:Approver"], enabledIf: ["reconciliation_state:unreconciled"], requiresSecondaryAuth: false, requiresJustification: false, truthOutcomes: { "200": "CommittedState", "202": "PendingState", "403": "PermissionBlock", "409": "ConflictBanner", "422": "ExceptionDrawer" } });
