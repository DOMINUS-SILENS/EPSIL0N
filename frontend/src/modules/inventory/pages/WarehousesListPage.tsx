
import { Plus, Search, Building2, Map, LayoutGrid, MoreVertical, MapPin } from 'lucide-react'
import { usePermissions } from '@/core/auth/useAuth'

const MOCK_WAREHOUSES = [
  { id: '1', name: 'New York Central (NYC-1)', type: 'Main Hub', location: 'New York, US', zones: 12, capacity: '85%', active: true },
  { id: '2', name: 'London Distribution (LON-2)', type: 'Distribution Center', location: 'London, UK', zones: 8, capacity: '60%', active: true },
  { id: '3', name: 'Berlin Forward Proxy (BER-1)', type: 'Transit Node', location: 'Berlin, DE', zones: 3, capacity: '95%', active: true },
  { id: '4', name: 'SF Overflow (SF-3)', type: 'Overflow', location: 'San Francisco, US', zones: 5, capacity: '0%', active: false },
]

export function WarehousesListPage() {
  const { can } = usePermissions()

  return (
    <div className="p-6 max-w-7xl mx-auto space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-neutral-900 dark:text-neutral-100">Warehouses & Locations</h1>
          <p className="text-sm text-neutral-500 dark:text-neutral-400">Map physical locations, zones, and logical routing</p>
        </div>
        
        {can('warehouses.manage') && (
          <button className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors font-medium text-sm">
            <Plus className="h-4 w-4" />
            Add Location
          </button>
        )}
      </div>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        
        {/* Hierarchy Sidebar */}
        <div className="md:col-span-1 border border-neutral-200 dark:border-neutral-800 rounded-lg bg-white dark:bg-neutral-900 p-4 shadow-sm h-max">
          <div className="font-semibold text-sm uppercase tracking-wider text-neutral-500 mb-4 px-2">Topology Map</div>
          
          <div className="space-y-1">
            <button className="w-full flex items-center justify-between p-2 rounded-md bg-primary/10 text-primary font-medium text-sm">
              <div className="flex items-center gap-2">
                <Building2 className="h-4 w-4" />
                Warehouses
              </div>
              <span className="bg-white/50 px-1.5 py-0.5 rounded text-[10px]">12</span>
            </button>
            <button className="w-full flex items-center justify-between p-2 rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-800 text-neutral-600 dark:text-neutral-300 transition-colors text-sm">
              <div className="flex items-center gap-2">
                <Map className="h-4 w-4" />
                Zones & Aisles
              </div>
            </button>
            <button className="w-full flex items-center justify-between p-2 rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-800 text-neutral-600 dark:text-neutral-300 transition-colors text-sm">
              <div className="flex items-center gap-2">
                <LayoutGrid className="h-4 w-4" />
                Bin Locations
              </div>
            </button>
          </div>
        </div>

        {/* List View */}
        <div className="md:col-span-3 bg-white dark:bg-neutral-900 shadow-sm border border-neutral-200 dark:border-neutral-800 rounded-lg overflow-hidden flex flex-col">
          <div className="p-4 border-b border-neutral-200 dark:border-neutral-800 flex items-center justify-between">
            <div className="relative w-full max-w-sm">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-neutral-400" />
              <input 
                type="text" 
                placeholder="Search warehouses..." 
                className="w-full pl-9 pr-4 py-1.5 bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm"
              />
            </div>
          </div>

          <table className="w-full text-left text-sm whitespace-nowrap">
            <thead className="bg-neutral-50 dark:bg-neutral-800/50 text-neutral-500 dark:text-neutral-400">
              <tr>
                <th className="px-6 py-3 font-medium">Warehouse Name</th>
                <th className="px-6 py-3 font-medium">Location</th>
                <th className="px-6 py-3 font-medium">Zones</th>
                <th className="px-6 py-3 font-medium">Capacity</th>
                <th className="px-6 py-3 font-medium">Status</th>
                <th className="px-6 py-3 font-medium text-right"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
              {MOCK_WAREHOUSES.map((wh) => (
                <tr key={wh.id} className="hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors cursor-pointer group">
                  <td className="px-6 py-4">
                    <div className="font-semibold text-neutral-900 dark:text-neutral-100">{wh.name}</div>
                    <div className="text-xs text-neutral-500 mt-0.5">{wh.type}</div>
                  </td>
                  <td className="px-6 py-4 text-neutral-600 dark:text-neutral-400">
                    <div className="flex items-center gap-1.5">
                      <MapPin className="h-3.5 w-3.5" />
                      {wh.location}
                    </div>
                  </td>
                  <td className="px-6 py-4 text-neutral-600 dark:text-neutral-400">
                    {wh.zones} Active Zones
                  </td>
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-2">
                      <div className="w-16 h-2 bg-neutral-200 dark:bg-neutral-700 rounded-full overflow-hidden">
                        <div 
                          className={`h-full rounded-full ${parseInt(wh.capacity) > 90 ? 'bg-red-500' : 'bg-green-500'}`} 
                          style={{ width: wh.capacity }} 
                        />
                      </div>
                      <span className="text-xs font-medium text-neutral-600 dark:text-neutral-400">{wh.capacity}</span>
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider ${
                      wh.active ? 'bg-green-100 text-green-700' : 'bg-neutral-100 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400'
                    }`}>
                      {wh.active ? 'Active' : 'Archived'}
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

export default WarehousesListPage;
