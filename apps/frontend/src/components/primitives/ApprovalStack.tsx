export function ApprovalStack(levels: readonly string[]): Record<string, unknown> {
  return { primitive: "ApprovalStack", levels };
}
