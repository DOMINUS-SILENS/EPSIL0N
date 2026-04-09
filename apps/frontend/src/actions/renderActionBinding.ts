import type { ActionContract } from "../semanticContracts";

type ActionBindingInput = {
  readonly commandDispatch: string;
  readonly truthOutcomeHandlers: Readonly<Record<string, string>>;
  readonly gateRequirements: readonly string[];
};

export function dispatchActionCommand(command: string): string {
  return command;
}

export function collectGateRequirements(contract: ActionContract): readonly string[] {
  const gates: string[] = [];
  if (contract.requiresSecondaryAuth) {
    gates.push("secondary-auth-modal");
  }
  if (contract.requiresJustification) {
    gates.push("justification-input");
  }
  return gates;
}

export function renderActionBinding(contract: ActionContract, input: ActionBindingInput): Record<string, unknown> {
  return {
    contract,
    submission: input.commandDispatch,
    outcomes: input.truthOutcomeHandlers,
    gateRequirements: input.gateRequirements,
  };
}
