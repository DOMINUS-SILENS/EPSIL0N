import { actionContract } from "./actionContract";
import { collectGateRequirements, dispatchActionCommand, renderActionBinding } from "../../renderActionBinding";

export function ApprovePurchaseRequestAction(): Record<string, unknown> {
  return renderActionBinding(actionContract, {
    commandDispatch: dispatchActionCommand("ApprovePurchaseRequestCommand"),
    truthOutcomeHandlers: actionContract.truthOutcomes,
    gateRequirements: collectGateRequirements(actionContract),
  });
}
