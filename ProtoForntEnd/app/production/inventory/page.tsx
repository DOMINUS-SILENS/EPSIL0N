"use client"

import * as React from "react"
import { AppSidebar } from "@/components/erp/app-sidebar"
import { AppHeader } from "@/components/erp/app-header"
import { DataTable, StatusBadge, type Column } from "@/components/erp/data-table"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Progress } from "@/components/ui/progress"
import {
  Search,
  Plus,
  Filter,
  Download,
  Package,
  AlertTriangle,
  TrendingUp,
  Warehouse,
} from "lucide-react"

interface InventoryItem {
  sku: string
  name: string
  category: string
  quantity: number
  minStock: number
  maxStock: number
  unit: string
  status: string
  location: string
  lastUpdated: string
  value: string
}

const inventoryItems: InventoryItem[] = [
  { sku: "SKU-001", name: "Raw Material A - Steel Sheets", category: "Raw Materials", quantity: 1500, minStock: 500, maxStock: 3000, unit: "pcs", status: "in-stock", location: "Warehouse A", lastUpdated: "Today", value: "$45,000" },
  { sku: "SKU-002", name: "Component X - Circuit Board", category: "Components", quantity: 320, minStock: 400, maxStock: 1500, unit: "pcs", status: "low", location: "Warehouse B", lastUpdated: "Today", value: "$16,000" },
  { sku: "SKU-003", name: "Assembly Part Y - Gearbox", category: "Parts", quantity: 850, minStock: 200, maxStock: 1200, unit: "pcs", status: "in-stock", location: "Warehouse A", lastUpdated: "Yesterday", value: "$127,500" },
  { sku: "SKU-004", name: "Finished Product Z - Motor Unit", category: "Finished Goods", quantity: 45, minStock: 100, maxStock: 500, unit: "units", status: "low", location: "Warehouse C", lastUpdated: "Today", value: "$67,500" },
  { sku: "SKU-005", name: "Packaging Material - Boxes", category: "Packaging", quantity: 5000, minStock: 1000, maxStock: 8000, unit: "pcs", status: "in-stock", location: "Warehouse B", lastUpdated: "2 days ago", value: "$5,000" },
  { sku: "SKU-006", name: "Raw Material B - Copper Wire", category: "Raw Materials", quantity: 2200, minStock: 800, maxStock: 4000, unit: "meters", status: "in-stock", location: "Warehouse A", lastUpdated: "Today", value: "$33,000" },
  { sku: "SKU-007", name: "Component Y - Sensor Module", category: "Components", quantity: 0, minStock: 150, maxStock: 600, unit: "pcs", status: "out-of-stock", location: "Warehouse B", lastUpdated: "1 week ago", value: "$0" },
  { sku: "SKU-008", name: "Assembly Part Z - Casing", category: "Parts", quantity: 420, minStock: 300, maxStock: 1000, unit: "pcs", status: "in-stock", location: "Warehouse C", lastUpdated: "Yesterday", value: "$21,000" },
]

const columns: Column<InventoryItem>[] = [
  { key: "sku", header: "SKU", className: "font-mono text-sm" },
  {
    key: "name",
    header: "Item",
    className: "min-w-[200px]",
    render: (item) => (
      <div>
        <p className="font-medium">{item.name}</p>
        <p className="text-xs text-muted-foreground">{item.category}</p>
      </div>
    ),
  },
  {
    key: "quantity",
    header: "Stock Level",
    render: (item) => {
      const percentage = Math.min((item.quantity / item.maxStock) * 100, 100)
      return (
        <div className="space-y-1 min-w-[120px]">
          <div className="flex items-center justify-between text-sm">
            <span className="font-medium">{item.quantity.toLocaleString()} {item.unit}</span>
          </div>
          <Progress 
            value={percentage} 
            className={`h-2 ${
              item.quantity === 0 ? "[&>div]:bg-destructive" :
              item.quantity < item.minStock ? "[&>div]:bg-warning" : ""
            }`}
          />
          <p className="text-xs text-muted-foreground">
            Min: {item.minStock} / Max: {item.maxStock}
          </p>
        </div>
      )
    },
  },
  { key: "status", header: "Status", render: (item) => <StatusBadge status={item.status} /> },
  { key: "location", header: "Location" },
  { key: "value", header: "Value", className: "text-right font-medium" },
  { key: "lastUpdated", header: "Updated", className: "text-muted-foreground" },
]

export default function InventoryPage() {
  const [search, setSearch] = React.useState("")
  
  const filteredItems = inventoryItems.filter(
    (item) =>
      item.sku.toLowerCase().includes(search.toLowerCase()) ||
      item.name.toLowerCase().includes(search.toLowerCase()) ||
      item.category.toLowerCase().includes(search.toLowerCase())
  )

  const totalItems = inventoryItems.length
  const lowStockItems = inventoryItems.filter((i) => i.status === "low").length
  const outOfStockItems = inventoryItems.filter((i) => i.status === "out-of-stock").length
  const totalValue = inventoryItems.reduce((sum, item) => {
    const value = parseFloat(item.value.replace(/[$,]/g, ""))
    return sum + value
  }, 0)

  return (
    <div className="min-h-screen bg-background">
      <AppSidebar />
      <main className="pl-64">
        <AppHeader title="Inventory Management" subtitle="Track stock levels, locations, and valuations" />
        
        <div className="p-6 space-y-6">
          {/* Stats Cards */}
          <div className="grid gap-4 md:grid-cols-4">
            <Card>
              <CardContent className="p-4">
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <Package className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{totalItems}</p>
                    <p className="text-xs text-muted-foreground">Total SKUs</p>
                  </div>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent className="p-4">
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-warning/10 text-warning">
                    <AlertTriangle className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{lowStockItems}</p>
                    <p className="text-xs text-muted-foreground">Low Stock</p>
                  </div>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent className="p-4">
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-destructive/10 text-destructive">
                    <Package className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{outOfStockItems}</p>
                    <p className="text-xs text-muted-foreground">Out of Stock</p>
                  </div>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent className="p-4">
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-info/10 text-info">
                    <TrendingUp className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">${(totalValue / 1000).toFixed(0)}K</p>
                    <p className="text-xs text-muted-foreground">Total Value</p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Warehouse Summary */}
          <div className="grid gap-4 md:grid-cols-3">
            {["Warehouse A", "Warehouse B", "Warehouse C"].map((warehouse) => {
              const warehouseItems = inventoryItems.filter((i) => i.location === warehouse)
              const warehouseValue = warehouseItems.reduce((sum, item) => {
                const value = parseFloat(item.value.replace(/[$,]/g, ""))
                return sum + value
              }, 0)
              return (
                <Card key={warehouse}>
                  <CardContent className="p-4">
                    <div className="flex items-center gap-3">
                      <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                        <Warehouse className="h-5 w-5" />
                      </div>
                      <div className="flex-1">
                        <p className="font-medium">{warehouse}</p>
                        <div className="flex items-center justify-between text-sm text-muted-foreground">
                          <span>{warehouseItems.length} items</span>
                          <span className="font-medium text-foreground">${(warehouseValue / 1000).toFixed(0)}K</span>
                        </div>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              )
            })}
          </div>

          {/* Inventory Table */}
          <Card>
            <CardHeader className="pb-3">
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle className="text-base font-semibold">Stock Items</CardTitle>
                  <CardDescription>Manage inventory levels and track stock movements</CardDescription>
                </div>
                <div className="flex items-center gap-2">
                  <div className="relative w-64">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                      placeholder="Search inventory..."
                      className="pl-9"
                      value={search}
                      onChange={(e) => setSearch(e.target.value)}
                    />
                  </div>
                  <Button variant="outline" size="sm" className="gap-2">
                    <Filter className="h-4 w-4" />
                    Filter
                  </Button>
                  <Button variant="outline" size="sm" className="gap-2">
                    <Download className="h-4 w-4" />
                    Export
                  </Button>
                  <Button size="sm" className="gap-2">
                    <Plus className="h-4 w-4" />
                    Add Item
                  </Button>
                </div>
              </div>
            </CardHeader>
            <CardContent className="pt-0">
              <DataTable data={filteredItems} columns={columns} />
            </CardContent>
          </Card>
        </div>
      </main>
    </div>
  )
}
