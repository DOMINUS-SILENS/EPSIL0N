export const canonicalViewStates = [
  "empty",
  "loading",
  "partial",
  "ready",
  "pending",
  "accepted",
  "processing",
  "synced",
  "stale",
  "conflicted",
  "rejected",
  "failed",
  "unauthorized",
  "archived"
] as const;

export type CanonicalViewState = typeof canonicalViewStates[number];

export const latencyTruthStates = [
  "locally_staged",
  "durably_queued",
  "server_processing",
  "server_committed",
  "server_rejected",
  "server_conflicted",
  "sync_not_yet_reconciled"
] as const;

export type LatencyTruth = typeof latencyTruthStates[number];

export const exceptionSeverities = [
  "informational",
  "recoverable",
  "blocking",
  "integrity_risk",
  "security_boundary_violation",
  "irreversible_action_warning"
] as const;

export type ExceptionSeverity = typeof exceptionSeverities[number];

export const permissionEffects = [
  "hidden",
  "disabled",
  "enabled"
] as const;

export type PermissionEffect = typeof permissionEffects[number];
