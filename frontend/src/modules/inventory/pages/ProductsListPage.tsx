import { useState } from 'react'
import { Plus, Search, Filter, MoreVertical, Package, Tag, AlertTriangle, Box } from 'lucide-react'
import { usePermissions } from '@/core/auth/useAuth'
import { cn } from '@/lib/utils'

const MOCK_PRODUCTS = [
  { id: 'PRD-001', name: 'Enterprise POS Terminal', category: 'Hardware', price: 1250.00, on_hand: 45, reserved: 12, incoming: 100, status: 'In Stock' },
  { id: 'PRD-002', name: 'Rugged Tablet 10"', category: 'Hardware', price: 850.00, on_hand: 5, reserved: 2, incoming: 0, status: 'Low Stock' },
  { id: 'PRD-003', name: 'Barcode Scanner (Wireless)', category: 'Peripherals', price: 299.00, on_hand: 120, reserved: 45, incoming: 50, status: 'In Stock' },
  { id: 'PRD-004', name: 'Thermal Receipt Printer', category: 'Peripherals', price: 199.00, on_hand: 0, reserved: 15, incoming: 200, status: 'Out of Stock' },
  { id: 'PRD-005', name: 'Premium Service SLA', category: 'Services', price: 1500.00, on_hand: null, reserved: null, incoming: null, status: 'Active' },
]

export function ProductsListPage() {
  const { can } = usePermissions()
  const [search, setSearch] = useState('')

  const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val)
  }

  const getStatusBadge = (status: string) => {
    switch(status) {
      case 'In Stock': return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
      case 'Low Stock': return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
      case 'Out of Stock': return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
      default: return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
    }
  }

  return (
    <div className="p-6 max-w-[1600px] mx-auto space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-neutral-900 dark:text-neutral-100">Products & Catalog</h1>
          <p className="text-sm text-neutral-500 dark:text-neutral-400">Manage master product data, variants, and global inventory levels</p>
        </div>
        
        <div className="flex items-center gap-2">
          {can('products.create') && (
            <button className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors font-medium text-sm">
              <Plus className="h-4 w-4" />
              New Product
            </button>
          )}
        </div>
      </div>

      <div className="bg-white dark:bg-neutral-900 shadow-sm border border-neutral-200 dark:border-neutral-800 rounded-lg overflow-hidden flex flex-col">
        <div className="p-4 border-b border-neutral-200 dark:border-neutral-800 flex items-center justify-between gap-4">
          <div className="relative w-full max-w-md">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-neutral-400" />
            <input 
              type="text" 
              placeholder="Search by SKU or product name..." 
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full pl-9 pr-4 py-2 bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm"
            />
          </div>
          
          <button className="flex items-center gap-2 px-3 py-2 text-sm text-neutral-600 bg-neutral-100 dark:bg-neutral-800 dark:text-neutral-300 rounded-md hover:bg-neutral-200 dark:hover:bg-neutral-700 transition-colors">
            <Filter className="h-4 w-4" />
            Category Filter
          </button>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm whitespace-nowrap">
            <thead className="bg-neutral-50 dark:bg-neutral-800/50 text-neutral-500 dark:text-neutral-400">
              <tr>
                <th className="px-6 py-3 font-medium">Product / SKU</th>
                <th className="px-6 py-3 font-medium">Category</th>
                <th className="px-6 py-3 font-medium">Sales Price</th>
                <th className="px-6 py-3 font-medium text-right">On Hand</th>
                <th className="px-6 py-3 font-medium text-right">Reserved</th>
                <th className="px-6 py-3 font-medium text-center">Status</th>
                <th className="px-6 py-3 font-medium text-right"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
              {MOCK_PRODUCTS.map((prod) => (
                <tr key={prod.id} className="hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors cursor-pointer group">
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-3">
                      <div className="h-10 w-10 bg-neutral-100 dark:bg-neutral-800 rounded flex items-center justify-center text-neutral-400">
                        {prod.category === 'Services' ? <Tag className="h-5 w-5" /> : <Package className="h-5 w-5" />}
                      </div>
                      <div>
                        <div className="font-semibold text-neutral-900 dark:text-neutral-100">{prod.name}</div>
                        <div className="text-xs text-neutral-500 font-mono mt-0.5">{prod.id}</div>
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4 text-neutral-600 dark:text-neutral-400">
                    <span className="inline-flex items-center px-2 py-0.5 rounded bg-neutral-100 dark:bg-neutral-800 text-xs font-medium">
                      {prod.category}
                    </span>
                  </td>
                  <td className="px-6 py-4 font-medium text-neutral-900 dark:text-neutral-100">
                    {formatCurrency(prod.price)}
                  </td>
                  <td className="px-6 py-4 text-right">
                    {prod.on_hand !== null ? (
                      <span className="font-medium text-neutral-900 dark:text-neutral-100">{prod.on_hand}</span>
                    ) : (
                      <span className="text-neutral-400">—</span>
                    )}
                  </td>
                  <td className="px-6 py-4 text-right">
                    {prod.reserved !== null ? (
                      <span className="text-neutral-500">{prod.reserved}</span>
                    ) : (
                      <span className="text-neutral-400">—</span>
                    )}
                  </td>
                  <td className="px-6 py-4 text-center">
                    <span className={cn(
                      "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold",
                      getStatusBadge(prod.status)
                    )}>
                      {prod.status === 'Low Stock' && <AlertTriangle className="h-3 w-3" />}
                      {prod.status === 'Out of Stock' && <Box className="h-3 w-3" />}
                      {prod.status}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-right">
                    <button className="p-1.5 text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100 transition-colors rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-800 opacity-0 group-hover:opacity-100">
                      <MoreVertical className="h-5 w-5" />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

export default ProductsListPage;
