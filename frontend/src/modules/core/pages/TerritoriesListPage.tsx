import { Plus, Search, MapPin, Grid, Layers, Map as MapIcon, ChevronRight } from 'lucide-react'
import { usePermissions } from '@/core/auth/useAuth'

const TERRITORIES_MOCK = [
  { id: 't1', type: 'Region', name: 'North America', active: true, children: 3 },
  { id: 't2', type: 'Region', name: 'EMEA', active: true, children: 5 },
  { id: 't3', type: 'Region', name: 'APAC', active: false, children: 0 },
]

export function TerritoriesListPage() {
  const { can } = usePermissions()

  return (
    <div className="p-6 max-w-7xl mx-auto space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-neutral-900 dark:text-neutral-100">Territories & Geography</h1>
          <p className="text-sm text-neutral-500 dark:text-neutral-400">Manage hierarchical regions, zones, sectors, and sales routes.</p>
        </div>
        
        {can('territories.manage') && (
          <button className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors">
            <Plus className="h-4 w-4" />
            Add Territory
          </button>
        )}
      </div>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        
        <div className="md:col-span-1 space-y-2">
          <div className="font-semibold text-sm uppercase tracking-wider text-neutral-500 mb-4">Structure Level</div>
          
          <button className="w-full flex items-center justify-between p-3 rounded-lg bg-primary/10 text-primary border border-primary/20">
            <div className="flex items-center gap-3">
              <MapIcon className="h-5 w-5" />
              <span className="font-medium">Regions</span>
            </div>
            <div className="bg-white/50 text-xs px-2 py-0.5 rounded-full">12</div>
          </button>
          
          <button className="w-full flex items-center justify-between p-3 rounded-lg hover:bg-neutral-100 dark:hover:bg-neutral-800/50 text-neutral-700 dark:text-neutral-300 border border-transparent">
            <div className="flex items-center gap-3">
              <Grid className="h-5 w-5 text-neutral-400" />
              <span className="font-medium">Zones</span>
            </div>
            <div className="bg-neutral-100 dark:bg-neutral-800 text-xs px-2 py-0.5 rounded-full">48</div>
          </button>
          
          <button className="w-full flex items-center justify-between p-3 rounded-lg hover:bg-neutral-100 dark:hover:bg-neutral-800/50 text-neutral-700 dark:text-neutral-300 border border-transparent">
            <div className="flex items-center gap-3">
              <Layers className="h-5 w-5 text-neutral-400" />
              <span className="font-medium">Sectors</span>
            </div>
            <div className="bg-neutral-100 dark:bg-neutral-800 text-xs px-2 py-0.5 rounded-full">120</div>
          </button>

          <button className="w-full flex items-center justify-between p-3 rounded-lg hover:bg-neutral-100 dark:hover:bg-neutral-800/50 text-neutral-700 dark:text-neutral-300 border border-transparent">
            <div className="flex items-center gap-3">
              <MapPin className="h-5 w-5 text-neutral-400" />
              <span className="font-medium">Routes</span>
            </div>
            <div className="bg-neutral-100 dark:bg-neutral-800 text-xs px-2 py-0.5 rounded-full">250</div>
          </button>
        </div>

        <div className="md:col-span-3 bg-white dark:bg-neutral-900 shadow-sm border border-neutral-200 dark:border-neutral-800 rounded-lg">
          <div className="p-4 border-b border-neutral-200 dark:border-neutral-800 flex items-center justify-between">
            <h2 className="font-semibold text-lg">Sub-Regions</h2>
            <div className="relative w-64">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-neutral-400" />
              <input 
                type="text" 
                placeholder="Search..." 
                className="w-full pl-9 pr-4 py-1.5 bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm"
              />
            </div>
          </div>
          <div className="divide-y divide-neutral-200 dark:divide-neutral-800">
            {TERRITORIES_MOCK.map((territory) => (
              <div key={territory.id} className="p-4 flex items-center justify-between hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors cursor-pointer group">
                <div className="flex items-center gap-4">
                  <div className={`h-10 w-10 rounded-lg flex items-center justify-center text-primary ${territory.active ? 'bg-primary/10' : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-400'}`}>
                    <MapPin className="h-5 w-5" />
                  </div>
                  <div>
                    <h3 className={`font-medium ${!territory.active && 'text-neutral-500 line-through'}`}>{territory.name}</h3>
                    <p className="text-sm text-neutral-500">{territory.children} Zones inside</p>
                  </div>
                </div>
                <div className="flex items-center gap-4">
                  <span className={`text-xs px-2 py-1 rounded-full ${territory.active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                    {territory.active ? 'Active' : 'Archived'}
                  </span>
                  <ChevronRight className="h-5 w-5 text-neutral-400 group-hover:text-primary transition-colors" />
                </div>
              </div>
            ))}
          </div>
        </div>

      </div>
    </div>
  )
}

export default TerritoriesListPage;
