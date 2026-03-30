import { Button } from '../../primitives/Button/Button'
import { Input } from '@/design-system/primitives/Input/Input'
import { cn } from '@/lib/utils'
import {
  flexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable,
} from '@tanstack/react-table'
import { useVirtualizer } from '@tanstack/react-virtual'
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight, Loader2 } from 'lucide-react'

import * as React from 'react'
import { DataTableProps } from './types'
import { useUndoRedo } from './useUndoRedo'

export function DataTable<TData>({
  data,
  columns,
  isLoading = false,
  enableSorting = true,
  enableFiltering = true,
  enablePagination = true,
  enableVirtualization = false,
  enableInlineEditing = true,
  pageCount,
  onRowClick,
  onCellEdit,
  onSave,
  meta,
}: DataTableProps<TData>) {
  const [sorting, setSorting] = React.useState([])
  const [columnFilters, setColumnFilters] = React.useState([])
  const [rowSelection, setRowSelection] = React.useState({})
  const [editingCell, setEditingCell] = React.useState<{ rowId: string; columnId: string } | null>(null)

  const { undo, redo, push, canUndo, canRedo } = useUndoRedo({
    maxHistory: 50,
    onSave,
  })
/* @__NO_MEMO__ */
// eslint-disable-next-line react-hooks/incompatible-library
  const table = useReactTable<TData>({
    data,
    columns,
    state: {
      sorting,
      columnFilters,
      rowSelection,
    } as any,
    onSortingChange: setSorting as any,
    onColumnFiltersChange: setColumnFilters as any,
    onRowSelectionChange: setRowSelection,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: enableSorting ? getSortedRowModel() : undefined,
    getFilteredRowModel: enableFiltering ? getFilteredRowModel() : undefined,
    getPaginationRowModel: enablePagination ? getPaginationRowModel() : undefined,
    pageCount: pageCount,
    manualPagination: !!pageCount,
    meta: meta,
  }) as any

  const { rows } = table.getRowModel()
  const containerRef = React.useRef<HTMLDivElement>(null)

  const virtualizer = useVirtualizer({
    count: rows.length,
    getScrollElement: () => containerRef.current,
    estimateSize: () => 48,
    overscan: 10,
  })

  const handleCellEdit = (rowId: string, columnId: string, newValue: unknown) => {
    const row = data.find((_, idx) => idx.toString() === rowId)
    if (!row) return

    const oldValue = (row as any)[columnId]
    
    push({
      rowId,
      columnId,
      oldValue,
      newValue,
      timestamp: Date.now(),
    })

    if (onCellEdit) {
      onCellEdit(rowId, columnId, newValue)
    }

    setEditingCell(null)
  }

  const handleKeyDown = (e: React.KeyboardEvent, rowId: string, columnId: string, value: string | number) => {
    if (e.key === 'Enter') {
      handleCellEdit(rowId, columnId, value)
    } else if (e.key === 'Escape') {
      setEditingCell(null)
    }
  }

  return (
    <div className="relative">
      {/* Toolbar */}
      <div className="mb-4 flex items-center justify-between">
        <div className="flex items-center gap-2">
          {enableFiltering && (
            <Input
              placeholder="Filter..."
              value={(table.getColumn('name')?.getFilterValue() as string) ?? ''}
              onChange={(e) => (table.getColumn('name') as any)?.setFilterValue(e.target.value)}
              className="max-w-sm"
            />
          )}
        </div>
        <div className="flex items-center gap-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => undo()}
            disabled={!canUndo}
          >
            Undo
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={() => redo()}
            disabled={!canRedo}
          >
            Redo
          </Button>
        </div>
      </div>

      {/* Table Container */}
      <div
        ref={containerRef}
        className={cn(
          'relative border rounded-md overflow-auto',
          enableVirtualization && 'h-[600px]'
        )}
      >
        <table className="w-full caption-bottom text-sm">
          <thead className="sticky top-0 z-10 bg-neutral-50 dark:bg-neutral-900 border-b">
            {table.getHeaderGroups().map((headerGroup: any) => (
              <tr key={headerGroup.id}>
                {headerGroup.headers.map((header: any) => (
                  <th
                    key={header.id}
                    className={cn(
                      'h-12 px-4 text-left align-middle font-medium text-neutral-500',
                      enableSorting && header.column.getCanSort() && 'cursor-pointer select-none'
                    )}
                    onClick={header.column.getToggleSortingHandler()}
                  >
                    {flexRender(header.column.columnDef.header, header.getContext())}
                    {{
                      asc: ' ↑',
                      desc: ' ↓',
                    }[header.column.getIsSorted() as string] ?? null}
                  </th>
                ))}
               </tr>
            ))}
          </thead>
          <tbody>
            {isLoading ? (
              <tr>
                <td colSpan={columns.length} className="h-24 text-center">
                  <Loader2 className="h-6 w-6 animate-spin mx-auto" />
                </td>
              </tr>
            ) : enableVirtualization ? (
              virtualizer.getVirtualItems().map((virtualRow: any) => {
                const row = rows[virtualRow.index]
                return (
                  <tr
                    key={row.id}
                    className={cn(
                      'border-b transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900',
                      onRowClick && 'cursor-pointer'
                    )}
                    onClick={() => onRowClick?.(row)}
                  >
                    {row.getVisibleCells().map((cell: any) => (
                      <td
                        key={cell.id}
                        className="p-4 align-middle"
                        onDoubleClick={() => enableInlineEditing && setEditingCell({ rowId: row.id, columnId: cell.column.id })}
                      >
                        {editingCell?.rowId === row.id && editingCell?.columnId === cell.column.id ? (
                          <Input
                            defaultValue={cell.getValue() as string}
                            onBlur={(e) => handleCellEdit(row.id, cell.column.id, e.target.value)}
                            onKeyDown={(e) => handleKeyDown(e, row.id, cell.column.id, (e.target as HTMLInputElement).value)}
                            autoFocus
                          />
                        ) : (
                          flexRender(cell.column.columnDef.cell, cell.getContext())
                        )}
                      </td>
                    ))}
                  </tr>
                )
              })
            ) : (
              rows.map((row: any) => (
                <tr
                  key={row.id}
                  className={cn(
                    'border-b transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900',
                    onRowClick && 'cursor-pointer'
                  )}
                  onClick={() => onRowClick?.(row)}
                >
                  {row.getVisibleCells().map((cell: any) => (
                    <td
                      key={cell.id}
                      className="p-4 align-middle"
                      onDoubleClick={() => enableInlineEditing && setEditingCell({ rowId: row.id, columnId: cell.column.id })}
                    >
                      {editingCell?.rowId === row.id && editingCell?.columnId === cell.column.id ? (
                        <Input
                          defaultValue={cell.getValue() as string}
                          onBlur={(e) => handleCellEdit(row.id, cell.column.id, e.target.value)}
                          onKeyDown={(e) => handleKeyDown(e, row.id, cell.column.id, (e.target as HTMLInputElement).value)}
                          autoFocus
                        />
                      ) : (
                        flexRender(cell.column.columnDef.cell, cell.getContext())
                      )}
                    </td>
                  ))}
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Pagination */}
      {enablePagination && (
        <div className="flex items-center justify-between px-2 py-4">
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              onClick={() => table.setPageIndex(0)}
              disabled={!table.getCanPreviousPage()}
            >
              <ChevronsLeft className="h-4 w-4" />
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={() => table.previousPage()}
              disabled={!table.getCanPreviousPage()}
            >
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <span className="text-sm">
              Page {(table.getState() as any).pagination?.pageIndex + 1} of {table.getPageCount()}
            </span>
            <Button
              variant="outline"
              size="sm"
              onClick={() => table.nextPage()}
              disabled={!table.getCanNextPage()}
            >
              <ChevronRight className="h-4 w-4" />
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={() => table.setPageIndex(table.getPageCount() - 1)}
              disabled={!table.getCanNextPage()}
            >
              <ChevronsRight className="h-4 w-4" />
            </Button>
          </div>
          <div className="text-sm text-neutral-500">
            {table.getFilteredRowModel().rows.length} total rows
          </div>
        </div>
      )}
    </div>
  )
}
