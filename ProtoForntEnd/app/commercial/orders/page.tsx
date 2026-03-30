"use client"

import * as React from "react"
import { AppSidebar } from "@/components/erp/app-sidebar"
import { AppHeader } from "@/components/erp/app-header"
import { DataTable, StatusBadge, type Column } from "@/components/erp/data-table"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"
import {
  Search,
  Plus,
  Filter,
  Download,
  Eye,
  ShoppingCart,
  DollarSign,
  Clock,
  CheckCircle,
} from "lucide-react"

interface Order {
  id: string
  customer: string
  items: number
  total: string
  status: string
  date: string
  paymentStatus: string
  shipping: string
}

const orders: Order[] = [
  { id: "ORD-2024-001", customer: "Acme Corp", items: 5, total: "$12,500", status: "completed", date: "Mar 27, 2026", paymentStatus: "paid", shipping: "Delivered" },
  { id: "ORD-2024-002", customer: "TechStart Inc", items: 3, total: "$8,750", status: "processing", date: "Mar 27, 2026", paymentStatus: "paid", shipping: "In Transit" },
  { id: "ORD-2024-003", customer: "Global Trade", items: 8, total: "$15,200", status: "shipped", date: "Mar 26, 2026", paymentStatus: "paid", shipping: "Shipped" },
  { id: "ORD-2024-004", customer: "Summit Group", items: 2, total: "$6,800", status: "pending", date: "Mar 26, 2026", paymentStatus: "pending", shipping: "Awaiting" },
  { id: "ORD-2024-005", customer: "Prime Solutions", items: 12, total: "$22,100", status: "completed", date: "Mar 25, 2026", paymentStatus: "paid", shipping: "Delivered" },
  { id: "ORD-2024-006", customer: "DataFlow Systems", items: 4, total: "$9,350", status: "processing", date: "Mar 25, 2026", paymentStatus: "paid", shipping: "Processing" },
  { id: "ORD-2024-007", customer: "Nexus Industries", items: 6, total: "$18,900", status: "shipped", date: "Mar 24, 2026", paymentStatus: "paid", shipping: "In Transit" },
  { id: "ORD-2024-008", customer: "Quantum Labs", items: 10, total: "$31,500", status: "completed", date: "Mar 24, 2026", paymentStatus: "paid", shipping: "Delivered" },
  { id: "ORD-2024-009", customer: "Vertex Holdings", items: 7, total: "$14,200", status: "pending", date: "Mar 23, 2026", paymentStatus: "pending", shipping: "Awaiting" },
  { id: "ORD-2024-010", customer: "Horizon Tech", items: 15, total: "$42,000", status: "processing", date: "Mar 23, 2026", paymentStatus: "partial", shipping: "Processing" },
]

const columns: Column<Order>[] = [
  { key: "id", header: "Order ID", className: "font-mono text-sm" },
  { key: "customer", header: "Customer", className: "font-medium" },
  { key: "items", header: "Items", className: "text-center" },
  { key: "total", header: "Total", className: "text-right font-semibold" },
  { key: "status", header: "Status", render: (item) => <StatusBadge status={item.status} /> },
  {
    key: "paymentStatus",
    header: "Payment",
    render: (item) => (
      <Badge
        variant="outline"
        className={
          item.paymentStatus === "paid"
            ? "bg-success/20 text-success border-success/30"
            : item.paymentStatus === "pending"
            ? "bg-warning/20 text-warning border-warning/30"
            : "bg-info/20 text-info border-info/30"
        }
      >
        {item.paymentStatus.charAt(0).toUpperCase() + item.paymentStatus.slice(1)}
      </Badge>
    ),
  },
  { key: "shipping", header: "Shipping", className: "text-muted-foreground" },
  { key: "date", header: "Date", className: "text-muted-foreground" },
  {
    key: "actions",
    header: "",
    render: () => (
      <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
        <Eye className="h-4 w-4" />
      </Button>
    ),
  },
]

export default function OrdersPage() {
  const [search, setSearch] = React.useState("")
  
  const filteredOrders = orders.filter(
    (order) =>
      order.id.toLowerCase().includes(search.toLowerCase()) ||
      order.customer.toLowerCase().includes(search.toLowerCase())
  )

  const totalRevenue = orders.reduce((sum, order) => {
    const amount = parseFloat(order.total.replace(/[$,]/g, ""))
    return sum + amount
  }, 0)

  const completedOrders = orders.filter((o) => o.status === "completed").length
  const pendingOrders = orders.filter((o) => o.status === "pending").length
  const processingOrders = orders.filter((o) => o.status === "processing" || o.status === "shipped").length

  return (
    <div className="min-h-screen bg-background">
      <AppSidebar />
      <main className="pl-64">
        <AppHeader title="Sales Orders" subtitle="Track and manage customer orders" />
        
        <div className="p-6 space-y-6">
          {/* Stats Cards */}
          <div className="grid gap-4 md:grid-cols-4">
            <Card>
              <CardContent className="p-4">
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <ShoppingCart className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{orders.length}</p>
                    <p className="text-xs text-muted-foreground">Total Orders</p>
                  </div>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent className="p-4">
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-success/10 text-success">
                    <CheckCircle className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{completedOrders}</p>
                    <p className="text-xs text-muted-foreground">Completed</p>
                  </div>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent className="p-4">
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-warning/10 text-warning">
                    <Clock className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{pendingOrders + processingOrders}</p>
                    <p className="text-xs text-muted-foreground">In Progress</p>
                  </div>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent className="p-4">
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-info/10 text-info">
                    <DollarSign className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">${(totalRevenue / 1000).toFixed(1)}K</p>
                    <p className="text-xs text-muted-foreground">Total Value</p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Orders Table */}
          <Card>
            <CardHeader className="pb-3">
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle className="text-base font-semibold">Order Management</CardTitle>
                  <CardDescription>View and process customer orders</CardDescription>
                </div>
                <div className="flex items-center gap-2">
                  <div className="relative w-64">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                      placeholder="Search orders..."
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
                    New Order
                  </Button>
                </div>
              </div>
            </CardHeader>
            <CardContent className="pt-0">
              <DataTable data={filteredOrders} columns={columns} />
            </CardContent>
          </Card>
        </div>
      </main>
    </div>
  )
}
