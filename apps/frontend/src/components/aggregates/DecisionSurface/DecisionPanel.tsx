import { componentContract } from "./componentContract";
import { renderAggregateCard } from "../../renderAggregateCard";

export function DecisionPanelSurface(): Record<string, unknown> {
  return renderAggregateCard(componentContract, {
    projection: { id: "decision-surface", viewState: "ready" },
    stateBranch: "ready",
    identityFieldAcknowledgement: ["decision-surface"],
    criticalMetricAcknowledgement: [],
    primitiveBindings: ["DecisionPanel", "StateStrip", "ActionCluster"],
    actionBindings: [],
  });
}
