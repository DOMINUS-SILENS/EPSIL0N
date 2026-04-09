import { actionContract } from "./actionContract";
import { collectGateRequirements, dispatchActionCommand, renderActionBinding } from "../../renderActionBinding";

export function UpdateCustomerCreditAction(): Record<string, unknown> {
  return renderActionBinding(actionContract, {
    commandDispatch: dispatchActionCommand("UpdateCustomerCreditCommand"),
    truthOutcomeHandlers: actionContract.truthOutcomes,
    gateRequirements: collectGateRequirements(actionContract),
  });
}
