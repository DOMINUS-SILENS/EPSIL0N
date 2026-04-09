import { actionContract } from "./actionContract";
import { collectGateRequirements, dispatchActionCommand, renderActionBinding } from "../../renderActionBinding";

export function ResolveApprovalRequestAction(): Record<string, unknown> {
  return renderActionBinding(actionContract, {
    commandDispatch: dispatchActionCommand("ResolveApprovalRequestCommand"),
    truthOutcomeHandlers: actionContract.truthOutcomes,
    gateRequirements: collectGateRequirements(actionContract),
  });
}
