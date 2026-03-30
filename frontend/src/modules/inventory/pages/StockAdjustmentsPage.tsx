import { useState } from 'react'
import { Plus, Search, MoreVertical, ClipboardList, ScanLine, Calculator } from 'lucide-react'
import { usePermissions } from '@/core/auth/useAuth'

const MOCK_ADJUSTMENTS = [
  { id: 'INV-2026-001', location: 'WH/Stock/A1', date: '2026-03-20', status: 'Validated', reason: 'Annual Cycle Count', responsible: 'John Doe', items: 145 },
  { id: 'INV-2026-002', location: 'WH/Stock/B2', date: '2026-03-21', status: 'In Progress', reason: 'Damage Check', responsible: 'Sarah Connor', items: 12 },
  { id: 'INV-2026-003', location: 'WH/Stock', date: '2026-03-24', status: 'Draft', reason: 'Initial Inventory', responsible: 'Admin User', items: 50 },
]

export function StockAdjustmentsPage() {
  const { can } = usePermissions()
  const [search, setSearch] = useState('')

  return (
    <div className="p-6 max-w-[1600px] mx-auto space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-neutral-900 dark:text-neutral-100">Physical Inventory</h1>
          <p className="text-sm text-neutral-500 dark:text-neutral-400">Perform cycle counts, physical inventory tracking, and logical write-offs</p>
        </div>
        
        <div className="flex items-center gap-2">
          <button className="flex items-center gap-2 px-3 py-2 text-sm text-neutral-600 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-md hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors">
            <ScanLine className="h-4 w-4" />
            Barcode Scan
          </button>
          {can('inventory.adjustments.create') && (
            <button className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors font-medium text-sm">
              <Plus className="h-4 w-4" />
              Start Cycle Count
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
              placeholder="Search adjustments..." 
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full pl-9 pr-4 py-2 bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm"
            />
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm whitespace-nowrap">
            <thead className="bg-neutral-50 dark:bg-neutral-800/50 text-neutral-500 dark:text-neutral-400">
              <tr>
                <th className="px-6 py-3 font-medium">Inventory Reference</th>
                <th className="px-6 py-3 font-medium">Location</th>
                <th className="px-6 py-3 font-medium">Date / Time</th>
                <th className="px-6 py-3 font-medium">Accounted Lines</th>
                <th className="px-6 py-3 font-medium">Responsible</th>
                <th className="px-6 py-3 font-medium text-center">Status</th>
                <th className="px-6 py-3 font-medium text-right"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
              {MOCK_ADJUSTMENTS.map((adj) => (
                <tr key={adj.id} className="hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors cursor-pointer group">
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-3">
                      <div className="h-9 w-9 bg-neutral-100 dark:bg-neutral-800 rounded flex items-center justify-center text-primary">
                        <ClipboardList className="h-4 w-4" />
                      </div>
                      <div className="flex flex-col gap-0.5">
                        <div className="font-semibold text-neutral-900 dark:text-neutral-100">{adj.id}</div>
                        <div className="text-xs text-neutral-500">{adj.reason}</div>
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4 font-mono text-xs text-neutral-600 dark:text-neutral-400">
                    {adj.location}
                  </td>
                  <td className="px-6 py-4 text-neutral-600 dark:text-neutral-400">
                    {adj.date}
                  </td>
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-1.5 text-neutral-600 dark:text-neutral-400 font-medium">
                      <Calculator className="h-3.5 w-3.5" />
                      {adj.items} items parsed
                    </div>
                  </td>
                  <td className="px-6 py-4 text-neutral-600 dark:text-neutral-400">
                    {adj.responsible}
                  </td>
                  <td className="px-6 py-4 text-center">
                    <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${
                      adj.status === 'Validated' ? 'bg-green-100 text-green-700' :
                      adj.status === 'In Progress' ? 'bg-blue-100 text-blue-700' :
                      'bg-neutral-100 text-neutral-500'
                    }`}>
                      {adj.status}
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

export default StockAdjustmentsPage;
