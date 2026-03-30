import { KPICard } from '@/design-system/primitives/KPICard/KPICard'
import { DataTable } from '@/design-system/composite/DataTable/DataTable'
import { ChartCard, OrdersChart, ProductionPieChart, DistributionChart } from '@/components/erp/charts'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  DollarSign,
  TrendingUp,
  Users,
  Package,
  Download,
  Calendar,
  BarChart3,
  Filter,
} from 'lucide-react'
import type { ColumnDef } from '@tanstack/react-table'

// Sample data
const revenueData = [
  { month: 'Jan', revenue: 125000, orders: 142 },
  { month: 'Feb', revenue: 148000, orders: 168 },
  { month: 'Mar', revenue: 162000, orders: 185 },
  { month: 'Apr', revenue: 189000, orders: 210 },
  { month: 'May', revenue: 215000, orders: 245 },
  { month: 'Jun', revenue: 242000, orders: 278 },
]

const ordersData = [
  { month: 'Jan', orders: 142, completed: 135 },
  { month: 'Feb', orders: 168, completed: 158 },
  { month: 'Mar', orders: 185, completed: 172 },
  { month: 'Apr', orders: 210, completed: 195 },
  { month: 'May', orders: 245, completed: 228 },
  { month: 'Jun', orders: 278, completed: 260 },
]

const distributionData = [
  { day: 'Mon', outgoing: 45, incoming: 32 },
  { day: 'Tue', outgoing: 52, incoming: 38 },
  { day: 'Wed', outgoing: 48, incoming: 42 },
  { day: 'Thu', outgoing: 61, incoming: 35 },
  { day: 'Fri', outgoing: 55, incoming: 40 },
  { day: 'Sat', outgoing: 38, incoming: 28 },
  { day: 'Sun', outgoing: 25, incoming: 18 },
]

const salesByRegion = [
  { name: 'North America', value: 45, color: 'hsl(var(--primary))' },
  { name: 'Europe', value: 28, color: 'hsl(var(--info))' },
  { name: 'Asia Pacific', value: 18, color: 'hsl(var(--warning))' },
  { name: 'Other', value: 9, color: 'hsl(var(--muted))' },
]

const productCategories = [
  { name: 'Electronics', value: 35, color: 'hsl(var(--primary))' },
  { name: 'Industrial', value: 30, color: 'hsl(var(--info))' },
  { name: 'Consumer', value: 25, color: 'hsl(var(--warning))' },
  { name: 'Services', value: 10, color: 'hsl(var(--muted))' },
]

interface TopProduct {
  [key: string]: unknown
  rank: number
  name: string
  category: string
  revenue: number
  units: number
  growth: number
}

const topProducts: TopProduct[] = [
  { rank: 1, name: 'Industrial Motor X-500', category: 'Industrial', revenue: 485000, units: 245, growth: 15.2 },
  { rank: 2, name: 'Smart Controller Pro', category: 'Electronics', revenue: 362000, units: 890, growth: 22.5 },
  { rank: 3, name: 'Assembly Kit A-100', category: 'Industrial', revenue: 298000, units: 1560, growth: -5.3 },
  { rank: 4, name: 'Power Supply Unit', category: 'Electronics', revenue: 245000, units: 420, growth: 8.7 },
  { rank: 5, name: 'Sensor Array S-200', category: 'Electronics', revenue: 198000, units: 675, growth: 12.1 },
]

const topProductColumns: ColumnDef<TopProduct>[] = [
  { accessorKey: 'rank', header: 'Rank', cell: ({ row }) => <span className="font-bold text-muted-foreground">#{row.getValue('rank')}</span> },
  { accessorKey: 'name', header: 'Product', cell: ({ row }) => <span className="font-medium">{row.getValue('name')}</span> },
  { accessorKey: 'category', header: 'Category', cell: ({ row }) => <span className="text-muted-foreground">{row.getValue('category')}</span> },
  { accessorKey: 'revenue', header: 'Revenue', cell: ({ row }) => <span className="font-medium">${(row.getValue('revenue') as number).toLocaleString()}</span> },
  { accessorKey: 'units', header: 'Units Sold', cell: ({ row }) => <span className="text-center">{row.getValue('units')}</span> },
  {
    accessorKey: 'growth',
    header: 'Growth',
    cell: ({ row }) => {
      const growth = row.getValue('growth') as number
      const colorClass = growth > 0 ? 'text-green-600' : 'text-red-600'
      return <span className={`font-medium ${colorClass}`}>{growth > 0 ? '+' : ''}{growth}%</span>
    },
  },
]

interface CustomerSegment {
  [key: string]: unknown
  segment: string
  customers: number
  revenue: number
  avgOrder: number
  retention: number
}

const customerSegments: CustomerSegment[] = [
  { segment: 'Enterprise', customers: 45, revenue: 1250000, avgOrder: 45000, retention: 94 },
  { segment: 'Mid-Market', customers: 180, revenue: 890000, avgOrder: 8500, retention: 87 },
  { segment: 'Small Business', customers: 420, revenue: 520000, avgOrder: 2200, retention: 78 },
  { segment: 'Startup', customers: 95, revenue: 180000, avgOrder: 3200, retention: 65 },
]

const customerColumns: ColumnDef<CustomerSegment>[] = [
  { accessorKey: 'segment', header: 'Segment', cell: ({ row }) => <span className="font-medium">{row.getValue('segment')}</span> },
  { accessorKey: 'customers', header: 'Customers', cell: ({ row }) => <span className="text-center">{row.getValue('customers')}</span> },
  { accessorKey: 'revenue', header: 'Revenue', cell: ({ row }) => <span className="font-medium">${(row.getValue('revenue') as number).toLocaleString()}</span> },
  { accessorKey: 'avgOrder', header: 'Avg Order', cell: ({ row }) => <span>${(row.getValue('avgOrder') as number).toLocaleString()}</span> },
  {
    accessorKey: 'retention',
    header: 'Retention %',
    cell: ({ row }) => {
      const retention = row.getValue('retention') as number
      const colorClass = retention >= 90 ? 'text-green-600' : retention >= 80 ? 'text-amber-600' : 'text-red-600'
      return <span className={`font-medium ${colorClass}`}>{retention}%</span>
    },
  },
]

export function AnalyticsPage() {
  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Analytics</h1>
          <p className="text-muted-foreground">Business intelligence and performance metrics</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" size="sm">
            <Calendar className="h-4 w-4 mr-2" />
            Last 30 Days
          </Button>
          <Button variant="outline" size="sm">
            <Download className="h-4 w-4 mr-2" />
            Export
          </Button>
        </div>
      </div>

      {/* KPI Cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <KPICard
          title="Total Revenue"
          value="$1.08M"
          change={18.2}
          trend="up"
          changeLabel="vs last period"
          icon={<DollarSign className="h-5 w-5" />}
        />
        <KPICard
          title="Growth Rate"
          value="24.5%"
          change={4.3}
          trend="up"
          changeLabel="vs last year"
          icon={<TrendingUp className="h-5 w-5" />}
        />
        <KPICard
          title="Active Customers"
          value="740"
          change={56}
          trend="up"
          changeLabel="new this month"
          icon={<Users className="h-5 w-5" />}
        />
        <KPICard
          title="Total Orders"
          value="1,228"
          change={12.8}
          trend="up"
          changeLabel="vs last period"
          icon={<Package className="h-5 w-5" />}
        />
      </div>

      {/* Tabs for different views */}
      <Tabs defaultValue="overview" className="w-full">
        <TabsList>
          <TabsTrigger value="overview" className="gap-2">
            <BarChart3 className="h-4 w-4" />
            Overview
          </TabsTrigger>
          <TabsTrigger value="sales" className="gap-2">
            <DollarSign className="h-4 w-4" />
            Sales
          </TabsTrigger>
          <TabsTrigger value="operations" className="gap-2">
            <Package className="h-4 w-4" />
            Operations
          </TabsTrigger>
          <TabsTrigger value="customers" className="gap-2">
            <Users className="h-4 w-4" />
            Customers
          </TabsTrigger>
        </TabsList>

        <TabsContent value="overview" className="mt-6 space-y-6">
          {/* Overview Charts */}
          <div className="grid gap-4 md:grid-cols-2">
            <ChartCard title="Revenue Trend" subtitle="Monthly revenue performance">
              <OrdersChart data={revenueData.map(d => ({ month: d.month, orders: d.revenue / 1000, completed: d.orders * 0.8 }))} />
            </ChartCard>
            <ChartCard title="Order Fulfillment" subtitle="Orders vs Completed">
              <OrdersChart data={ordersData} />
            </ChartCard>
          </div>

          {/* Top Products Table */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <div>
                <CardTitle>Top Performing Products</CardTitle>
                <p className="text-sm text-muted-foreground mt-1">By revenue and growth</p>
              </div>
              <Button variant="outline" size="sm">
                <Filter className="h-4 w-4 mr-2" />
                Filter
              </Button>
            </CardHeader>
            <CardContent>
              <DataTable
                data={topProducts}
                columns={topProductColumns}
                enablePagination={true}
                pageSize={5}
              />
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="sales" className="mt-6 space-y-6">
          {/* Sales Charts */}
          <div className="grid gap-4 md:grid-cols-2">
            <ChartCard title="Sales by Region" subtitle="Revenue distribution">
              <ProductionPieChart data={salesByRegion} />
            </ChartCard>
            <ChartCard title="Product Categories" subtitle="Sales breakdown">
              <ProductionPieChart data={productCategories} />
            </ChartCard>
          </div>

          {/* Revenue Trend */}
          <Card>
            <CardHeader>
              <CardTitle>Revenue Analysis</CardTitle>
              <p className="text-sm text-muted-foreground mt-1">6-month revenue trend</p>
            </CardHeader>
            <CardContent>
              <div className="h-80">
                <OrdersChart data={revenueData.map(d => ({ month: d.month, orders: d.revenue / 1000, completed: d.revenue / 1200 }))} />
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="operations" className="mt-6 space-y-6">
          {/* Operations Charts */}
          <div className="grid gap-4 md:grid-cols-2">
            <ChartCard title="Weekly Distribution" subtitle="Outgoing vs Incoming">
              <DistributionChart data={distributionData} />
            </ChartCard>
            <Card>
              <CardHeader>
                <CardTitle>Operational Metrics</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center justify-between">
                  <span className="text-muted-foreground">Order Processing Time</span>
                  <span className="text-2xl font-bold">2.4 days</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-muted-foreground">Fulfillment Rate</span>
                  <span className="text-2xl font-bold text-green-600">96.8%</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-muted-foreground">Return Rate</span>
                  <span className="text-2xl font-bold text-amber-600">3.2%</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-muted-foreground">Inventory Turnover</span>
                  <span className="text-2xl font-bold">8.5x</span>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="customers" className="mt-6 space-y-6">
          {/* Customer Segments Table */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <div>
                <CardTitle>Customer Segments</CardTitle>
                <p className="text-sm text-muted-foreground mt-1">Performance by segment</p>
              </div>
              <Button variant="outline" size="sm">View All Customers</Button>
            </CardHeader>
            <CardContent>
              <DataTable
                data={customerSegments}
                columns={customerColumns}
                enablePagination={true}
                pageSize={5}
              />
            </CardContent>
          </Card>

          {/* Customer Charts */}
          <div className="grid gap-4 md:grid-cols-2">
            <ChartCard title="Customer Distribution" subtitle="By segment">
              <ProductionPieChart data={[
                { name: 'Enterprise', value: 45, color: 'hsl(var(--primary))' },
                { name: 'Mid-Market', value: 180, color: 'hsl(var(--info))' },
                { name: 'Small Business', value: 420, color: 'hsl(var(--warning))' },
                { name: 'Startup', value: 95, color: 'hsl(var(--muted))' },
              ]} />
            </ChartCard>
            <Card>
              <CardHeader>
                <CardTitle>Customer Metrics</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center justify-between">
                  <span className="text-muted-foreground">Customer Lifetime Value</span>
                  <span className="text-2xl font-bold">$24.5K</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-muted-foreground">Net Promoter Score</span>
                  <span className="text-2xl font-bold text-green-600">72</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-muted-foreground">Churn Rate</span>
                  <span className="text-2xl font-bold text-amber-600">5.2%</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-muted-foreground">Support Tickets</span>
                  <span className="text-2xl font-bold">128</span>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>
    </div>
  )
}
