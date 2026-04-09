export function RecordCard(payload: Record<string, unknown>): Record<string, unknown> {
  return { primitive: "RecordCard", ...payload };
}
