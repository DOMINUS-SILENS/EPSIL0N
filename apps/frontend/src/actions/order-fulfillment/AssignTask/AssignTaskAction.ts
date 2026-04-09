import { actionContract } from "./actionContract";
import { collectGateRequirements, dispatchActionCommand, renderActionBinding } from "../../renderActionBinding";

export function AssignTaskAction(): Record<string, unknown> {
  return renderActionBinding(actionContract, {
    commandDispatch: dispatchActionCommand("AssignTaskCommand"),
    truthOutcomeHandlers: actionContract.truthOutcomes,
    gateRequirements: collectGateRequirements(actionContract),
  });
}
