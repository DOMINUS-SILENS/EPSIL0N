export function ActionCluster(actions: readonly string[]): Record<string, unknown> {
  return { primitive: "ActionCluster", actions };
}
