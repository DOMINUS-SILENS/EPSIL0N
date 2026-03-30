import { KPICard } from '@/design-system/primitives/KPICard/KPICard'
import { DataTable, StatusBadge, Column } from '@/design-system/primitives/DataTable/DataTable'
import { ChartCard, DistributionChart } from '@/design-system/primitives/Charts/Charts'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/design-system/primitives/Button/Button'
import {
  Truck,
  Package,
  Warehouse,
  MapPin,
  Clock,
  CheckCircle,
  AlertTriangle,
  Plus,
  Filter,
  TrendingUp,
} from 'lucide-react'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/design-system/primitives/Tabs/Tabs'

const distributionData = [
  { day: 'Mon', outgoing: 45, incoming: 32 },
  { day: 'Tue', outgoing: 52, incoming: 38 },
  { day: 'Wed', outgoing: 48, incoming: 42 },
  { day: 'Thu', outgoing: 61, incoming: 35 },
  { day: 'Fri', outgoing: 55, incoming: 40 },
  { day: 'Sat', outgoing: 38, incoming: 28 },
  { day: 'Sun', outgoing: 25, incoming: 18 },
]

interface Shipment {
  [key: string]: unknown
  id: string
  origin: string
  destination: string
  customer: string
  items: number
  status: string
  carrier: string
  eta: string
  tracking: string
}

const shipments: Shipment[] = [
  { id: 'SHP-001', origin: 'Warehouse A', destination: 'New York, NY', customer: 'Acme Corp', items: 12, status: 'in-transit', carrier: 'FedEx', eta: 'Mar 28', tracking: 'FX123456789' },
  { id: 'SHP-002', origin: 'Warehouse B', destination: 'San Francisco, CA', customer: 'TechStart Inc', items: 5, status: 'delivered', carrier: 'UPS', eta: 'Mar 27', tracking: 'UP987654321' },
  { id: 'SHP-003', origin: 'Warehouse A', destination: 'Chicago, IL', customer: 'Global Trade', items: 20, status: 'in-transit', carrier: 'DHL', eta: 'Mar 29', tracking: 'DH456789012' },
  { id: 'SHP-004', origin: 'Warehouse C', destination: 'Austin, TX', customer: 'Summit Group', items: 8, status: 'pending', carrier: 'FedEx', eta: 'Mar 30', tracking: '-' },
  { id: 'SHP-005', origin: 'Warehouse B', destination: 'Seattle, WA', customer: 'Prime Solutions', items: 15, status: 'processing', carrier: 'UPS', eta: 'Mar 31', tracking: '-' },
]

const shipmentColumns: Column<Shipment>[] = [
  { key: 'id', header: 'Shipment ID', className: 'font-mono text-sm' },
  {
    key: 'route',
    header: 'Route',
    render: (item) => (
      <div className="text-sm">
        <p className="font-medium">{item.origin}</p>
        <p className="text-neutral-500 flex items-center gap-1">
          <TrendingUp className="h-3 w-3" />
          {item.destination}
        </p>
      </div>
    ),
  },
  { key: 'customer', header: 'Customer', className: 'font-medium' },
  { key: 'items', header: 'Items', className: 'text-center' },
  { key: 'carrier', header: 'Carrier' },
  { key: 'status', header: 'Status', render: (item) => <StatusBadge status={item.status === 'in-transit' ? 'shipped' : item.status} /> },
  { key: 'eta', header: 'ETA', className: 'text-neutral-500' },
  { key: 'tracking', header: 'Tracking', className: 'font-mono text-xs text-neutral-500' },
]

interface WarehouseData {
  [key: string]: unknown
  id: string
  name: string
  location: string
  capacity: number
  used: number
  status: string
  manager: string
  shipments: number
}

const warehouses: WarehouseData[] = [
  { id: 'WH-A', name: 'Warehouse A', location: 'Newark, NJ', capacity: 50000, used: 35000, status: 'active', manager: 'John Miller', shipments: 156 },
  { id: 'WH-B', name: 'Warehouse B', location: 'Los Angeles, CA', capacity: 75000, used: 62000, status: 'active', manager: 'Sarah Lee', shipments: 234 },
  { id: 'WH-C', name: 'Warehouse C', location: 'Dallas, TX', capacity: 40000, used: 28000, status: 'active', manager: 'Mike Chen', shipments: 98 },
]

const warehouseColumns: Column<WarehouseData>[] = [
  { key: 'id', header: 'ID', className: 'font-mono text-sm' },
  { key: 'name', header: 'Name', className: 'font-medium' },
  { key: 'location', header: 'Location' },
  {
    key: 'capacity',
    header: 'Capacity Usage',
    render: (item) => {
      const percentage = (item.used / item.capacity) * 100
      return (
        <div className="space-y-1 min-w-[120px]">
          <div className={`h-2 bg-neutral-200 rounded-full overflow-hidden`}>
            <div
              className={`h-full rounded-full transition-all ${percentage > 85 ? 'bg-amber-500' : 'bg-primary'}`}
              style={{ width: `${percentage}%` }}
            />
          </div>
          <p className="text-xs text-neutral-500">
            {item.used.toLocaleString()} / {item.capacity.toLocaleString()} sqft ({percentage.toFixed(0)}%)
          </p>
        </div>
      )
    },
  },
  { key: 'status', header: 'Status', render: (item) => <StatusBadge status={item.status} /> },
  { key: 'manager', header: 'Manager' },
  { key: 'shipments', header: 'Monthly Shipments', className: 'text-center font-medium' },
]

interface Delivery {
  [key: string]: unknown
  id: string
  shipmentId: string
  customer: string
  address: string
  driver: string
  status: string
  scheduledTime: string
  actualTime: string
}

const deliveries: Delivery[] = [
  { id: 'DEL-001', shipmentId: 'SHP-002', customer: 'TechStart Inc', address: '123 Market St, San Francisco', driver: 'Tom Wilson', status: 'delivered', scheduledTime: '10:00 AM', actualTime: '9:45 AM' },
  { id: 'DEL-002', shipmentId: 'SHP-006', customer: 'DataFlow Systems', address: '456 Congress Ave, Boston', driver: 'James Brown', status: 'in-progress', scheduledTime: '2:00 PM', actualTime: '-' },
  { id: 'DEL-003', shipmentId: 'SHP-007', customer: 'Quantum Labs', address: '789 Ocean Dr, Miami', driver: 'Maria Garcia', status: 'pending', scheduledTime: '4:30 PM', actualTime: '-' },
  { id: 'DEL-004', shipmentId: 'SHP-008', customer: 'Vertex Holdings', address: '321 5th Ave, Denver', driver: 'Robert Lee', status: 'pending', scheduledTime: 'Tomorrow', actualTime: '-' },
]

const deliveryColumns: Column<Delivery>[] = [
  { key: 'id', header: 'Delivery ID', className: 'font-mono text-sm' },
  { key: 'shipmentId', header: 'Shipment', className: 'font-mono text-xs text-neutral-500' },
  { key: 'customer', header: 'Customer', className: 'font-medium' },
  { key: 'address', header: 'Address', className: 'text-neutral-500 max-w-[200px] truncate' },
  { key: 'driver', header: 'Driver' },
  { key: 'status', header: 'Status', render: (item) => <StatusBadge status={item.status} /> },
  { key: 'scheduledTime', header: 'Scheduled', className: 'text-center' },
  { key: 'actualTime', header: 'Actual', className: 'text-center text-neutral-500' },
]

export function DistributionPage() {
  const inTransit = shipments.filter((s) => s.status === 'in-transit').length
  const delivered = shipments.filter((s) => s.status === 'delivered').length
  const pending = shipments.filter((s) => s.status === 'pending' || s.status === 'processing').length

  return (
    <div className="p-6 space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Distribution</h1>
          <p className="text-neutral-500">Logistics, shipping, and warehouse management</p>
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <KPICard
          title="Active Shipments"
          value="47"
          change={12.5}
          trend="up"
          icon={<Truck className="h-5 w-5" />}
        />
        <KPICard
          title="On-Time Delivery"
          value="96.8%"
          change={2.3}
          trend="up"
          icon={<CheckCircle className="h-5 w-5" />}
        />
        <KPICard
          title="Avg. Transit Time"
          value="2.4 days"
          change={-8.5}
          trend="up"
          icon={<Clock className="h-5 w-5" />}
        />
        <KPICard
          title="Warehouse Utilization"
          value="78%"
          change={5.2}
          trend="up"
          icon={<Warehouse className="h-5 w-5" />}
        />
      </div>

      <div className="grid gap-4 lg:grid-cols-3">
        <ChartCard
          title="Shipment Flow"
          subtitle="Outgoing vs incoming shipments (last 7 days)"
          className="lg:col-span-2"
        >
          <DistributionChart data={distributionData} />
        </ChartCard>

        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-base font-semibold">Shipment Summary</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <div className="h-3 w-3 rounded-full bg-blue-500" />
                  <span className="text-sm text-neutral-500">In Transit</span>
                </div>
                <span className="font-semibold">{inTransit}</span>
              </div>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <div className="h-3 w-3 rounded-full bg-green-500" />
                  <span className="text-sm text-neutral-500">Delivered</span>
                </div>
                <span className="font-semibold">{delivered}</span>
              </div>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <div className="h-3 w-3 rounded-full bg-amber-500" />
                  <span className="text-sm text-neutral-500">Pending/Processing</span>
                </div>
                <span className="font-semibold">{pending}</span>
              </div>
            </div>

            <div className="mt-6 pt-4 border-t border-neutral-200">
              <h4 className="text-sm font-medium mb-3">Carrier Performance</h4>
              <div className="space-y-3">
                {['FedEx', 'UPS', 'DHL'].map((carrier) => (
                  <div key={carrier} className="space-y-1">
                    <div className="flex items-center justify-between text-sm">
                      <span>{carrier}</span>
                      <span className="text-neutral-500">{Math.floor(Math.random() * 10 + 90)}% on-time</span>
                    </div>
                    <div className="h-1.5 bg-neutral-200 rounded-full overflow-hidden">
                      <div
                        className="h-full bg-primary rounded-full"
                        style={{ width: `${Math.floor(Math.random() * 10 + 90)}%` }}
                      />
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <Card className="border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-900/10">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-900/50">
                <AlertTriangle className="h-5 w-5" />
              </div>
              <div>
                <p className="font-medium">Delayed Shipments</p>
                <p className="text-sm text-neutral-500">3 shipments running behind schedule</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card className="border-blue-200 bg-blue-50 dark:border-blue-900 dark:bg-blue-900/10">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/50">
                <MapPin className="h-5 w-5" />
              </div>
              <div>
                <p className="font-medium">Route Optimization</p>
                <p className="text-sm text-neutral-500">12 routes optimized today, saving 15% fuel</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <Tabs defaultValue="shipments" className="space-y-4">
        <div className="flex items-center justify-between">
          <TabsList className="bg-neutral-100 dark:bg-neutral-800">
            <TabsTrigger value="shipments" className="gap-2">
              <Truck className="h-4 w-4" />
              Shipments
            </TabsTrigger>
            <TabsTrigger value="warehouses" className="gap-2">
              <Warehouse className="h-4 w-4" />
              Warehouses
            </TabsTrigger>
            <TabsTrigger value="deliveries" className="gap-2">
              <Package className="h-4 w-4" />
              Deliveries
            </TabsTrigger>
            <TabsTrigger value="routes" className="gap-2">
              <MapPin className="h-4 w-4" />
              Routes
            </TabsTrigger>
          </TabsList>
          <div className="flex items-center gap-2">
            <Button variant="outline" size="sm" className="gap-2">
              <Filter className="h-4 w-4" />
              Filter
            </Button>
            <Button size="sm" className="gap-2">
              <Plus className="h-4 w-4" />
              New Shipment
            </Button>
          </div>
        </div>

        <TabsContent value="shipments">
          <Card>
            <CardContent className="p-0">
              <DataTable data={shipments} columns={shipmentColumns} />
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="warehouses">
          <Card>
            <CardContent className="p-0">
              <DataTable data={warehouses} columns={warehouseColumns} />
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="deliveries">
          <Card>
            <CardContent className="p-0">
              <DataTable data={deliveries} columns={deliveryColumns} />
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="routes">
          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-center h-48 text-neutral-500">
                <div className="text-center">
                  <MapPin className="h-12 w-12 mx-auto mb-3 opacity-50" />
                  <p>Route planning and optimization</p>
                  <p className="text-sm">Map view coming soon</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  )
}
