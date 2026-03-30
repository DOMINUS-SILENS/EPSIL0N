import '@tanstack/react-table'

declare module '@tanstack/react-table' {
  interface TableMeta<_TData extends RowData> {
    updateData: (rowIndex: number, columnId: string, value: any) => void
  }
}
