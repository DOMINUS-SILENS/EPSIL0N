import { KPICard } from '@/design-system/primitives/KPICard/KPICard'
import { DataTable } from '@/design-system/composite/DataTable/DataTable'
import { ChartCard, OrdersChart, ProductionPieChart } from '@/components/erp/charts'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Progress } from '@/components/ui/progress'
import { Badge } from '@/components/ui/badge'
import {
  Factory,
  Package,
  ClipboardList,
  Truck,
  AlertTriangle,
  Plus,
  Filter,
} from 'lucide-react'
import type { ColumnDef } from '@tanstack/react-table'

// Sample data
const productionData = [
  { name: 'Completed', value: 65, color: 'hsl(var(--success))' },
  { name: 'In Progress', value: 25, color: 'hsl(var(--primary))' },
  { name: 'Pending', value: 10, color: 'hsl(var(--warning))' },
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
  dueDate: string
  completion: number
}

const workOrders: WorkOrder[] = [
  { id: 'WO-001', product: 'Assembly A-100', quantity: 500, status: 'in-progress', priority: 'High', dueDate: 'Mar 30', completion: 65 },
  { id: 'WO-002', product: 'Component B-200', quantity: 1000, status: 'in-progress', priority: 'Medium', dueDate: 'Apr 2', completion: 40 },
  { id: 'WO-003', product: 'Module C-300', quantity: 250, status: 'pending', priority: 'High', dueDate: 'Apr 5', completion: 0 },
  { id: 'WO-004', product: 'Part D-400', quantity: 2000, status: 'completed', priority: 'Low', dueDate: 'Mar 25', completion: 100 },
  { id: 'WO-005', product: 'Assembly A-100', quantity: 750, status: 'in-progress', priority: 'Medium', dueDate: 'Apr 8', completion: 25 },
]

const workOrderColumns: ColumnDef<WorkOrder>[] = [
  { accessorKey: 'id', header: 'Work Order', cell: ({ row }) => <span className="font-mono text-sm">{row.getValue('id')}</span> },
  { accessorKey: 'product', header: 'Product', cell: ({ row }) => <span className="font-medium">{row.getValue('product')}</span> },
  { accessorKey: 'quantity', header: 'Qty', cell: ({ row }) => <span className="text-center">{row.getValue('quantity')}</span> },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) => {
      const status = row.getValue('status') as string
      const variants: Record<string, { className: string; label: string }> = {
        'in-progress': { className: 'bg-blue-100 text-blue-800 border-blue-200', label: 'In Progress' },
        pending: { className: 'bg-amber-100 text-amber-800 border-amber-200', label: 'Pending' },
        completed: { className: 'bg-green-100 text-green-800 border-green-200', label: 'Completed' },
      }
      const variant = variants[status] || { className: 'bg-gray-100 text-gray-800', label: status }
      return <Badge variant="outline" className={`text-xs ${variant.className}`}>{variant.label}</Badge>
    },
  },
  {
    accessorKey: 'priority',
    header: 'Priority',
    cell: ({ row }) => {
      const priority = row.getValue('priority') as string
      const colorClass = priority === 'High' ? 'text-red-600' : priority === 'Medium' ? 'text-amber-600' : 'text-gray-500'
      return <span className={`text-sm font-medium ${colorClass}`}>{priority}</span>
    },
  },
  { accessorKey: 'dueDate', header: 'Due Date', cell: ({ row }) => <span className="text-muted-foreground">{row.getValue('dueDate')}</span> },
  {
    accessorKey: 'completion',
    header: 'Progress',
    cell: ({ row }) => {
      const value = row.getValue('completion') as number
      return (
        <div className="flex items-center gap-2 min-w-[120px]">
          <Progress value={value} className="h-2 flex-1" />
          <span className="text-xs text-muted-foreground w-8">{value}%</span>
        </div>
      )
    },
  },
]

interface InventoryItem {
  [key: string]: unknown
  sku: string
  name: string
  quantity: number
  minStock: number
  status: string
  location: string
}

const inventoryItems: InventoryItem[] = [
  { sku: 'SKU-001', name: 'Raw Material A', quantity: 1500, minStock: 500, status: 'in-stock', location: 'Warehouse A' },
  { sku: 'SKU-002', name: 'Component X', quantity: 320, minStock: 400, status: 'low', location: 'Warehouse B' },
  { sku: 'SKU-003', name: 'Part Y-200', quantity: 85, minStock: 100, status: 'low', location: 'Warehouse A' },
  { sku: 'SKU-004', name: 'Assembly Z-500', quantity: 2500, minStock: 800, status: 'in-stock', location: 'Warehouse C' },
  { sku: 'SKU-005', name: 'Material M-100', quantity: 0, minStock: 200, status: 'out-of-stock', location: 'Warehouse B' },
]

const inventoryColumns: ColumnDef<InventoryItem>[] = [
  { accessorKey: 'sku', header: 'SKU', cell: ({ row }) => <span className="font-mono text-sm">{row.getValue('sku')}</span> },
  { accessorKey: 'name', header: 'Name', cell: ({ row }) => <span className="font-medium">{row.getValue('name')}</span> },
  { accessorKey: 'quantity', header: 'Qty', cell: ({ row }) => <span className="text-center">{row.getValue('quantity')}</span> },
  { accessorKey: 'minStock', header: 'Min Stock', cell: ({ row }) => <span className="text-muted-foreground">{row.getValue('minStock')}</span> },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) => {
      const status = row.getValue('status') as string
      const variants: Record<string, { className: string; label: string }> = {
        'in-stock': { className: 'bg-green-100 text-green-800 border-green-200', label: 'In Stock' },
        low: { className: 'bg-amber-100 text-amber-800 border-amber-200', label: 'Low Stock' },
        'out-of-stock': { className: 'bg-red-100 text-red-800 border-red-200', label: 'Out of Stock' },
      }
      const variant = variants[status] || { className: 'bg-gray-100 text-gray-800', label: status }
      return <Badge variant="outline" className={`text-xs ${variant.className}`}>{variant.label}</Badge>
    },
  },
  { accessorKey: 'location', header: 'Location', cell: ({ row }) => <span className="text-muted-foreground">{row.getValue('location')}</span> },
]

export function DashboardPage() {
  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Dashboard</h1>
          <p className="text-muted-foreground">Production overview and key metrics</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" size="sm">
            <Filter className="h-4 w-4 mr-2" />
            Filter
          </Button>
          <Button size="sm">
            <Plus className="h-4 w-4 mr-2" />
            New Order
          </Button>
        </div>
      </div>

      {/* KPI Cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <KPICard
          title="Production Output"
          value="2,450"
          change={12.5}
          trend="up"
          changeLabel="vs last month"
          icon={<Factory className="h-5 w-5" />}
        />
        <KPICard
          title="Active Work Orders"
          value="24"
          change={-3}
          trend="down"
          changeLabel="vs last week"
          icon={<ClipboardList className="h-5 w-5" />}
        />
        <KPICard
          title="Inventory Alerts"
          value="3"
          change={2}
          trend="up"
          changeLabel="low stock items"
          icon={<AlertTriangle className="h-5 w-5" />}
        />
        <KPICard
          title="On-Time Delivery"
          value="94.2%"
          change={1.8}
          trend="up"
          changeLabel="vs last month"
          icon={<Truck className="h-5 w-5" />}
        />
      </div>

      {/* Charts */}
      <div className="grid gap-4 md:grid-cols-2">
        <ChartCard title="Monthly Orders" subtitle="Orders vs Completed">
          <OrdersChart data={monthlyOutput} />
        </ChartCard>
        <ChartCard title="Production Status" subtitle="Current work order distribution">
          <ProductionPieChart data={productionData} />
        </ChartCard>
      </div>

      {/* Work Orders Table */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <div>
            <CardTitle>Active Work Orders</CardTitle>
            <p className="text-sm text-muted-foreground mt-1">Track production progress and priorities</p>
          </div>
          <Button variant="outline" size="sm">View All</Button>
        </CardHeader>
        <CardContent>
          <DataTable
            data={workOrders}
            columns={workOrderColumns}
            enablePagination={true}
            pageSize={5}
          />
        </CardContent>
      </Card>

      {/* Inventory Alerts */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <div className="flex items-center gap-2">
            <Package className="h-5 w-5 text-amber-500" />
            <div>
              <CardTitle>Inventory Alerts</CardTitle>
              <p className="text-sm text-muted-foreground mt-1">Items requiring attention</p>
            </div>
          </div>
          <Button variant="outline" size="sm">Manage Inventory</Button>
        </CardHeader>
        <CardContent>
          <DataTable
            data={inventoryItems}
            columns={inventoryColumns}
            enablePagination={true}
            pageSize={5}
          />
        </CardContent>
      </Card>
    </div>
  )
}
