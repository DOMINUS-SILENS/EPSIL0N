export type RouteContract = {
  readonly manifest: string;
  readonly aggregate: string;
  readonly route: string;
  readonly roles: readonly string[];
  readonly states: readonly string[];
};

export type ComponentContract = {
  readonly manifest: string;
  readonly component: string;
  readonly aggregate: string;
  readonly states: readonly string[];
  readonly primitives: readonly string[];
  readonly roles: readonly string[];
};

export type ActionContract = {
  readonly manifest: string;
  readonly action: string;
  readonly aggregate: string;
  readonly command: string;
  readonly visibleIf: readonly string[];
  readonly enabledIf: readonly string[];
  readonly requiresSecondaryAuth: boolean;
  readonly requiresJustification: boolean;
  readonly truthOutcomes: Readonly<Record<string, string>>;
};

export function defineRouteContract(contract: RouteContract): RouteContract {
  return contract;
}

export function defineComponentContract(contract: ComponentContract): ComponentContract {
  return contract;
}

export function defineActionContract(contract: ActionContract): ActionContract {
  return contract;
}
