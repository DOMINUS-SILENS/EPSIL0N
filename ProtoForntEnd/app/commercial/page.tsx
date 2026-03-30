import { AppSidebar } from "@/components/erp/app-sidebar"
import { AppHeader } from "@/components/erp/app-header"
import { KPICard } from "@/components/erp/kpi-card"
import { DataTable, StatusBadge, type Column } from "@/components/erp/data-table"
import { ChartCard, RevenueChart } from "@/components/erp/charts"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import {
  Users,
  Target,
  TrendingUp,
  DollarSign,
  ShoppingCart,
  FileText,
  Plus,
  Filter,
  Download,
} from "lucide-react"

// Sample data
const revenueData = [
  { month: "Jan", revenue: 125000, orders: 142 },
  { month: "Feb", revenue: 148000, orders: 168 },
  { month: "Mar", revenue: 162000, orders: 185 },
  { month: "Apr", revenue: 189000, orders: 210 },
  { month: "May", revenue: 215000, orders: 245 },
  { month: "Jun", revenue: 242000, orders: 278 },
]

interface Customer {
  id: string
  name: string
  company: string
  email: string
  status: string
  revenue: string
  lastContact: string
}

const customers: Customer[] = [
  { id: "CUS-001", name: "John Smith", company: "Acme Corp", email: "john@acme.com", status: "active", revenue: "$125,000", lastContact: "Today" },
  { id: "CUS-002", name: "Sarah Johnson", company: "TechStart Inc", email: "sarah@techstart.io", status: "active", revenue: "$89,500", lastContact: "Yesterday" },
  { id: "CUS-003", name: "Michael Brown", company: "Global Trade", email: "m.brown@globaltrade.com", status: "active", revenue: "$215,000", lastContact: "2 days ago" },
  { id: "CUS-004", name: "Emily Davis", company: "Summit Group", email: "emily@summit.com", status: "pending", revenue: "$45,200", lastContact: "1 week ago" },
  { id: "CUS-005", name: "Robert Wilson", company: "Prime Solutions", email: "rwilson@prime.co", status: "active", revenue: "$178,900", lastContact: "3 days ago" },
]

const customerColumns: Column<Customer>[] = [
  { key: "id", header: "ID", className: "font-mono text-sm" },
  { key: "name", header: "Name", className: "font-medium" },
  { key: "company", header: "Company" },
  { key: "email", header: "Email", className: "text-muted-foreground" },
  { key: "status", header: "Status", render: (item) => <StatusBadge status={item.status} /> },
  { key: "revenue", header: "Revenue", className: "text-right font-medium" },
  { key: "lastContact", header: "Last Contact", className: "text-muted-foreground" },
]

interface Lead {
  id: string
  name: string
  company: string
  source: string
  status: string
  value: string
  assignedTo: string
}

const leads: Lead[] = [
  { id: "LED-001", name: "Tech Solutions Ltd", company: "Tech Solutions", source: "Website", status: "new", value: "$50,000", assignedTo: "John Doe" },
  { id: "LED-002", name: "Future Industries", company: "Future Ind.", source: "Referral", status: "qualified", value: "$125,000", assignedTo: "Jane Smith" },
  { id: "LED-003", name: "Metro Corp", company: "Metro Corp", source: "Trade Show", status: "new", value: "$75,000", assignedTo: "John Doe" },
  { id: "LED-004", name: "Pacific Trading", company: "Pacific Ltd", source: "Cold Call", status: "qualified", value: "$200,000", assignedTo: "Mike Johnson" },
  { id: "LED-005", name: "Alpha Systems", company: "Alpha Sys", source: "LinkedIn", status: "new", value: "$35,000", assignedTo: "Jane Smith" },
]

const leadColumns: Column<Lead>[] = [
  { key: "id", header: "ID", className: "font-mono text-sm" },
  { key: "name", header: "Lead Name", className: "font-medium" },
  { key: "source", header: "Source" },
  { key: "status", header: "Status", render: (item) => <StatusBadge status={item.status} /> },
  { key: "value", header: "Est. Value", className: "text-right font-medium" },
  { key: "assignedTo", header: "Assigned To" },
]

interface Opportunity {
  id: string
  name: string
  customer: string
  stage: string
  value: string
  probability: string
  closeDate: string
}

const opportunities: Opportunity[] = [
  { id: "OPP-001", name: "Enterprise License Deal", customer: "Acme Corp", stage: "Negotiation", value: "$250,000", probability: "75%", closeDate: "Apr 15" },
  { id: "OPP-002", name: "Hardware Upgrade", customer: "TechStart Inc", stage: "Proposal", value: "$85,000", probability: "50%", closeDate: "Apr 28" },
  { id: "OPP-003", name: "Annual Contract Renewal", customer: "Global Trade", stage: "Closing", value: "$175,000", probability: "90%", closeDate: "Mar 31" },
  { id: "OPP-004", name: "New Implementation", customer: "Summit Group", stage: "Discovery", value: "$120,000", probability: "25%", closeDate: "May 15" },
  { id: "OPP-005", name: "Expansion Project", customer: "Prime Solutions", stage: "Negotiation", value: "$320,000", probability: "60%", closeDate: "Apr 20" },
]

const opportunityColumns: Column<Opportunity>[] = [
  { key: "id", header: "ID", className: "font-mono text-sm" },
  { key: "name", header: "Opportunity", className: "font-medium" },
  { key: "customer", header: "Customer" },
  { key: "stage", header: "Stage", render: (item) => <StatusBadge status={item.stage.toLowerCase().replace(" ", "-")} /> },
  { key: "value", header: "Value", className: "text-right font-medium" },
  { key: "probability", header: "Probability", className: "text-center" },
  { key: "closeDate", header: "Close Date", className: "text-muted-foreground" },
]

interface Order {
  id: string
  customer: string
  items: number
  total: string
  status: string
  date: string
}

const orders: Order[] = [
  { id: "ORD-001", customer: "Acme Corp", items: 5, total: "$12,500", status: "completed", date: "Mar 27" },
  { id: "ORD-002", customer: "TechStart Inc", items: 3, total: "$8,750", status: "processing", date: "Mar 27" },
  { id: "ORD-003", customer: "Global Trade", items: 8, total: "$15,200", status: "shipped", date: "Mar 26" },
  { id: "ORD-004", customer: "Summit Group", items: 2, total: "$6,800", status: "pending", date: "Mar 26" },
  { id: "ORD-005", customer: "Prime Solutions", items: 12, total: "$22,100", status: "completed", date: "Mar 25" },
]

const orderColumns: Column<Order>[] = [
  { key: "id", header: "Order ID", className: "font-mono text-sm" },
  { key: "customer", header: "Customer", className: "font-medium" },
  { key: "items", header: "Items", className: "text-center" },
  { key: "total", header: "Total", className: "text-right font-medium" },
  { key: "status", header: "Status", render: (item) => <StatusBadge status={item.status} /> },
  { key: "date", header: "Date", className: "text-muted-foreground" },
]

export default function CommercialPage() {
  return (
    <div className="min-h-screen bg-background">
      <AppSidebar />
      <main className="pl-64">
        <AppHeader title="Commercial" subtitle="CRM, Sales Force Automation, and Order Management" />
        
        <div className="p-6 space-y-6">
          {/* KPI Cards */}
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <KPICard
              title="Total Customers"
              value="2,847"
              change={5.2}
              trend="up"
              icon={<Users className="h-5 w-5" />}
            />
            <KPICard
              title="Active Leads"
              value="156"
              change={12.8}
              trend="up"
              icon={<Target className="h-5 w-5" />}
            />
            <KPICard
              title="Pipeline Value"
              value="$2.4M"
              change={8.5}
              trend="up"
              icon={<TrendingUp className="h-5 w-5" />}
            />
            <KPICard
              title="Monthly Revenue"
              value="$242K"
              change={15.3}
              trend="up"
              icon={<DollarSign className="h-5 w-5" />}
            />
          </div>

          {/* Revenue Chart */}
          <ChartCard title="Sales Performance" subtitle="Revenue trend over the past 6 months">
            <RevenueChart data={revenueData} />
          </ChartCard>

          {/* Tabs for different sections */}
          <Tabs defaultValue="customers" className="space-y-4">
            <div className="flex items-center justify-between">
              <TabsList className="bg-muted">
                <TabsTrigger value="customers" className="gap-2">
                  <Users className="h-4 w-4" />
                  Customers
                </TabsTrigger>
                <TabsTrigger value="leads" className="gap-2">
                  <Target className="h-4 w-4" />
                  Leads
                </TabsTrigger>
                <TabsTrigger value="opportunities" className="gap-2">
                  <TrendingUp className="h-4 w-4" />
                  Opportunities
                </TabsTrigger>
                <TabsTrigger value="orders" className="gap-2">
                  <ShoppingCart className="h-4 w-4" />
                  Orders
                </TabsTrigger>
              </TabsList>
              <div className="flex items-center gap-2">
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
                  Add New
                </Button>
              </div>
            </div>

            <TabsContent value="customers">
              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="text-base font-semibold">Customer Database</CardTitle>
                </CardHeader>
                <CardContent className="pt-0">
                  <DataTable data={customers} columns={customerColumns} />
                </CardContent>
              </Card>
            </TabsContent>

            <TabsContent value="leads">
              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="text-base font-semibold">Lead Pipeline</CardTitle>
                </CardHeader>
                <CardContent className="pt-0">
                  <DataTable data={leads} columns={leadColumns} />
                </CardContent>
              </Card>
            </TabsContent>

            <TabsContent value="opportunities">
              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="text-base font-semibold">Sales Opportunities</CardTitle>
                </CardHeader>
                <CardContent className="pt-0">
                  <DataTable data={opportunities} columns={opportunityColumns} />
                </CardContent>
              </Card>
            </TabsContent>

            <TabsContent value="orders">
              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="text-base font-semibold">Sales Orders</CardTitle>
                </CardHeader>
                <CardContent className="pt-0">
                  <DataTable data={orders} columns={orderColumns} />
                </CardContent>
              </Card>
            </TabsContent>
          </Tabs>
        </div>
      </main>
    </div>
  )
}
