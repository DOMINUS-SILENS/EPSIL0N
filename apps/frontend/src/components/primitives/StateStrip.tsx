export function StateStrip(states: readonly string[]): Record<string, unknown> {
  return { primitive: "StateStrip", states };
}
