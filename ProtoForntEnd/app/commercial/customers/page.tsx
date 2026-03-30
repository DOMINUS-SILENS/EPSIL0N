"use client"

import * as React from "react"
import { AppSidebar } from "@/components/erp/app-sidebar"
import { AppHeader } from "@/components/erp/app-header"
import { DataTable, StatusBadge, type Column } from "@/components/erp/data-table"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
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
  Mail,
  Phone,
  Building2,
  MapPin,
} from "lucide-react"

interface Customer {
  id: string
  name: string
  company: string
  email: string
  phone: string
  status: string
  revenue: string
  location: string
  industry: string
  lastContact: string
}

const customers: Customer[] = [
  { id: "CUS-001", name: "John Smith", company: "Acme Corp", email: "john@acme.com", phone: "+1 555-0101", status: "active", revenue: "$125,000", location: "New York, NY", industry: "Technology", lastContact: "Today" },
  { id: "CUS-002", name: "Sarah Johnson", company: "TechStart Inc", email: "sarah@techstart.io", phone: "+1 555-0102", status: "active", revenue: "$89,500", location: "San Francisco, CA", industry: "Software", lastContact: "Yesterday" },
  { id: "CUS-003", name: "Michael Brown", company: "Global Trade", email: "m.brown@globaltrade.com", phone: "+1 555-0103", status: "active", revenue: "$215,000", location: "Chicago, IL", industry: "Import/Export", lastContact: "2 days ago" },
  { id: "CUS-004", name: "Emily Davis", company: "Summit Group", email: "emily@summit.com", phone: "+1 555-0104", status: "pending", revenue: "$45,200", location: "Austin, TX", industry: "Consulting", lastContact: "1 week ago" },
  { id: "CUS-005", name: "Robert Wilson", company: "Prime Solutions", email: "rwilson@prime.co", phone: "+1 555-0105", status: "active", revenue: "$178,900", location: "Seattle, WA", industry: "Manufacturing", lastContact: "3 days ago" },
  { id: "CUS-006", name: "Lisa Anderson", company: "DataFlow Systems", email: "lisa@dataflow.io", phone: "+1 555-0106", status: "active", revenue: "$92,300", location: "Boston, MA", industry: "Data Analytics", lastContact: "Today" },
  { id: "CUS-007", name: "James Taylor", company: "Nexus Industries", email: "jtaylor@nexus.com", phone: "+1 555-0107", status: "pending", revenue: "$156,800", location: "Denver, CO", industry: "Energy", lastContact: "5 days ago" },
  { id: "CUS-008", name: "Jennifer Martinez", company: "Quantum Labs", email: "jenn@quantum.io", phone: "+1 555-0108", status: "active", revenue: "$234,500", location: "Miami, FL", industry: "Research", lastContact: "Yesterday" },
]

const columns: Column<Customer>[] = [
  { key: "id", header: "ID", className: "font-mono text-sm w-24" },
  {
    key: "name",
    header: "Customer",
    className: "min-w-[200px]",
    render: (item) => (
      <div>
        <p className="font-medium">{item.name}</p>
        <p className="text-xs text-muted-foreground">{item.company}</p>
      </div>
    ),
  },
  {
    key: "contact",
    header: "Contact",
    render: (item) => (
      <div className="space-y-1">
        <div className="flex items-center gap-1 text-sm text-muted-foreground">
          <Mail className="h-3 w-3" />
          {item.email}
        </div>
        <div className="flex items-center gap-1 text-sm text-muted-foreground">
          <Phone className="h-3 w-3" />
          {item.phone}
        </div>
      </div>
    ),
  },
  {
    key: "location",
    header: "Location",
    render: (item) => (
      <div className="flex items-center gap-1 text-sm">
        <MapPin className="h-3 w-3 text-muted-foreground" />
        {item.location}
      </div>
    ),
  },
  { key: "industry", header: "Industry" },
  { key: "status", header: "Status", render: (item) => <StatusBadge status={item.status} /> },
  { key: "revenue", header: "Total Revenue", className: "text-right font-medium" },
  { key: "lastContact", header: "Last Contact", className: "text-muted-foreground" },
]

export default function CustomersPage() {
  const [search, setSearch] = React.useState("")
  
  const filteredCustomers = customers.filter(
    (customer) =>
      customer.name.toLowerCase().includes(search.toLowerCase()) ||
      customer.company.toLowerCase().includes(search.toLowerCase()) ||
      customer.email.toLowerCase().includes(search.toLowerCase())
  )

  return (
    <div className="min-h-screen bg-background">
      <AppSidebar />
      <main className="pl-64">
        <AppHeader title="Customers" subtitle="Manage your customer database and relationships" />
        
        <div className="p-6 space-y-6">
          {/* Stats Cards */}
          <div className="grid gap-4 md:grid-cols-4">
            <Card>
              <CardContent className="p-4">
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <Building2 className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">2,847</p>
                    <p className="text-xs text-muted-foreground">Total Customers</p>
                  </div>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent className="p-4">
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-success/10 text-success">
                    <Building2 className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">2,456</p>
                    <p className="text-xs text-muted-foreground">Active Customers</p>
                  </div>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent className="p-4">
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-warning/10 text-warning">
                    <Building2 className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">391</p>
                    <p className="text-xs text-muted-foreground">Pending Review</p>
                  </div>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent className="p-4">
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-info/10 text-info">
                    <Building2 className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">$4.2M</p>
                    <p className="text-xs text-muted-foreground">Total Revenue</p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Customer Table */}
          <Card>
            <CardHeader className="pb-3">
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle className="text-base font-semibold">Customer Database</CardTitle>
                  <CardDescription>View and manage all customer information</CardDescription>
                </div>
                <div className="flex items-center gap-2">
                  <div className="relative w-64">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                      placeholder="Search customers..."
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
                        Add Customer
                      </Button>
                    </DialogTrigger>
                    <DialogContent className="sm:max-w-[500px]">
                      <DialogHeader>
                        <DialogTitle>Add New Customer</DialogTitle>
                        <DialogDescription>
                          Enter the customer details below to add them to your database.
                        </DialogDescription>
                      </DialogHeader>
                      <FieldGroup className="py-4">
                        <div className="grid grid-cols-2 gap-4">
                          <Field>
                            <FieldLabel>First Name</FieldLabel>
                            <Input placeholder="John" />
                          </Field>
                          <Field>
                            <FieldLabel>Last Name</FieldLabel>
                            <Input placeholder="Smith" />
                          </Field>
                        </div>
                        <Field>
                          <FieldLabel>Company</FieldLabel>
                          <Input placeholder="Acme Corp" />
                        </Field>
                        <Field>
                          <FieldLabel>Email</FieldLabel>
                          <Input type="email" placeholder="john@acme.com" />
                        </Field>
                        <Field>
                          <FieldLabel>Phone</FieldLabel>
                          <Input type="tel" placeholder="+1 555-0100" />
                        </Field>
                        <div className="grid grid-cols-2 gap-4">
                          <Field>
                            <FieldLabel>Industry</FieldLabel>
                            <Select>
                              <SelectTrigger>
                                <SelectValue placeholder="Select industry" />
                              </SelectTrigger>
                              <SelectContent>
                                <SelectItem value="technology">Technology</SelectItem>
                                <SelectItem value="manufacturing">Manufacturing</SelectItem>
                                <SelectItem value="retail">Retail</SelectItem>
                                <SelectItem value="healthcare">Healthcare</SelectItem>
                                <SelectItem value="finance">Finance</SelectItem>
                              </SelectContent>
                            </Select>
                          </Field>
                          <Field>
                            <FieldLabel>Status</FieldLabel>
                            <Select>
                              <SelectTrigger>
                                <SelectValue placeholder="Select status" />
                              </SelectTrigger>
                              <SelectContent>
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="pending">Pending</SelectItem>
                                <SelectItem value="inactive">Inactive</SelectItem>
                              </SelectContent>
                            </Select>
                          </Field>
                        </div>
                      </FieldGroup>
                      <DialogFooter>
                        <Button variant="outline">Cancel</Button>
                        <Button>Add Customer</Button>
                      </DialogFooter>
                    </DialogContent>
                  </Dialog>
                </div>
              </div>
            </CardHeader>
            <CardContent className="pt-0">
              <DataTable data={filteredCustomers} columns={columns} />
            </CardContent>
          </Card>
        </div>
      </main>
    </div>
  )
}
