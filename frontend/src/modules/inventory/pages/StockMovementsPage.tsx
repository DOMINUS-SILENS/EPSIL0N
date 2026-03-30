import { useState } from 'react'
import { Plus, Search, Filter, MoreVertical, ArrowRightLeft, ArrowDownToLine, ArrowUpFromLine, Clock } from 'lucide-react'
import { usePermissions } from '@/core/auth/useAuth'

const MOCK_MOVEMENTS = [
  { id: 'WH/IN/0001', type: 'Receipt', reference: 'PO-2026-041', contact: 'Tech Supplier Inc', source: 'Vendors', dest: 'WH/Stock', status: 'Done', date: '2026-03-22' },
  { id: 'WH/OUT/0002', type: 'Delivery', reference: 'SO-2026-001', contact: 'Alpha Retail Group', source: 'WH/Stock', dest: 'Customers', status: 'Ready', date: '2026-03-23' },
  { id: 'WH/INT/0001', type: 'Internal Transfer', reference: 'Restock Zone B', contact: 'Internal', source: 'WH/Stock/A1', dest: 'WH/Stock/B2', status: 'Waiting', date: '2026-03-24' },
  { id: 'WH/OUT/0003', type: 'Delivery', reference: 'SO-2026-002', contact: 'Beta Logistics', source: 'WH/Stock', dest: 'Customers', status: 'Draft', date: '2026-03-25' },
]

export function StockMovementsPage() {
  const { can } = usePermissions()
  const [search, setSearch] = useState('')

  const getTypeIcon = (type: string) => {
    switch(type) {
      case 'Receipt': return <ArrowDownToLine className="h-4 w-4 text-green-500" />
      case 'Delivery': return <ArrowUpFromLine className="h-4 w-4 text-primary" />
      case 'Internal Transfer': return <ArrowRightLeft className="h-4 w-4 text-amber-500" />
      default: return <ArrowRightLeft className="h-4 w-4 text-neutral-400" />
    }
  }

  const getStatusBadge = (status: string) => {
    switch(status) {
      case 'Done': return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
      case 'Ready': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
      case 'Waiting': return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
      case 'Draft': return 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-400'
      default: return 'bg-neutral-100 text-neutral-700'
    }
  }

  return (
    <div className="p-6 max-w-[1600px] mx-auto space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-neutral-900 dark:text-neutral-100">Transfers & Operations</h1>
          <p className="text-sm text-neutral-500 dark:text-neutral-400">Process receipts, deliveries, and internal physical movements</p>
        </div>
        
        <div className="flex items-center gap-2">
          {can('inventory.movements.create') && (
            <button className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors font-medium text-sm">
              <Plus className="h-4 w-4" />
              New Transfer
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
              placeholder="Search by Reference or Contact..." 
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full pl-9 pr-4 py-2 bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm"
            />
          </div>
          
          <button className="flex items-center gap-2 px-3 py-2 text-sm text-neutral-600 bg-neutral-100 dark:bg-neutral-800 dark:text-neutral-300 rounded-md hover:bg-neutral-200 dark:hover:bg-neutral-700 transition-colors">
            <Filter className="h-4 w-4" />
            Filters
          </button>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm whitespace-nowrap">
            <thead className="bg-neutral-50 dark:bg-neutral-800/50 text-neutral-500 dark:text-neutral-400">
              <tr>
                <th className="px-6 py-3 font-medium">Reference</th>
                <th className="px-6 py-3 font-medium">Contact</th>
                <th className="px-6 py-3 font-medium">Scheduled Date</th>
                <th className="px-6 py-3 font-medium">Source Location</th>
                <th className="px-6 py-3 font-medium">Destination Location</th>
                <th className="px-6 py-3 font-medium text-center">Status</th>
                <th className="px-6 py-3 font-medium text-right"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
              {MOCK_MOVEMENTS.map((mov) => (
                <tr key={mov.id} className="hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors cursor-pointer group">
                  <td className="px-6 py-4">
                    <div className="flex flex-col gap-1">
                      <div className="font-semibold text-neutral-900 dark:text-neutral-100">{mov.id}</div>
                      <div className="flex items-center gap-1.5 text-xs text-neutral-500">
                        {getTypeIcon(mov.type)}
                        {mov.reference}
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4 font-medium text-neutral-900 dark:text-neutral-100">
                    {mov.contact}
                  </td>
                  <td className="px-6 py-4 text-neutral-600 dark:text-neutral-400">
                    <div className="flex items-center gap-1.5">
                      <Clock className="h-3.5 w-3.5" />
                      {mov.date}
                    </div>
                  </td>
                  <td className="px-6 py-4 font-mono text-xs text-neutral-600 dark:text-neutral-400">
                    {mov.source}
                  </td>
                  <td className="px-6 py-4 font-mono text-xs text-neutral-600 dark:text-neutral-400">
                    {mov.dest}
                  </td>
                  <td className="px-6 py-4 text-center">
                    <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${getStatusBadge(mov.status)}`}>
                      {mov.status}
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

export default StockMovementsPage;
