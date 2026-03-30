import { useState } from 'react'
import { Plus, Search, Check, Settings2 } from 'lucide-react'
import { usePermissions } from '@/core/auth/useAuth'

const MOCK_ROLES = [
  { id: '1', name: 'Super Admin', usersCount: 2, description: 'Full system access' },
  { id: '2', name: 'Sales Manager', usersCount: 15, description: 'Manage sales teams and CRM' },
  { id: '3', name: 'Field Rep', usersCount: 120, description: 'SFA access with limited CRM' },
]

export function RolesListPage() {
  const { can } = usePermissions()
  const [search, setSearch] = useState('')

  return (
    <div className="p-6 max-w-7xl mx-auto space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-neutral-900 dark:text-neutral-100">Roles & Permissions</h1>
          <p className="text-sm text-neutral-500 dark:text-neutral-400">Configure access levels and feature visibility across the ERP</p>
        </div>

        {can('roles.manage') && (
          <button className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors">
            <Plus className="h-4 w-4" />
            Create Role
          </button>
        )}
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {/* Roles List Sidebar */}
        <div className="md:col-span-1 space-y-4">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-neutral-400" />
            <input
              type="text"
              placeholder="Filter roles..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full pl-9 pr-4 py-2 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50"
            />
          </div>

          <div className="bg-white dark:bg-neutral-900 shadow-sm border border-neutral-200 dark:border-neutral-800 rounded-lg overflow-hidden flex flex-col">
            {MOCK_ROLES.map((role, idx) => (
              <button
                key={role.id}
                className={`w-full text-left p-4 hover:bg-neutral-50 dark:hover:bg-neutral-800 border-b border-neutral-200 dark:border-neutral-800 last:border-0 transition-colors ${idx === 0 ? 'bg-primary/5 dark:bg-primary/10 border-l-4 border-l-primary' : 'border-l-4 border-l-transparent'}`}
              >
                <div className="font-medium text-neutral-900 dark:text-neutral-100">{role.name}</div>
                <div className="text-xs text-neutral-500 mt-1">{role.usersCount} users assigned</div>
              </button>
            ))}
          </div>
        </div>

        {/* Roles Detail / Permissions Matrix */}
        <div className="md:col-span-2 bg-white dark:bg-neutral-900 shadow-sm border border-neutral-200 dark:border-neutral-800 rounded-lg p-6">
          <div className="flex justify-between items-start mb-6">
            <div>
              <h2 className="text-xl font-semibold flex items-center gap-2">
                Super Admin
                <span className="bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400 text-xs px-2 py-0.5 rounded-full font-normal">System Role</span>
              </h2>
              <p className="text-sm text-neutral-500 mt-1">Full system access bypasses granular permissions.</p>
            </div>

            <button className="p-2 text-neutral-500 hover:text-neutral-900 hover:bg-neutral-100 rounded-md transition-colors">
              <Settings2 className="h-5 w-5" />
            </button>
          </div>

          <div className="space-y-6">
            {/* Example Module block */}
            <div className="border border-neutral-200 dark:border-neutral-800 rounded-lg overflow-hidden">
              <div className="bg-neutral-50 dark:bg-neutral-800/50 p-3 px-4 flex justify-between items-center border-b border-neutral-200 dark:border-neutral-800">
                <span className="font-medium font-sm text-neutral-700 dark:text-neutral-300">CRM & Sales</span>
                <div className="flex items-center gap-2">
                  <span className="text-xs text-neutral-500">Global Access:</span>
                  <div className="h-4 w-8 bg-primary rounded-full relative">
                    <div className="h-3 w-3 bg-white rounded-full absolute right-0.5 top-0.5"></div>
                  </div>
                </div>
              </div>
              <div className="p-4 grid grid-cols-2 gap-4">
                {['leads.view', 'leads.create', 'quotes.approve', 'opportunities.manage'].map(p => (
                  <label key={p} className="flex items-center gap-2 cursor-pointer">
                    <div className="h-4 w-4 rounded bg-primary text-white flex items-center justify-center">
                      <Check className="h-3 w-3" />
                    </div>
                    <span className="text-sm text-neutral-700 dark:text-neutral-300 font-mono">{p}</span>
                  </label>
                ))}
              </div>
            </div>

            <div className="border border-neutral-200 dark:border-neutral-800 rounded-lg overflow-hidden">
              <div className="bg-neutral-50 dark:bg-neutral-800/50 p-3 px-4 flex justify-between items-center border-b border-neutral-200 dark:border-neutral-800">
                <span className="font-medium font-sm text-neutral-700 dark:text-neutral-300">Inventory Management</span>
              </div>
              <div className="p-4 grid grid-cols-2 gap-4 opacity-50 pointer-events-none">
                {['stock.view', 'warehouses.manage'].map(p => (
                  <label key={p} className="flex items-center gap-2 cursor-pointer">
                    <div className="h-4 w-4 rounded border border-neutral-300 dark:border-neutral-600 flex items-center justify-center">
                    </div>
                    <span className="text-sm text-neutral-700 dark:text-neutral-300 font-mono">{p}</span>
                  </label>
                ))}
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  )
}

export default RolesListPage;
