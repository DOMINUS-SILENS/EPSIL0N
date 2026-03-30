import { KPICard } from '@/design-system/primitives/KPICard/KPICard'
import { DataTable } from '@/design-system/composite/DataTable/DataTable'
import { ChartCard, ProductionPieChart } from '@/components/erp/charts'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  Factory,
  ClipboardList,
  Package,
  Plus,
  Filter,
  Clock,
  CheckCircle,
} from 'lucide-react'
import type { ColumnDef } from '@tanstack/react-table'

// Sample data
const productionData = [
  { name: 'Completed', value: 65, color: 'hsl(var(--success))' },
  { name: 'In Progress', value: 25, color: 'hsl(var(--primary))' },
  { name: 'Pending', value: 10, color: 'hsl(var(--warning))' },
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
  assignedTo: string
}

const workOrders: WorkOrder[] = [
  { id: 'WO-2024-001', product: 'Assembly A-100', quantity: 500, status: 'in-progress', priority: 'High', startDate: 'Mar 20', dueDate: 'Mar 30', assignedTo: 'Team Alpha' },
  { id: 'WO-2024-002', product: 'Component B-200', quantity: 1000, status: 'in-progress', priority: 'Medium', startDate: 'Mar 22', dueDate: 'Apr 2', assignedTo: 'Team Beta' },
  { id: 'WO-2024-003', product: 'Module C-300', quantity: 250, status: 'pending', priority: 'High', startDate: 'Mar 28', dueDate: 'Apr 5', assignedTo: 'Team Gamma' },
  { id: 'WO-2024-004', product: 'Part D-400', quantity: 2000, status: 'completed', priority: 'Low', startDate: 'Mar 15', dueDate: 'Mar 25', assignedTo: 'Team Alpha' },
  { id: 'WO-2024-005', product: 'Assembly A-100', quantity: 750, status: 'in-progress', priority: 'Medium', startDate: 'Mar 24', dueDate: 'Apr 8', assignedTo: 'Team Beta' },
  { id: 'WO-2024-006', product: 'Component X-500', quantity: 300, status: 'pending', priority: 'Low', startDate: 'Mar 30', dueDate: 'Apr 10', assignedTo: 'Team Gamma' },
]

const workOrderColumns: ColumnDef<WorkOrder>[] = [
  { accessorKey: 'id', header: 'Work Order ID', cell: ({ row }) => <span className="font-mono text-sm">{row.getValue('id')}</span> },
  { accessorKey: 'product', header: 'Product', cell: ({ row }) => <span className="font-medium">{row.getValue('product')}</span> },
  { accessorKey: 'quantity', header: 'Quantity', cell: ({ row }) => <span className="text-center">{row.getValue('quantity')}</span> },
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
  { accessorKey: 'assignedTo', header: 'Assigned To', cell: ({ row }) => <span className="text-muted-foreground">{row.getValue('assignedTo')}</span> },
  { accessorKey: 'dueDate', header: 'Due Date', cell: ({ row }) => <span className="text-muted-foreground">{row.getValue('dueDate')}</span> },
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
  { sku: 'RAW-001', name: 'Steel Sheet 4x8', category: 'Raw Materials', quantity: 1500, minStock: 500, status: 'in-stock', location: 'Warehouse A' },
  { sku: 'RAW-002', name: 'Aluminum Bar', category: 'Raw Materials', quantity: 320, minStock: 400, status: 'low', location: 'Warehouse B' },
  { sku: 'COMP-001', name: 'PCB Assembly', category: 'Components', quantity: 85, minStock: 100, status: 'low', location: 'Warehouse A' },
  { sku: 'COMP-002', name: 'Motor Assembly', category: 'Components', quantity: 2500, minStock: 800, status: 'in-stock', location: 'Warehouse C' },
  { sku: 'FIN-001', name: 'Finished Product A', category: 'Finished Goods', quantity: 450, minStock: 200, status: 'in-stock', location: 'Warehouse D' },
  { sku: 'FIN-002', name: 'Finished Product B', category: 'Finished Goods', quantity: 0, minStock: 150, status: 'out-of-stock', location: 'Warehouse D' },
]

const inventoryColumns: ColumnDef<InventoryItem>[] = [
  { accessorKey: 'sku', header: 'SKU', cell: ({ row }) => <span className="font-mono text-sm">{row.getValue('sku')}</span> },
  { accessorKey: 'name', header: 'Name', cell: ({ row }) => <span className="font-medium">{row.getValue('name')}</span> },
  { accessorKey: 'category', header: 'Category', cell: ({ row }) => <span className="text-muted-foreground">{row.getValue('category')}</span> },
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

export function ProductionPage() {
  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Production</h1>
          <p className="text-muted-foreground">Manage work orders and inventory</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" size="sm">
            <Filter className="h-4 w-4 mr-2" />
            Filter
          </Button>
          <Button size="sm">
            <Plus className="h-4 w-4 mr-2" />
            New Work Order
          </Button>
        </div>
      </div>

      {/* KPI Cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <KPICard
          title="Active Work Orders"
          value="24"
          change={3}
          trend="up"
          changeLabel="new this week"
          icon={<Factory className="h-5 w-5" />}
        />
        <KPICard
          title="Completed Today"
          value="8"
          change={2}
          trend="up"
          changeLabel="vs yesterday"
          icon={<CheckCircle className="h-5 w-5" />}
        />
        <KPICard
          title="Pending Orders"
          value="6"
          change={-1}
          trend="down"
          changeLabel="vs last week"
          icon={<Clock className="h-5 w-5" />}
        />
        <KPICard
          title="Low Stock Items"
          value="3"
          change={1}
          trend="up"
          changeLabel="need attention"
          icon={<Package className="h-5 w-5" />}
        />
      </div>

      {/* Charts */}
      <div className="grid gap-4 md:grid-cols-3">
        <ChartCard title="Work Order Status" subtitle="Current distribution" className="md:col-span-1">
          <ProductionPieChart data={productionData} />
        </ChartCard>
        <Card className="md:col-span-2">
          <CardHeader>
            <CardTitle>Production Schedule</CardTitle>
            <p className="text-sm text-muted-foreground">Upcoming deadlines and priorities</p>
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
      </div>

      {/* Tabs for Work Orders and Inventory */}
      <Tabs defaultValue="workorders" className="w-full">
        <TabsList>
          <TabsTrigger value="workorders">
            <ClipboardList className="h-4 w-4 mr-2" />
            Work Orders
          </TabsTrigger>
          <TabsTrigger value="inventory">
            <Package className="h-4 w-4 mr-2" />
            Inventory
          </TabsTrigger>
        </TabsList>
        <TabsContent value="workorders" className="mt-4">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <div>
                <CardTitle>All Work Orders</CardTitle>
                <p className="text-sm text-muted-foreground mt-1">Manage and track production orders</p>
              </div>
              <Button variant="outline" size="sm">Export</Button>
            </CardHeader>
            <CardContent>
              <DataTable
                data={workOrders}
                columns={workOrderColumns}
                enablePagination={true}
                pageSize={10}
              />
            </CardContent>
          </Card>
        </TabsContent>
        <TabsContent value="inventory" className="mt-4">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <div>
                <CardTitle>Inventory Management</CardTitle>
                <p className="text-sm text-muted-foreground mt-1">Track stock levels and locations</p>
              </div>
              <Button variant="outline" size="sm">Reorder Report</Button>
            </CardHeader>
            <CardContent>
              <DataTable
                data={inventoryItems}
                columns={inventoryColumns}
                enablePagination={true}
                pageSize={10}
              />
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  )
}
