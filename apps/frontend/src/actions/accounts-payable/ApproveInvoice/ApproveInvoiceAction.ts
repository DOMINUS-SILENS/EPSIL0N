import { actionContract } from "./actionContract";
import { collectGateRequirements, dispatchActionCommand, renderActionBinding } from "../../renderActionBinding";

export function ApproveInvoiceAction(): Record<string, unknown> {
  return renderActionBinding(actionContract, {
    commandDispatch: dispatchActionCommand("ApproveInvoiceCommand"),
    truthOutcomeHandlers: actionContract.truthOutcomes,
    gateRequirements: collectGateRequirements(actionContract),
  });
}
