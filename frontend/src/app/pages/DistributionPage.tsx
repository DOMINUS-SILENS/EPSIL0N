import { KPICard } from '@/design-system/primitives/KPICard/KPICard'
import { DataTable } from '@/design-system/composite/DataTable/DataTable'
import { ChartCard, DistributionChart } from '@/components/erp/charts'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  Truck,
  Package,
  MapPin,
  Clock,
  CheckCircle,
  AlertTriangle,
  Plus,
  Filter,
  Calendar,
} from 'lucide-react'
import type { ColumnDef } from '@tanstack/react-table'

// Sample data
const weeklyDistribution = [
  { day: 'Mon', outgoing: 45, incoming: 32 },
  { day: 'Tue', outgoing: 52, incoming: 28 },
  { day: 'Wed', outgoing: 38, incoming: 41 },
  { day: 'Thu', outgoing: 65, incoming: 35 },
  { day: 'Fri', outgoing: 48, incoming: 29 },
  { day: 'Sat', outgoing: 12, incoming: 15 },
  { day: 'Sun', outgoing: 8, incoming: 10 },
]

interface Shipment {
  [key: string]: unknown
  id: string
  orderId: string
  customer: string
  destination: string
  status: string
  carrier: string
  trackingNumber: string
  estimatedDelivery: string
  items: number
}

const shipments: Shipment[] = [
  { id: 'SH-2024-001', orderId: 'ORD-4521', customer: 'Acme Corp', destination: 'New York, NY', status: 'in-transit', carrier: 'FedEx', trackingNumber: 'FX789456123', estimatedDelivery: 'Mar 30', items: 5 },
  { id: 'SH-2024-002', orderId: 'ORD-4522', customer: 'Tech Solutions', destination: 'Los Angeles, CA', status: 'delivered', carrier: 'UPS', trackingNumber: 'UPS123789', estimatedDelivery: 'Mar 28', items: 12 },
  { id: 'SH-2024-003', orderId: 'ORD-4523', customer: 'Global Industries', destination: 'Chicago, IL', status: 'pending', carrier: 'FedEx', trackingNumber: 'FX789456124', estimatedDelivery: 'Apr 2', items: 3 },
  { id: 'SH-2024-004', orderId: 'ORD-4524', customer: 'Metro Supplies', destination: 'Houston, TX', status: 'in-transit', carrier: 'DHL', trackingNumber: 'DHL456789', estimatedDelivery: 'Mar 31', items: 8 },
  { id: 'SH-2024-005', orderId: 'ORD-4525', customer: 'Sunrise Logistics', destination: 'Phoenix, AZ', status: 'exception', carrier: 'UPS', trackingNumber: 'UPS123790', estimatedDelivery: 'Delayed', items: 2 },
  { id: 'SH-2024-006', orderId: 'ORD-4526', customer: 'Coastal Trading', destination: 'Seattle, WA', status: 'delivered', carrier: 'FedEx', trackingNumber: 'FX789456125', estimatedDelivery: 'Mar 27', items: 15 },
]

const shipmentColumns: ColumnDef<Shipment>[] = [
  { accessorKey: 'id', header: 'Shipment ID', cell: ({ row }) => <span className="font-mono text-sm">{row.getValue('id')}</span> },
  { accessorKey: 'orderId', header: 'Order', cell: ({ row }) => <span className="font-mono text-sm">{row.getValue('orderId')}</span> },
  { accessorKey: 'customer', header: 'Customer', cell: ({ row }) => <span className="font-medium">{row.getValue('customer')}</span> },
  { accessorKey: 'destination', header: 'Destination', cell: ({ row }) => <span className="text-muted-foreground flex items-center gap-1"><MapPin className="h-3 w-3" />{row.getValue('destination')}</span> },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) => {
      const status = row.getValue('status') as string
      const variants: Record<string, { className: string; label: string }> = {
        'in-transit': { className: 'bg-blue-100 text-blue-800 border-blue-200', label: 'In Transit' },
        delivered: { className: 'bg-green-100 text-green-800 border-green-200', label: 'Delivered' },
        pending: { className: 'bg-amber-100 text-amber-800 border-amber-200', label: 'Pending' },
        exception: { className: 'bg-red-100 text-red-800 border-red-200', label: 'Exception' },
      }
      const variant = variants[status] || { className: 'bg-gray-100 text-gray-800', label: status }
      return <Badge variant="outline" className={`text-xs ${variant.className}`}>{variant.label}</Badge>
    },
  },
  { accessorKey: 'carrier', header: 'Carrier', cell: ({ row }) => <span className="text-muted-foreground">{row.getValue('carrier')}</span> },
  { accessorKey: 'trackingNumber', header: 'Tracking', cell: ({ row }) => <span className="font-mono text-sm">{row.getValue('trackingNumber')}</span> },
  { accessorKey: 'estimatedDelivery', header: 'Est. Delivery', cell: ({ row }) => <span className="text-muted-foreground flex items-center gap-1"><Calendar className="h-3 w-3" />{row.getValue('estimatedDelivery')}</span> },
]

interface DeliveryRoute {
  [key: string]: unknown
  route: string
  origin: string
  destination: string
  distance: string
  avgTime: string
  shipments: number
  onTimeRate: string
}

const deliveryRoutes: DeliveryRoute[] = [
  { route: 'NYC-LAX', origin: 'New York', destination: 'Los Angeles', distance: '2,789 mi', avgTime: '5 days', shipments: 145, onTimeRate: '94%' },
  { route: 'CHI-HOU', origin: 'Chicago', destination: 'Houston', distance: '1,085 mi', avgTime: '3 days', shipments: 98, onTimeRate: '96%' },
  { route: 'SEA-MIA', origin: 'Seattle', destination: 'Miami', distance: '3,300 mi', avgTime: '6 days', shipments: 76, onTimeRate: '92%' },
  { route: 'PHX-DEN', origin: 'Phoenix', destination: 'Denver', distance: '862 mi', avgTime: '2 days', shipments: 112, onTimeRate: '98%' },
]

const routeColumns: ColumnDef<DeliveryRoute>[] = [
  { accessorKey: 'route', header: 'Route', cell: ({ row }) => <span className="font-mono text-sm font-medium">{row.getValue('route')}</span> },
  { accessorKey: 'origin', header: 'Origin', cell: ({ row }) => <span className="text-muted-foreground">{row.getValue('origin')}</span> },
  { accessorKey: 'destination', header: 'Destination', cell: ({ row }) => <span className="text-muted-foreground">{row.getValue('destination')}</span> },
  { accessorKey: 'distance', header: 'Distance', cell: ({ row }) => <span>{row.getValue('distance')}</span> },
  { accessorKey: 'avgTime', header: 'Avg Time', cell: ({ row }) => <span>{row.getValue('avgTime')}</span> },
  { accessorKey: 'shipments', header: 'Shipments', cell: ({ row }) => <span className="text-center font-medium">{row.getValue('shipments')}</span> },
  { accessorKey: 'onTimeRate', header: 'On-Time %', cell: ({ row }) => <span className="text-green-600 font-medium">{row.getValue('onTimeRate')}</span> },
]

export function DistributionPage() {
  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Distribution</h1>
          <p className="text-muted-foreground">Manage shipments and logistics</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" size="sm">
            <Filter className="h-4 w-4 mr-2" />
            Filter
          </Button>
          <Button size="sm">
            <Plus className="h-4 w-4 mr-2" />
            New Shipment
          </Button>
        </div>
      </div>

      {/* KPI Cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <KPICard
          title="Active Shipments"
          value="156"
          change={12}
          trend="up"
          changeLabel="vs last week"
          icon={<Truck className="h-5 w-5" />}
        />
        <KPICard
          title="Delivered Today"
          value="23"
          change={5}
          trend="up"
          changeLabel="vs yesterday"
          icon={<CheckCircle className="h-5 w-5" />}
        />
        <KPICard
          title="Avg Delivery Time"
          value="3.2 days"
          change={-0.5}
          trend="up"
          changeLabel="improvement"
          icon={<Clock className="h-5 w-5" />}
        />
        <KPICard
          title="Delivery Exceptions"
          value="2"
          change={-1}
          trend="up"
          changeLabel="vs last week"
          icon={<AlertTriangle className="h-5 w-5" />}
        />
      </div>

      {/* Charts */}
      <div className="grid gap-4 md:grid-cols-3">
        <ChartCard title="Weekly Distribution" subtitle="Outgoing vs Incoming" className="md:col-span-2">
          <DistributionChart data={weeklyDistribution} />
        </ChartCard>
        <Card className="md:col-span-1">
          <CardHeader>
            <CardTitle>Quick Stats</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex items-center justify-between">
              <span className="text-muted-foreground">On-Time Rate</span>
              <span className="text-2xl font-bold text-green-600">94.5%</span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-muted-foreground">Pending Pickup</span>
              <span className="text-2xl font-bold">18</span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-muted-foreground">In Transit</span>
              <span className="text-2xl font-bold">89</span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-muted-foreground">Out for Delivery</span>
              <span className="text-2xl font-bold">47</span>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Tabs for Shipments and Routes */}
      <Tabs defaultValue="shipments" className="w-full">
        <TabsList>
          <TabsTrigger value="shipments">
            <Package className="h-4 w-4 mr-2" />
            Shipments
          </TabsTrigger>
          <TabsTrigger value="routes">
            <Truck className="h-4 w-4 mr-2" />
            Delivery Routes
          </TabsTrigger>
        </TabsList>
        <TabsContent value="shipments" className="mt-4">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <div>
                <CardTitle>Active Shipments</CardTitle>
                <p className="text-sm text-muted-foreground mt-1">Track and manage all shipments</p>
              </div>
              <Button variant="outline" size="sm">Export</Button>
            </CardHeader>
            <CardContent>
              <DataTable
                data={shipments}
                columns={shipmentColumns}
                enablePagination={true}
                pageSize={10}
              />
            </CardContent>
          </Card>
        </TabsContent>
        <TabsContent value="routes" className="mt-4">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <div>
                <CardTitle>Delivery Routes</CardTitle>
                <p className="text-sm text-muted-foreground mt-1">Performance by route</p>
              </div>
              <Button variant="outline" size="sm">Route Optimization</Button>
            </CardHeader>
            <CardContent>
              <DataTable
                data={deliveryRoutes}
                columns={routeColumns}
                enablePagination={true}
                pageSize={5}
              />
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  )
}
