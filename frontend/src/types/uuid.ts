declare module 'uuid' {
  export function v1(options?: unknown, buffer?: unknown, offset?: number): string;
  export function v3(name: string | unknown[], namespace: string | unknown[], buffer?: unknown, offset?: number): string;
  export function v4(options?: unknown, buffer?: unknown, offset?: number): string;
  export function v5(name: string | unknown[], namespace: string | unknown[], buffer?: unknown, offset?: number): string;
  export const NIL: string;
}
