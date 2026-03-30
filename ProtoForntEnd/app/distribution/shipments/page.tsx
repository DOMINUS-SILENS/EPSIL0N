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
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog"
import { Field, FieldGroup, FieldLabel } from "@/components/ui/field"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import {
  Search,
  Plus,
  Filter,
  Download,
  Truck,
  CheckCircle,
  Clock,
  Package,
  Eye,
  Copy,
} from "lucide-react"

interface Shipment {
  id: string
  orderId: string
  origin: string
  destination: string
  customer: string
  items: number
  weight: string
  status: string
  carrier: string
  service: string
  eta: string
  tracking: string
  createdAt: string
  cost: string
}

const shipments: Shipment[] = [
  { id: "SHP-2024-001", orderId: "ORD-001", origin: "Warehouse A, Newark", destination: "New York, NY 10001", customer: "Acme Corp", items: 12, weight: "45 kg", status: "in-transit", carrier: "FedEx", service: "Express", eta: "Mar 28, 2:00 PM", tracking: "FX794612384561", createdAt: "Mar 26", cost: "$125.00" },
  { id: "SHP-2024-002", orderId: "ORD-003", origin: "Warehouse B, Los Angeles", destination: "San Francisco, CA 94102", customer: "TechStart Inc", items: 5, weight: "12 kg", status: "delivered", carrier: "UPS", service: "Ground", eta: "Mar 27, 10:00 AM", tracking: "1Z999AA10123456784", createdAt: "Mar 24", cost: "$45.00" },
  { id: "SHP-2024-003", orderId: "ORD-005", origin: "Warehouse A, Newark", destination: "Chicago, IL 60601", customer: "Global Trade", items: 20, weight: "85 kg", status: "in-transit", carrier: "DHL", service: "Express", eta: "Mar 29, 4:00 PM", tracking: "1234567890", createdAt: "Mar 25", cost: "$210.00" },
  { id: "SHP-2024-004", orderId: "ORD-007", origin: "Warehouse C, Dallas", destination: "Austin, TX 78701", customer: "Summit Group", items: 8, weight: "28 kg", status: "pending", carrier: "FedEx", service: "Ground", eta: "Mar 30, 12:00 PM", tracking: "-", createdAt: "Mar 27", cost: "$65.00" },
  { id: "SHP-2024-005", orderId: "ORD-009", origin: "Warehouse B, Los Angeles", destination: "Seattle, WA 98101", customer: "Prime Solutions", items: 15, weight: "52 kg", status: "processing", carrier: "UPS", service: "Express", eta: "Mar 31, 9:00 AM", tracking: "-", createdAt: "Mar 27", cost: "$145.00" },
  { id: "SHP-2024-006", orderId: "ORD-011", origin: "Warehouse A, Newark", destination: "Boston, MA 02101", customer: "DataFlow Systems", items: 6, weight: "18 kg", status: "delivered", carrier: "USPS", service: "Priority", eta: "Mar 26, 3:00 PM", tracking: "9400111899223456789012", createdAt: "Mar 23", cost: "$35.00" },
  { id: "SHP-2024-007", orderId: "ORD-013", origin: "Warehouse C, Dallas", destination: "Denver, CO 80201", customer: "Nexus Industries", items: 25, weight: "110 kg", status: "in-transit", carrier: "FedEx", service: "Freight", eta: "Mar 30, 6:00 PM", tracking: "FX794612384999", createdAt: "Mar 26", cost: "$320.00" },
  { id: "SHP-2024-008", orderId: "ORD-015", origin: "Warehouse B, Los Angeles", destination: "Miami, FL 33101", customer: "Quantum Labs", items: 10, weight: "35 kg", status: "pending", carrier: "DHL", service: "Express", eta: "Apr 1, 11:00 AM", tracking: "-", createdAt: "Mar 27", cost: "$185.00" },
]

const columns: Column<Shipment>[] = [
  { key: "id", header: "Shipment ID", className: "font-mono text-sm" },
  {
    key: "route",
    header: "Route",
    className: "min-w-[200px]",
    render: (item) => (
      <div className="text-sm">
        <p className="font-medium text-muted-foreground">{item.origin}</p>
        <p className="font-medium">{item.destination}</p>
      </div>
    ),
  },
  { key: "customer", header: "Customer", className: "font-medium" },
  {
    key: "package",
    header: "Package",
    render: (item) => (
      <div className="text-sm">
        <p>{item.items} items</p>
        <p className="text-muted-foreground">{item.weight}</p>
      </div>
    ),
  },
  {
    key: "carrier",
    header: "Carrier",
    render: (item) => (
      <div>
        <p className="font-medium">{item.carrier}</p>
        <Badge variant="outline" className="text-xs mt-1">{item.service}</Badge>
      </div>
    ),
  },
  { key: "status", header: "Status", render: (item) => <StatusBadge status={item.status === "in-transit" ? "shipped" : item.status} /> },
  { key: "eta", header: "ETA", className: "text-muted-foreground" },
  {
    key: "tracking",
    header: "Tracking",
    render: (item) => (
      item.tracking !== "-" ? (
        <div className="flex items-center gap-1">
          <span className="font-mono text-xs text-muted-foreground truncate max-w-[100px]">{item.tracking}</span>
          <Button variant="ghost" size="sm" className="h-6 w-6 p-0">
            <Copy className="h-3 w-3" />
          </Button>
        </div>
      ) : (
        <span className="text-muted-foreground">-</span>
      )
    ),
  },
  { key: "cost", header: "Cost", className: "text-right font-medium" },
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

export default function ShipmentsPage() {
  const [search, setSearch] = React.useState("")
  
  const filteredShipments = shipments.filter(
    (shipment) =>
      shipment.id.toLowerCase().includes(search.toLowerCase()) ||
      shipment.customer.toLowerCase().includes(search.toLowerCase()) ||
      shipment.tracking.toLowerCase().includes(search.toLowerCase())
  )

  const inTransit = shipments.filter((s) => s.status === "in-transit").length
  const delivered = shipments.filter((s) => s.status === "delivered").length
  const pending = shipments.filter((s) => s.status === "pending" || s.status === "processing").length
  const totalCost = shipments.reduce((sum, s) => sum + parseFloat(s.cost.replace(/[$,]/g, "")), 0)

  return (
    <div className="min-h-screen bg-background">
      <AppSidebar />
      <main className="pl-64">
        <AppHeader title="Shipments" subtitle="Track and manage all outbound shipments" />
        
        <div className="p-6 space-y-6">
          {/* Stats Cards */}
          <div className="grid gap-4 md:grid-cols-4">
            <Card>
              <CardContent className="p-4">
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-info/10 text-info">
                    <Truck className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{inTransit}</p>
                    <p className="text-xs text-muted-foreground">In Transit</p>
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
                    <p className="text-2xl font-bold">{delivered}</p>
                    <p className="text-xs text-muted-foreground">Delivered</p>
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
                    <p className="text-2xl font-bold">{pending}</p>
                    <p className="text-xs text-muted-foreground">Pending</p>
                  </div>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent className="p-4">
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <Package className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">${totalCost.toFixed(0)}</p>
                    <p className="text-xs text-muted-foreground">Shipping Cost</p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Shipments Table */}
          <Card>
            <CardHeader className="pb-3">
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle className="text-base font-semibold">Shipment Management</CardTitle>
                  <CardDescription>View and track all shipments</CardDescription>
                </div>
                <div className="flex items-center gap-2">
                  <div className="relative w-64">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                      placeholder="Search shipments..."
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
                  <Dialog>
                    <DialogTrigger asChild>
                      <Button size="sm" className="gap-2">
                        <Plus className="h-4 w-4" />
                        New Shipment
                      </Button>
                    </DialogTrigger>
                    <DialogContent className="sm:max-w-[500px]">
                      <DialogHeader>
                        <DialogTitle>Create New Shipment</DialogTitle>
                        <DialogDescription>
                          Enter shipment details to create a new shipment.
                        </DialogDescription>
                      </DialogHeader>
                      <FieldGroup className="py-4">
                        <Field>
                          <FieldLabel>Order ID</FieldLabel>
                          <Input placeholder="ORD-XXX" />
                        </Field>
                        <div className="grid grid-cols-2 gap-4">
                          <Field>
                            <FieldLabel>Origin Warehouse</FieldLabel>
                            <Select>
                              <SelectTrigger>
                                <SelectValue placeholder="Select warehouse" />
                              </SelectTrigger>
                              <SelectContent>
                                <SelectItem value="warehouse-a">Warehouse A - Newark</SelectItem>
                                <SelectItem value="warehouse-b">Warehouse B - Los Angeles</SelectItem>
                                <SelectItem value="warehouse-c">Warehouse C - Dallas</SelectItem>
                              </SelectContent>
                            </Select>
                          </Field>
                          <Field>
                            <FieldLabel>Carrier</FieldLabel>
                            <Select>
                              <SelectTrigger>
                                <SelectValue placeholder="Select carrier" />
                              </SelectTrigger>
                              <SelectContent>
                                <SelectItem value="fedex">FedEx</SelectItem>
                                <SelectItem value="ups">UPS</SelectItem>
                                <SelectItem value="dhl">DHL</SelectItem>
                                <SelectItem value="usps">USPS</SelectItem>
                              </SelectContent>
                            </Select>
                          </Field>
                        </div>
                        <Field>
                          <FieldLabel>Destination Address</FieldLabel>
                          <Input placeholder="Full delivery address" />
                        </Field>
                        <div className="grid grid-cols-2 gap-4">
                          <Field>
                            <FieldLabel>Weight (kg)</FieldLabel>
                            <Input type="number" placeholder="0" />
                          </Field>
                          <Field>
                            <FieldLabel>Service Type</FieldLabel>
                            <Select>
                              <SelectTrigger>
                                <SelectValue placeholder="Select service" />
                              </SelectTrigger>
                              <SelectContent>
                                <SelectItem value="express">Express</SelectItem>
                                <SelectItem value="ground">Ground</SelectItem>
                                <SelectItem value="priority">Priority</SelectItem>
                                <SelectItem value="freight">Freight</SelectItem>
                              </SelectContent>
                            </Select>
                          </Field>
                        </div>
                      </FieldGroup>
                      <DialogFooter>
                        <Button variant="outline">Cancel</Button>
                        <Button>Create Shipment</Button>
                      </DialogFooter>
                    </DialogContent>
                  </Dialog>
                </div>
              </div>
            </CardHeader>
            <CardContent className="pt-0">
              <DataTable data={filteredShipments} columns={columns} />
            </CardContent>
          </Card>
        </div>
      </main>
    </div>
  )
}
