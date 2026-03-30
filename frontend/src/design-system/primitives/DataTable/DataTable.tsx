import * as React from 'react'
import { cn } from '@/lib/utils'
import { Badge } from '@/design-system/primitives/Badge/Badge'

// Table Primitives
const Table = React.forwardRef<
  HTMLTableElement,
  React.HTMLAttributes<HTMLTableElement>
>(({ className, ...props }, ref) => (
  <div className="w-full overflow-auto">
    <table
      ref={ref}
      className={cn('w-full caption-bottom text-sm', className)}
      {...props}
    />
  </div>
))
Table.displayName = 'Table'

const TableHeader = React.forwardRef<
  HTMLTableSectionElement,
  React.HTMLAttributes<HTMLTableSectionElement>
>(({ className, ...props }, ref) => (
  <thead ref={ref} className={cn('[&_tr]:border-b', className)} {...props} />
))
TableHeader.displayName = 'TableHeader'

const TableBody = React.forwardRef<
  HTMLTableSectionElement,
  React.HTMLAttributes<HTMLTableSectionElement>
>(({ className, ...props }, ref) => (
  <tbody
    ref={ref}
    className={cn('[&_tr:last-child]:border-0', className)}
    {...props}
  />
))
TableBody.displayName = 'TableBody'

const TableRow = React.forwardRef<
  HTMLTableRowElement,
  React.HTMLAttributes<HTMLTableRowElement>
>(({ className, ...props }, ref) => (
  <tr
    ref={ref}
    className={cn(
      'border-b transition-colors hover:bg-neutral-100/50 data-[state=selected]:bg-neutral-100 dark:hover:bg-neutral-800/50 dark:data-[state=selected]:bg-neutral-800',
      className
    )}
    {...props}
  />
))
TableRow.displayName = 'TableRow'

const TableHead = React.forwardRef<
  HTMLTableCellElement,
  React.ThHTMLAttributes<HTMLTableCellElement>
>(({ className, ...props }, ref) => (
  <th
    ref={ref}
    className={cn(
      'h-12 px-4 text-left align-middle font-medium text-neutral-500 dark:text-neutral-400 [&:has([role=checkbox])]:pr-0',
      className
    )}
    {...props}
  />
))
TableHead.displayName = 'TableHead'

const TableCell = React.forwardRef<
  HTMLTableCellElement,
  React.TdHTMLAttributes<HTMLTableCellElement>
>(({ className, ...props }, ref) => (
  <td
    ref={ref}
    className={cn('p-4 align-middle [&:has([role=checkbox])]:pr-0', className)}
    {...props}
  />
))
TableCell.displayName = 'TableCell'

// DataTable Types
export interface Column<T> {
  key: keyof T | string
  header: string
  render?: (item: T) => React.ReactNode
  className?: string
}

interface DataTableProps<T> {
  data: T[]
  columns: Column<T>[]
  onRowClick?: (item: T) => void
}

// DataTable Component
export function DataTable<T extends Record<string, unknown>>({
  data,
  columns,
  onRowClick,
}: DataTableProps<T>) {
  return (
    <div className="rounded-lg border border-neutral-200 dark:border-neutral-800 overflow-hidden">
      <Table>
        <TableHeader>
          <TableRow className="bg-neutral-50 dark:bg-neutral-800/50 hover:bg-neutral-50 dark:hover:bg-neutral-800/50">
            {columns.map((column) => (
              <TableHead
                key={String(column.key)}
                className={cn(
                  'text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400',
                  column.className
                )}
              >
                {column.header}
              </TableHead>
            ))}
          </TableRow>
        </TableHeader>
        <TableBody>
          {data.map((item, index) => (
            <TableRow
              key={index}
              className={cn(
                'border-neutral-200 dark:border-neutral-800 transition-colors',
                onRowClick && 'cursor-pointer hover:bg-neutral-100/30 dark:hover:bg-neutral-800/30'
              )}
              onClick={() => onRowClick?.(item)}
            >
              {columns.map((column) => (
                <TableCell key={String(column.key)} className={column.className}>
                  {column.render
                    ? column.render(item)
                    : String(item[column.key as keyof T] ?? '')}
                </TableCell>
              ))}
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  )
}

// StatusBadge Component
export function StatusBadge({ status }: { status: string }) {
  const variants: Record<string, { variant: 'default' | 'success' | 'warning' | 'destructive' | 'secondary' | 'outline'; label: string }> = {
    active: { variant: 'success', label: 'Active' },
    pending: { variant: 'warning', label: 'Pending' },
    completed: { variant: 'success', label: 'Completed' },
    cancelled: { variant: 'destructive', label: 'Cancelled' },
    shipped: { variant: 'default', label: 'Shipped' },
    processing: { variant: 'default', label: 'Processing' },
    draft: { variant: 'secondary', label: 'Draft' },
    new: { variant: 'default', label: 'New' },
    qualified: { variant: 'success', label: 'Qualified' },
    won: { variant: 'success', label: 'Won' },
    lost: { variant: 'destructive', label: 'Lost' },
    'in-progress': { variant: 'default', label: 'In Progress' },
    'in_progress': { variant: 'default', label: 'In Progress' },
    delivered: { variant: 'success', label: 'Delivered' },
    low: { variant: 'warning', label: 'Low Stock' },
    'in-stock': { variant: 'success', label: 'In Stock' },
    'in_stock': { variant: 'success', label: 'In Stock' },
    'out-of-stock': { variant: 'destructive', label: 'Out of Stock' },
    'out_of_stock': { variant: 'destructive', label: 'Out of Stock' },
    passed: { variant: 'success', label: 'Passed' },
    failed: { variant: 'destructive', label: 'Failed' },
  }

  const variant = variants[status.toLowerCase()] || { variant: 'secondary', label: status }

  return (
    <Badge variant={variant.variant} className="text-xs font-medium">
      {variant.label}
    </Badge>
  )
}

export { Table, TableHeader, TableBody, TableRow, TableHead, TableCell }
