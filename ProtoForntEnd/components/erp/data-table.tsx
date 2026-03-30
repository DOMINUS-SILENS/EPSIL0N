"use client"

import * as React from "react"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { Badge } from "@/components/ui/badge"
import { cn } from "@/lib/utils"

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

export function DataTable<T extends Record<string, unknown>>({
  data,
  columns,
  onRowClick,
}: DataTableProps<T>) {
  return (
    <div className="rounded-lg border border-border overflow-hidden">
      <Table>
        <TableHeader>
          <TableRow className="bg-muted/50 hover:bg-muted/50">
            {columns.map((column) => (
              <TableHead
                key={String(column.key)}
                className={cn("text-xs font-semibold uppercase tracking-wide text-muted-foreground", column.className)}
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
                "border-border transition-colors",
                onRowClick && "cursor-pointer hover:bg-muted/30"
              )}
              onClick={() => onRowClick?.(item)}
            >
              {columns.map((column) => (
                <TableCell key={String(column.key)} className={column.className}>
                  {column.render
                    ? column.render(item)
                    : String(item[column.key as keyof T] ?? "")}
                </TableCell>
              ))}
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  )
}

export function StatusBadge({ status }: { status: string }) {
  const variants: Record<string, { className: string; label: string }> = {
    active: { className: "bg-success/20 text-success border-success/30", label: "Active" },
    pending: { className: "bg-warning/20 text-warning border-warning/30", label: "Pending" },
    completed: { className: "bg-info/20 text-info border-info/30", label: "Completed" },
    cancelled: { className: "bg-destructive/20 text-destructive border-destructive/30", label: "Cancelled" },
    shipped: { className: "bg-primary/20 text-primary border-primary/30", label: "Shipped" },
    processing: { className: "bg-info/20 text-info border-info/30", label: "Processing" },
    draft: { className: "bg-muted text-muted-foreground border-border", label: "Draft" },
    new: { className: "bg-primary/20 text-primary border-primary/30", label: "New" },
    qualified: { className: "bg-success/20 text-success border-success/30", label: "Qualified" },
    won: { className: "bg-success/20 text-success border-success/30", label: "Won" },
    lost: { className: "bg-destructive/20 text-destructive border-destructive/30", label: "Lost" },
    "in-progress": { className: "bg-info/20 text-info border-info/30", label: "In Progress" },
    delivered: { className: "bg-success/20 text-success border-success/30", label: "Delivered" },
    low: { className: "bg-warning/20 text-warning border-warning/30", label: "Low Stock" },
    "in-stock": { className: "bg-success/20 text-success border-success/30", label: "In Stock" },
    "out-of-stock": { className: "bg-destructive/20 text-destructive border-destructive/30", label: "Out of Stock" },
  }

  const variant = variants[status.toLowerCase()] || { className: "bg-muted text-muted-foreground", label: status }

  return (
    <Badge variant="outline" className={cn("text-xs font-medium", variant.className)}>
      {variant.label}
    </Badge>
  )
}
