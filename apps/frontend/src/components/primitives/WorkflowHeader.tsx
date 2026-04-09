export function WorkflowHeader(title: string, roles: readonly string[]): Record<string, unknown> {
  return { primitive: "WorkflowHeader", title, roles };
}
