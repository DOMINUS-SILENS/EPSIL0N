import { ActionCluster } from "./primitives/ActionCluster";
import { ApprovalStack } from "./primitives/ApprovalStack";
import { AuditRail } from "./primitives/AuditRail";
import { ConflictBanner } from "./primitives/ConflictBanner";
import { DecisionPanel } from "./primitives/DecisionPanel";
import { ExceptionDrawer } from "./primitives/ExceptionDrawer";
import { RecordCard } from "./primitives/RecordCard";
import { StateStrip } from "./primitives/StateStrip";
import { SyncBadge } from "./primitives/SyncBadge";
import { WorkflowHeader } from "./primitives/WorkflowHeader";
import type { ComponentContract } from "../semanticContracts";

type AggregateRenderInput = {
  readonly projection: Record<string, unknown>;
  readonly stateBranch: string;
  readonly identityFieldAcknowledgement: readonly unknown[];
  readonly criticalMetricAcknowledgement: readonly unknown[];
  readonly primitiveBindings: readonly string[];
  readonly actionBindings: readonly unknown[];
};

export function renderAggregateCard(contract: ComponentContract, input: AggregateRenderInput): Record<string, unknown> {
  return RecordCard({
    contract,
    projection: input.projection,
    stateBranch: input.stateBranch,
    identityFieldAcknowledgement: input.identityFieldAcknowledgement,
    criticalMetricAcknowledgement: input.criticalMetricAcknowledgement,
    primitiveBindings: input.primitiveBindings,
    actionBindings: input.actionBindings,
    header: WorkflowHeader(contract.component, contract.roles),
    state: StateStrip(contract.states),
    sync: contract.primitives.includes("SyncBadge") ? SyncBadge("contract-bound") : null,
    conflict: contract.primitives.includes("ConflictBanner") ? ConflictBanner("truth divergence detected") : null,
    audit: contract.primitives.includes("AuditRail") ? AuditRail(contract.manifest) : null,
    actions: contract.primitives.includes("ActionCluster") ? ActionCluster(contract.primitives) : null,
    exception: contract.primitives.includes("ExceptionDrawer") ? ExceptionDrawer("recoverable") : null,
    approval: contract.primitives.includes("ApprovalStack") ? ApprovalStack(contract.roles) : null,
    decision: contract.primitives.includes("DecisionPanel") ? DecisionPanel(contract.states) : null,
  });
}
