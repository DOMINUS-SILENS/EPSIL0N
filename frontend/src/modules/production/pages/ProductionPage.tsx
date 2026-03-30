import { KPICard } from '@/design-system/primitives/KPICard/KPICard'
import { DataTable, StatusBadge, Column } from '@/design-system/primitives/DataTable/DataTable'
import { ChartCard, OrdersChart, ProductionPieChart } from '@/design-system/primitives/Charts/Charts'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/design-system/primitives/Button/Button'
import {
  Factory,
  Package,
  ClipboardList,
  Calendar,
  BarChart3,
  Plus,
  Filter,
  AlertTriangle,
  CheckCircle,
  Clock,
} from 'lucide-react'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/design-system/primitives/Tabs/Tabs'

const productionData = [
  { name: 'Completed', value: 65, color: '#10b981' },
  { name: 'In Progress', value: 25, color: '#3b82f6' },
  { name: 'Pending', value: 10, color: '#f59e0b' },
]

const monthlyOutput = [
  { month: 'Jan', orders: 580, completed: 545 },
  { month: 'Feb', orders: 620, completed: 590 },
  { month: 'Mar', orders: 710, completed: 680 },
  { month: 'Apr', orders: 685, completed: 650 },
  { month: 'May', orders: 750, completed: 720 },
  { month: 'Jun', orders: 820, completed: 785 },
]

interface WorkOrder {
  [key: string]: unknown
  id: string
  product: string
  quantity: number
  status: string
  priority: string
  startDate: string
  dueDate: string
  completion: number
}

const workOrders: WorkOrder[] = [
  { id: 'WO-001', product: 'Assembly A-100', quantity: 500, status: 'in-progress', priority: 'High', startDate: 'Mar 20', dueDate: 'Mar 30', completion: 65 },
  { id: 'WO-002', product: 'Component B-200', quantity: 1000, status: 'in-progress', priority: 'Medium', startDate: 'Mar 22', dueDate: 'Apr 2', completion: 40 },
  { id: 'WO-003', product: 'Module C-300', quantity: 250, status: 'pending', priority: 'High', startDate: 'Mar 28', dueDate: 'Apr 5', completion: 0 },
  { id: 'WO-004', product: 'Part D-400', quantity: 2000, status: 'completed', priority: 'Low', startDate: 'Mar 15', dueDate: 'Mar 25', completion: 100 },
  { id: 'WO-005', product: 'Assembly A-100', quantity: 750, status: 'in-progress', priority: 'Medium', startDate: 'Mar 24', dueDate: 'Apr 8', completion: 25 },
]

interface InventoryItem {
  [key: string]: unknown
  sku: string
  name: string
  category: string
  quantity: number
  minStock: number
  status: string
  location: string
}

const inventoryItems: InventoryItem[] = [
  { sku: 'SKU-001', name: 'Raw Material A', category: 'Raw Materials', quantity: 1500, minStock: 500, status: 'in-stock', location: 'Warehouse A' },
  { sku: 'SKU-002', name: 'Component X', category: 'Components', quantity: 320, minStock: 400, status: 'low', location: 'Warehouse B' },
  { sku: 'SKU-003', name: 'Assembly Part Y', category: 'Parts', quantity: 850, minStock: 200, status: 'in-stock', location: 'Warehouse A' },
  { sku: 'SKU-004', name: 'Finished Product Z', category: 'Finished Goods', quantity: 45, minStock: 100, status: 'low', location: 'Warehouse C' },
  { sku: 'SKU-005', name: 'Packaging Material', category: 'Packaging', quantity: 5000, minStock: 1000, status: 'in-stock', location: 'Warehouse B' },
]

interface QualityCheck {
  [key: string]: unknown
  id: string
  workOrder: string
  product: string
  inspector: string
  result: string
  date: string
  defects: number
}

const qualityChecks: QualityCheck[] = [
  { id: 'QC-001', workOrder: 'WO-004', product: 'Part D-400', inspector: 'John Smith', result: 'passed', date: 'Mar 25', defects: 0 },
  { id: 'QC-002', workOrder: 'WO-001', product: 'Assembly A-100', inspector: 'Sarah Lee', result: 'passed', date: 'Mar 26', defects: 2 },
  { id: 'QC-003', workOrder: 'WO-002', product: 'Component B-200', inspector: 'Mike Chen', result: 'pending', date: 'Mar 27', defects: 0 },
  { id: 'QC-004', workOrder: 'WO-001', product: 'Assembly A-100', inspector: 'John Smith', result: 'failed', date: 'Mar 27', defects: 8 },
]

const workOrderColumns: Column<WorkOrder>[] = [
  { key: 'id', header: 'Work Order', className: 'font-mono text-sm' },
  { key: 'product', header: 'Product', className: 'font-medium' },
  { key: 'quantity', header: 'Qty', className: 'text-center' },
  { key: 'status', header: 'Status', render: (item) => <StatusBadge status={item.status} /> },
  {
    key: 'priority',
    header: 'Priority',
    render: (item) => (
      <span className={`text-sm font-medium ${
        item.priority === 'High' ? 'text-red-600' :
        item.priority === 'Medium' ? 'text-amber-600' : 'text-neutral-500'
      }`}>
        {item.priority}
      </span>
    ),
  },
  { key: 'dueDate', header: 'Due Date', className: 'text-neutral-500' },
  {
    key: 'completion',
    header: 'Progress',
    render: (item) => (
      <div className="flex items-center gap-2 min-w-[120px]">
        <div className="h-2 flex-1 bg-neutral-200 rounded-full overflow-hidden">
          <div
            className="h-full bg-primary rounded-full transition-all"
            style={{ width: `${item.completion}%` }}
          />
        </div>
        <span className="text-xs text-neutral-500 w-8">{item.completion}%</span>
      </div>
    ),
  },
]

const inventoryColumns: Column<InventoryItem>[] = [
  { key: 'sku', header: 'SKU', className: 'font-mono text-sm' },
  { key: 'name', header: 'Item Name', className: 'font-medium' },
  { key: 'category', header: 'Category' },
  { key: 'quantity', header: 'Qty', className: 'text-right' },
  { key: 'minStock', header: 'Min Stock', className: 'text-right text-neutral-500' },
  { key: 'status', header: 'Status', render: (item) => <StatusBadge status={item.status} /> },
  { key: 'location', header: 'Location', className: 'text-neutral-500' },
]

const qualityColumns: Column<QualityCheck>[] = [
  { key: 'id', header: 'QC ID', className: 'font-mono text-sm' },
  { key: 'workOrder', header: 'Work Order' },
  { key: 'product', header: 'Product', className: 'font-medium' },
  { key: 'inspector', header: 'Inspector' },
  { key: 'result', header: 'Result', render: (item) => <StatusBadge status={item.result} /> },
  { key: 'defects', header: 'Defects', className: 'text-center' },
  { key: 'date', header: 'Date', className: 'text-neutral-500' },
]

export function ProductionPage() {
  return (
    <div className="p-6 space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Production</h1>
          <p className="text-neutral-500">Manufacturing planning, execution, and quality control</p>
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <KPICard
          title="Daily Output"
          value="1,247"
          change={8.5}
          trend="up"
          changeLabel="vs yesterday"
          icon={<Factory className="h-5 w-5" />}
        />
        <KPICard
          title="Active Work Orders"
          value="23"
          change={-2}
          trend="down"
          changeLabel="vs last week"
          icon={<ClipboardList className="h-5 w-5" />}
        />
        <KPICard
          title="Efficiency Rate"
          value="94.2%"
          change={1.8}
          trend="up"
          changeLabel="vs last month"
          icon={<BarChart3 className="h-5 w-5" />}
        />
        <KPICard
          title="Quality Pass Rate"
          value="97.5%"
          change={0.5}
          trend="up"
          changeLabel="vs last month"
          icon={<CheckCircle className="h-5 w-5" />}
        />
      </div>

      <div className="grid gap-4 lg:grid-cols-3">
        <ChartCard className="lg:col-span-2" title="Monthly Production Output" subtitle="Orders vs Completed">
          <OrdersChart data={monthlyOutput} />
        </ChartCard>

        <ChartCard title="Production Status" subtitle="Current distribution">
          <ProductionPieChart data={productionData} />
          <div className="mt-4 space-y-2">
            {productionData.map((item) => (
              <div key={item.name} className="flex items-center justify-between text-sm">
                <div className="flex items-center gap-2">
                  <div className="h-3 w-3 rounded-full" style={{ backgroundColor: item.color }} />
                  <span className="text-neutral-500">{item.name}</span>
                </div>
                <span className="font-medium">{item.value}%</span>
              </div>
            ))}
          </div>
        </ChartCard>
      </div>

      <div className="grid gap-4 md:grid-cols-3">
        <Card className="border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-900/10">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-900/50">
                <AlertTriangle className="h-5 w-5" />
              </div>
              <div>
                <p className="font-medium">Low Stock Alert</p>
                <p className="text-sm text-neutral-500">2 items below minimum</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card className="border-blue-200 bg-blue-50 dark:border-blue-900 dark:bg-blue-900/10">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/50">
                <Clock className="h-5 w-5" />
              </div>
              <div>
                <p className="font-medium">Pending Approvals</p>
                <p className="text-sm text-neutral-500">5 work orders waiting</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card className="border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-900/10">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-red-100 text-red-600 dark:bg-red-900/50">
                <AlertTriangle className="h-5 w-5" />
              </div>
              <div>
                <p className="font-medium">Quality Issues</p>
                <p className="text-sm text-neutral-500">1 batch failed QC</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <Tabs defaultValue="work-orders" className="space-y-4">
        <div className="flex items-center justify-between">
          <TabsList className="bg-neutral-100 dark:bg-neutral-800">
            <TabsTrigger value="work-orders" className="gap-2">
              <ClipboardList className="h-4 w-4" />
              Work Orders
            </TabsTrigger>
            <TabsTrigger value="inventory" className="gap-2">
              <Package className="h-4 w-4" />
              Inventory
            </TabsTrigger>
            <TabsTrigger value="quality" className="gap-2">
              <BarChart3 className="h-4 w-4" />
              Quality Control
            </TabsTrigger>
            <TabsTrigger value="planning" className="gap-2">
              <Calendar className="h-4 w-4" />
              Planning
            </TabsTrigger>
          </TabsList>
          <div className="flex items-center gap-2">
            <Button variant="outline" size="sm" className="gap-2">
              <Filter className="h-4 w-4" />
              Filter
            </Button>
            <Button size="sm" className="gap-2">
              <Plus className="h-4 w-4" />
              New Work Order
            </Button>
          </div>
        </div>

        <TabsContent value="work-orders">
          <Card>
            <CardContent className="p-0">
              <DataTable data={workOrders} columns={workOrderColumns} />
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="inventory">
          <Card>
            <CardContent className="p-0">
              <DataTable data={inventoryItems} columns={inventoryColumns} />
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="quality">
          <Card>
            <CardContent className="p-0">
              <DataTable data={qualityChecks} columns={qualityColumns} />
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="planning">
          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-center h-48 text-neutral-500">
                <div className="text-center">
                  <Calendar className="h-12 w-12 mx-auto mb-3 opacity-50" />
                  <p>Production planning calendar view</p>
                  <p className="text-sm">Coming soon</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  )
}
