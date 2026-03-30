
import '@tanstack/react-table'

declare module '@tanstack/react-table' {
  interface ColumnMeta<TData, TValue> {
    size?: number;
    onAction?: (action: string, id: any) => void;
  }
}
