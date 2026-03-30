import { useState } from 'react'
import { Plus, Search, Filter, MoreVertical, Mail, Phone, Building2, MapPin } from 'lucide-react'
import { usePermissions } from '@/core/auth/useAuth'
import { cn } from '@/lib/utils'

const MOCK_LEADS = [
  { id: '1', name: 'Alpha Corp Initiative', contact_name: 'Sarah Connor', company: 'Alpha Corp', email: 'sarah@alpha.co', phone: '+1 555-0100', status: 'New', score: 85, territory: 'North America' },
  { id: '2', name: 'Beta Systems POS', contact_name: 'John Smith', company: 'Beta Systems', email: 'john@beta.dev', phone: '+1 555-0200', status: 'Qualified', score: 92, territory: 'EMEA' },
  { id: '3', name: 'Gamma Fleet Software', contact_name: 'Emily Davis', company: 'Gamma Logistics', email: 'emily@gamma.io', phone: '+1 555-0300', status: 'Lost', score: 40, territory: 'APAC' },
]

export function LeadsListPage() {
  const { can } = usePermissions()
  const [search, setSearch] = useState('')

  return (
    <div className="p-6 max-w-[1600px] mx-auto space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-neutral-900 dark:text-neutral-100">Leads</h1>
          <p className="text-sm text-neutral-500 dark:text-neutral-400">Track and qualify top-of-funnel prospects</p>
        </div>
        
        <div className="flex items-center gap-2">
          {can('leads.create') && (
            <button className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors font-medium">
              <Plus className="h-4 w-4" />
              New Lead
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
              placeholder="Search leads by name, company, or contact..." 
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
                <th className="px-6 py-3 font-medium">Lead Name</th>
                <th className="px-6 py-3 font-medium">Contact & Company</th>
                <th className="px-6 py-3 font-medium">Contact Info</th>
                <th className="px-6 py-3 font-medium">Status</th>
                <th className="px-6 py-3 font-medium">Score</th>
                <th className="px-6 py-3 font-medium text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
              {MOCK_LEADS.map((lead) => (
                <tr key={lead.id} className="hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors cursor-pointer">
                  <td className="px-6 py-4">
                    <div className="font-semibold text-neutral-900 dark:text-neutral-100">{lead.name}</div>
                    <div className="flex items-center gap-1 text-xs text-neutral-500 mt-1">
                      <MapPin className="h-3 w-3" /> {lead.territory}
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <div className="font-medium text-neutral-800 dark:text-neutral-200">{lead.contact_name}</div>
                    <div className="flex items-center gap-1.5 text-xs text-neutral-500 mt-1">
                      <Building2 className="h-3 w-3" />
                      {lead.company}
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <div className="flex flex-col gap-1 text-neutral-600 dark:text-neutral-400">
                      <div className="flex items-center gap-1.5">
                        <Mail className="h-3.5 w-3.5" />
                        <span>{lead.email}</span>
                      </div>
                      <div className="flex items-center gap-1.5">
                        <Phone className="h-3.5 w-3.5" />
                        <span>{lead.phone}</span>
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <span className={cn(
                      "inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold",
                      lead.status === 'New' && "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400",
                      lead.status === 'Qualified' && "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400",
                      lead.status === 'Lost' && "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400"
                    )}>
                      {lead.status}
                    </span>
                  </td>
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-2">
                      <div className="w-16 h-2 bg-neutral-200 dark:bg-neutral-700 rounded-full overflow-hidden">
                        <div 
                          className={cn("h-full rounded-full", lead.score >= 80 ? 'bg-green-500' : lead.score >= 50 ? 'bg-amber-500' : 'bg-red-500')} 
                          style={{ width: `${lead.score}%` }} 
                        />
                      </div>
                      <span className="text-xs font-medium text-neutral-600 dark:text-neutral-400">{lead.score}</span>
                    </div>
                  </td>
                  <td className="px-6 py-4 text-right">
                    <button className="p-1.5 text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100 transition-colors rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-800">
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

export default LeadsListPage;
