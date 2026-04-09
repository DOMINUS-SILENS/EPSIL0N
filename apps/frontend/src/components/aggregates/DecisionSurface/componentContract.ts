import { defineComponentContract } from "../../../semanticContracts";

export const componentContract = defineComponentContract({ manifest: "packages/ui/manifests/components/decision-panel.json", component: "DecisionPanel", aggregate: "DecisionSurface", states: ["loading", "ready", "pending", "accepted", "stale", "conflicted", "rejected", "failed", "unauthorized", "archived"], primitives: ["DecisionPanel", "StateStrip", "ActionCluster"], roles: ["FinanceManager", "Approver", "Admin"] });
