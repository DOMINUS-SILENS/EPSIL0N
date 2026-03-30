import { useState } from 'react'
import { Plus, Search, MoreHorizontal, DollarSign, Calendar, GripVertical } from 'lucide-react'
import { usePermissions } from '@/core/auth/useAuth'

const STAGES = [
  { id: 'new', name: 'New', color: 'bg-blue-500' },
  { id: 'qualified', name: 'Qualified', color: 'bg-indigo-500' },
  { id: 'proposition', name: 'Proposition', color: 'bg-amber-500' },
  { id: 'won', name: 'Won', color: 'bg-green-500' },
]

const MOCK_OPPS = [
  { id: '1', name: '1000 POS Licenses', company: 'Alpha Retail', value: 45000, stage: 'new', closingDate: '2026-04-15' },
  { id: '2', name: 'Warehouse WMS Upgrade', company: 'Beta Logistics', value: 120000, stage: 'qualified', closingDate: '2026-05-01' },
  { id: '3', name: 'Fleet Routing Integration', company: 'Gamma Fleet', value: 85000, stage: 'proposition', closingDate: '2026-04-20' },
  { id: '4', name: '50 User ERP Migration', company: 'Delta Manufacturing', value: 250000, stage: 'won', closingDate: '2026-03-30' },
  { id: '5', name: 'Consulting Retainer', company: 'Epsilon Partners', value: 15000, stage: 'new', closingDate: '2026-06-10' },
]

export function OpportunitiesListPage() {
  const { can } = usePermissions()
  const [opportunities] = useState(MOCK_OPPS)

  const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(val)
  }

  return (
    <div className="p-6 max-w-[1600px] mx-auto h-[calc(100vh-4rem)] flex flex-col">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 shrink-0">
        <div>
          <h1 className="text-2xl font-bold text-neutral-900 dark:text-neutral-100">Opportunities Pipeline</h1>
          <p className="text-sm text-neutral-500 dark:text-neutral-400">Drag and drop opportunities across the sales funnel</p>
        </div>
        
        <div className="flex items-center gap-3">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-neutral-400" />
            <input 
              type="text" 
              placeholder="Search..." 
              className="w-full pl-9 pr-4 py-2 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm"
            />
          </div>
          {can('opportunities.manage') && (
            <button className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors font-medium">
              <Plus className="h-4 w-4" />
              New Opportunity
            </button>
          )}
        </div>
      </div>

      <div className="flex-1 flex gap-6 overflow-x-auto pb-4 custom-scrollbar">
        {STAGES.map((stage) => {
          const stageOpps = opportunities.filter(o => o.stage === stage.id)
          const totalValue = stageOpps.reduce((sum, o) => sum + o.value, 0)

          return (
            <div key={stage.id} className="min-w-[320px] w-[320px] flex flex-col bg-neutral-100/50 dark:bg-neutral-900/50 rounded-xl">
              {/* Stage Header */}
              <div className="p-4 border-b border-transparent shrink-0">
                <div className="flex items-center justify-between mb-2">
                  <h3 className="font-semibold text-neutral-800 dark:text-neutral-200 flex items-center gap-2">
                    <div className={`w-2 h-2 rounded-full ${stage.color}`} />
                    {stage.name}
                  </h3>
                  <button className="p-1 hover:bg-neutral-200 dark:hover:bg-neutral-800 rounded text-neutral-500">
                    <MoreHorizontal className="h-4 w-4" />
                  </button>
                </div>
                <div className="flex items-center justify-between text-xs text-neutral-500 font-medium">
                  <span>{formatCurrency(totalValue)}</span>
                  <span>{stageOpps.length} deals</span>
                </div>
              </div>

              {/* Kanban Droppable Area */}
              <div className="flex-1 p-3 space-y-3 overflow-y-auto">
                {stageOpps.map(opp => (
                  <div 
                    key={opp.id} 
                    className="bg-white dark:bg-neutral-900 p-4 rounded-lg shadow-sm border border-neutral-200 dark:border-neutral-800 hover:border-primary/50 dark:hover:border-primary/50 cursor-grab active:cursor-grabbing transition-colors group relative"
                  >
                    <div className="absolute left-2 top-1/2 -translate-y-1/2 text-neutral-300 dark:text-neutral-700 opacity-0 group-hover:opacity-100 transition-opacity">
                      <GripVertical className="h-4 w-4" />
                    </div>
                    
                    <div className="pl-4">
                      <h4 className="font-medium text-neutral-900 dark:text-neutral-100 text-sm mb-1">{opp.name}</h4>
                      <div className="text-xs text-neutral-500 mb-3">{opp.company}</div>
                      
                      <div className="flex items-center justify-between mt-auto pt-3 border-t border-neutral-100 dark:border-neutral-800/50">
                        <div className="flex items-center gap-1.5 text-xs font-semibold text-neutral-700 dark:text-neutral-300">
                          <DollarSign className="h-3.5 w-3.5 text-primary" />
                          {formatCurrency(opp.value)}
                        </div>
                        <div className="flex items-center gap-1 text-xs text-neutral-500 bg-neutral-100 dark:bg-neutral-800 px-2 py-0.5 rounded">
                          <Calendar className="h-3 w-3" />
                          {new Date(opp.closingDate).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
                
                {stageOpps.length === 0 && (
                  <div className="h-32 rounded-lg border-2 border-dashed border-neutral-300 dark:border-neutral-700 flex flex-col items-center justify-center text-neutral-400 dark:text-neutral-600 bg-white/50 dark:bg-neutral-900/50">
                    <p className="text-sm font-medium">Drop here</p>
                  </div>
                )}
              </div>
            </div>
          )
        })}
      </div>
    </div>
  )
}

export default OpportunitiesListPage;
