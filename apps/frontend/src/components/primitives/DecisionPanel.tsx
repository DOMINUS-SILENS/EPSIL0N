export function DecisionPanel(outcomes: readonly string[]): Record<string, unknown> {
  return { primitive: "DecisionPanel", outcomes };
}
