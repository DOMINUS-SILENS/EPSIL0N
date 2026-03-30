import { ColumnDef } from '@tanstack/react-table'

export interface DataTableProps<TData> {
  data: TData[]
  columns: ColumnDef<TData, any>[]
  isLoading?: boolean
  enableSorting?: boolean
  enableFiltering?: boolean
  enablePagination?: boolean
  enableRowSelection?: boolean
  enableVirtualization?: boolean
  enableInlineEditing?: boolean
  pageSize?: number
  pageCount?: number
  onRowClick?: (row: { original: TData }) => void
  onCellEdit?: (rowId: string, columnId: string, value: any) => void
  onSave?: (changes: CellChange[]) => void
  meta?: any
}

export interface CellChange {
  rowId: string
  columnId: string
  oldValue: any
  newValue: any
  timestamp: number
}
