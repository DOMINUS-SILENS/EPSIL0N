# 🚀 EPSILON Frontend - Developer Implementation Guide

## Quick Start Code Examples

### 1. Geographic Hierarchy Store (Zustand)

```typescript
// src/core/state/useGeography.ts
import { create } from 'zustand'
import { persist } from 'zustand/middleware'

export interface Zone {
  id: string
  name: string
  country: string
  timezone: string
  currency: string
  description?: string
}

export interface Region {
  id: string
  name: string
  zoneId: string
  centerLat: number
  centerLng: number
  code: string
}

export interface Depot {
  id: string
  name: string
  regionId: string
  address: string
  city: string
  lat: number
  lng: number
  capacity: number
  managerId: string
  type: 'warehouse' | 'distribution' | 'transit'
  status: 'active' | 'inactive' | 'maintenance'
}

interface GeographyState {
  zones: Zone[]
  regions: Region[]
  depots: Depot[]

  // Getters
  getZoneById: (id: string) => Zone | null
  getRegionsByZone: (zoneId: string) => Region[]
  getDepotsByRegion: (regionId: string) => Depot[]
  getDepotsByZone: (zoneId: string) => Depot[]

  // Setters
  setZones: (zones: Zone[]) => void
  setRegions: (regions: Region[]) => void
  setDepots: (depots: Depot[]) => void

  // Mutations
  addZone: (zone: Zone) => void
  updateZone: (id: string, updates: Partial<Zone>) => void
  deleteZone: (id: string) => void

  // Sync
  syncFromBackend: () => Promise<void>
}

export const useGeography = create<GeographyState>()(
  persist(
    (set, get) => ({
      zones: [],
      regions: [],
      depots: [],

      // Getters
      getZoneById: (id) => get().zones.find(z => z.id === id) || null,

      getRegionsByZone: (zoneId) =>
        get().regions.filter(r => r.zoneId === zoneId),

      getDepotsByRegion: (regionId) =>
        get().depots.filter(d => d.regionId === regionId),

      getDepotsByZone: (zoneId) => {
        const regionIds = get()
          .regions
          .filter(r => r.zoneId === zoneId)
          .map(r => r.id)
        return get()
          .depots
          .filter(d => regionIds.includes(d.regionId))
      },

      // Mutations
      setZones: (zones) => set({ zones }),
      setRegions: (regions) => set({ regions }),
      setDepots: (depots) => set({ depots }),

      addZone: (zone) =>
        set(state => ({ zones: [...state.zones, zone] })),

      updateZone: (id, updates) =>
        set(state => ({
          zones: state.zones.map(z =>
            z.id === id ? { ...z, ...updates } : z
          ),
        })),

      deleteZone: (id) =>
        set(state => ({
          zones: state.zones.filter(z => z.id !== id),
          regions: state.regions.filter(r => r.zoneId !== id),
          depots: state.depots.filter(d => {
            const region = state.regions.find(r => r.id === d.regionId)
            return region?.zoneId !== id
          }),
        })),

      // Sync from backend
      syncFromBackend: async () => {
        try {
          const [zones, regions, depots] = await Promise.all([
            api.get('/configuration/zones'),
            api.get('/configuration/regions'),
            api.get('/configuration/depots'),
          ])
          set({
            zones: zones.data,
            regions: regions.data,
            depots: depots.data,
          })
        } catch (error) {
          console.error('Failed to sync geography:', error)
        }
      },
    }),
    {
      name: 'geography-store',
      partialize: (state) => ({
        zones: state.zones,
        regions: state.regions,
        depots: state.depots,
      }),
    }
  )
)
```

### 2. Permission System

```typescript
// src/core/state/usePermissions.ts
interface Permission {
  id: string
  resource: string        // 'orders', 'crm', 'inventory'
  action: string         // 'view', 'create', 'edit', 'delete'
  scope: 'own' | 'zone' | 'region' | 'depot' | 'all'
  conditions?: {
    status?: string[]
    value?: { min?: number; max?: number }
    clientId?: string
  }
}

interface Role {
  id: string
  name: string
  permissions: Permission[]
  dashboardWidgets: string[]
  modules: string[]
  features: Record<string, boolean>
}

interface UserAssignment {
  userId: string
  email: string
  role: Role
  zoneId?: string
  regionIds: string[]
  depots: string[]
  clientIds: string[]
}

export const usePermissions = () => {
  const user = useAuthStore(s => s.user) as UserAssignment
  const { getZoneById, getRegionsByZone, getDepotsByRegion } = useGeography()

  // Check if user has permission
  const hasPermission = (
    resource: string,
    action: string,
    context?: { zone?: string; region?: string; depot?: string }
  ): boolean => {
    const permission = user.role.permissions.find(
      p => p.resource === resource && p.action === action
    )

    if (!permission) return false

    // Check scope
    switch (permission.scope) {
      case 'all':
        return true
      case 'zone':
        return context?.zone === user.zoneId
      case 'region':
        return user.regionIds.includes(context?.region)
      case 'depot':
        return user.depots.includes(context?.depot)
      case 'own':
        return true // Own records only (enforced on backend)
      default:
        return false
    }
  }

  return {
    can: hasPermission,
    canView: (resource: string, context?) =>
      hasPermission(resource, 'view', context),
    canCreate: (resource: string, context?) =>
      hasPermission(resource, 'create', context),
    canEdit: (resource: string, context?) =>
      hasPermission(resource, 'edit', context),
    canDelete: (resource: string, context?) =>
      hasPermission(resource, 'delete', context),
    canExport: (resource: string) =>
      hasPermission(resource, 'export'),
    hasFeature: (feature: string) =>
      user.role.features[feature] ?? false,
  }
}
```

### 3. Dynamic Route Generator

```typescript
// src/app/routes/dynamicRouter.ts
import { createRootRoute, createRoute, createRouter, RootRoute } from '@tanstack/react-router'
import { useSystemConfig } from '@/core/state'
import { usePermissions } from '@/core/hooks'

export function createDynamicRouter() {
  const { modules } = useSystemConfig()
  const { can } = usePermissions()

  // Root
  const rootRoute = createRootRoute({
    component: () => <Outlet />,
  })

  // Auth routes
  const authRoute = createRoute({
    getParentRoute: () => rootRoute,
    path: '/login',
    component: LoginPage,
  })

  // Protected layout
  const protectedRoute = createRoute({
    getParentRoute: () => rootRoute,
    id: 'protected',
    component: AppLayout,
    beforeLoad: ({ location }) => {
      const auth = useAuthStore.getState()
      if (!auth.isAuthenticated) {
        throw redirect({ to: '/login' })
      }
    },
  })

  // Dashboard
  const dashboardRoute = createRoute({
    getParentRoute: () => protectedRoute,
    path: '/',
    component: DashboardPage,
  })

  // Geographic context routes
  const zoneRoute = createRoute({
    getParentRoute: () => protectedRoute,
    path: '/zone/:zoneId',
    component: ZoneContextLayout,
    beforeLoad: ({ params }) => {
      const { getZoneById } = useGeography.getState()
      if (!getZoneById(params.zoneId)) {
        throw new Error('Zone not found')
      }
      useUserContext.setState({ selectedZone: params.zoneId })
    },
  })

  const regionRoute = createRoute({
    getParentRoute: () => zoneRoute,
    path: '/region/:regionId',
    component: RegionContextLayout,
    beforeLoad: ({ params }) => {
      useUserContext.setState({ selectedRegion: params.regionId })
    },
  })

  const depotRoute = createRoute({
    getParentRoute: () => protectedRoute,
    path: '/depot/:depotId',
    component: DepotContextLayout,
    beforeLoad: ({ params }) => {
      useUserContext.setState({ selectedDepot: params.depotId })
    },
  })

  // Dynamically generate module routes
  const moduleRoutes = Object.values(modules).flatMap(module => {
    if (!module.enabled) return []

    const moduleRoute = createRoute({
      getParentRoute: () => protectedRoute,
      id: module.id,
      path: `/${module.id}`,
      component: () => <Outlet />,
    })

    const pages = module.pages
      .filter(page => can(page.permissions))
      .map(page =>
        createRoute({
          getParentRoute: () => moduleRoute,
          path: page.path.replace(`/${module.id}`, ''),
          component: () => import(`@/modules/${module.id}/pages/${page.component}`),
        })
      )

    return [moduleRoute, ...pages]
  })

  // Admin routes
  const settingsRoute = createRoute({
    getParentRoute: () => protectedRoute,
    path: '/settings',
    component: () => <Outlet />,
  })

  const moduleManagerRoute = createRoute({
    getParentRoute: () => settingsRoute,
    path: '/modules',
    component: ModuleManager,
    beforeLoad: () => {
      if (!can('system', 'admin')) {
        throw redirect({ to: '/' })
      }
    },
  })

  // Build router
  const routeTree = rootRoute.addChildren([
    authRoute,
    protectedRoute.addChildren([
      dashboardRoute,
      zoneRoute,
      regionRoute,
      depotRoute,
      ...moduleRoutes,
      settingsRoute.addChildren([moduleManagerRoute]),
    ]),
  ])

  return createRouter({ routeTree, context: { auth: useAuthStore.getState() } })
}
```

### 4. Smart DataTable Component

```typescript
// src/design-system/composite/DataTable/ConfigurableDataTable.tsx
import { useReactTable, getCoreRowModel } from '@tanstack/react-table'
import { usePermissions } from '@/core/hooks'

interface DataTableColumn {
  key: string
  label: string
  sortable?: boolean
  filterable?: boolean
  renderer?: (value: any, row: any) => React.ReactNode
  type?: 'text' | 'number' | 'date' | 'currency' | 'status'
  hidden?: boolean
}

interface ConfigurableDataTableProps {
  data: any[]
  columns: DataTableColumn[]
  onRowClick?: (row: any) => void
  canEdit?: boolean
  canDelete?: boolean
  canExport?: boolean
  defaultFilters?: Record<string, any>
  selectable?: boolean
  onBulkAction?: (action: string, rows: any[]) => void
}

export function ConfigurableDataTable({
  data,
  columns,
  onRowClick,
  canEdit,
  canDelete,
  canExport,
  defaultFilters,
  selectable,
  onBulkAction,
}: ConfigurableDataTableProps) {
  const [columnVisibility, setColumnVisibility] = useState<Record<string, boolean>>({})
  const [filters, setFilters] = useState(defaultFilters || {})
  const [rowSelection, setRowSelection] = useState({})
  const { can } = usePermissions()

  // Filter data
  const filteredData = data.filter(row =>
    Object.entries(filters).every(([key, value]) => {
      if (!value) return true
      const cellValue = row[key]
      if (Array.isArray(value)) {
        return value.includes(cellValue)
      }
      return String(cellValue).includes(String(value))
    })
  )

  // TanStack Table
  const table = useReactTable({
    data: filteredData,
    columns: columns
      .filter(col => columnVisibility[col.key] !== false)
      .map(col => ({
        accessorKey: col.key,
        header: col.label,
        cell: (info) => {
          const value = info.getValue()
          if (col.renderer) return col.renderer(value, info.row.original)

          switch (col.type) {
            case 'currency':
              return formatCurrency(value)
            case 'date':
              return format(new Date(value), 'MMM dd, yyyy')
            case 'status':
              return <StatusBadge status={value} />
            default:
              return value
          }
        },
      })),
    getCoreRowModel: getCoreRowModel(),
    state: { columnVisibility, rowSelection },
    onColumnVisibilityChange: setColumnVisibility,
    onRowSelectionChange: setRowSelection,
  })

  const selectedRows = Object.entries(rowSelection)
    .filter(([_, selected]) => selected)
    .map(([index]) => filteredData[parseInt(index)])

  return (
    <div className="space-y-4">
      {/* Filters */}
      <div className="flex gap-2 flex-wrap">
        {columns
          .filter(col => col.filterable)
          .map(col => (
            <Input
              key={col.key}
              placeholder={`Filter ${col.label}...`}
              value={filters[col.key] || ''}
              onChange={(e) =>
                setFilters(prev => ({ ...prev, [col.key]: e.target.value }))
              }
              className="w-40"
            />
          ))}
      </div>

      {/* Actions */}
      <div className="flex gap-2">
        {canEdit && <Button>Edit</Button>}
        {canDelete && (
          <Button
            variant="destructive"
            onClick={() =>
              onBulkAction?.('delete', selectedRows)
            }
            disabled={selectedRows.length === 0}
          >
            Delete ({selectedRows.length})
          </Button>
        )}
        {canExport && (
          <Button
            onClick={() =>
              exportToCSV(filteredData, columns)
            }
          >
            Export
          </Button>
        )}
      </div>

      {/* Table */}
      <div className="border rounded-lg overflow-x-auto">
        <table className="w-full">
          <thead>
            {table.getHeaderGroups().map(headerGroup => (
              <tr key={headerGroup.id} className="border-b">
                {selectable && (
                  <th className="p-2 w-12">
                    <input
                      type="checkbox"
                      onChange={(e) => {
                        const newSelection = {}
                        filteredData.forEach((_, i) => {
                          newSelection[i] = e.target.checked
                        })
                        setRowSelection(newSelection)
                      }}
                      checked={
                        filteredData.length > 0 &&
                        Object.values(rowSelection).every(v => v)
                      }
                    />
                  </th>
                )}
                {headerGroup.headers.map(header => (
                  <th
                    key={header.id}
                    className="p-3 text-left font-semibold cursor-pointer hover:bg-gray-50"
                    onClick={() => {
                      if (columns.find(c => c.key === header.id)?.sortable) {
                        // Sort logic
                      }
                    }}
                  >
                    {header.isPlaceholder
                      ? null
                      : flexRender(
                          header.column.columnDef.header,
                          header.getContext()
                        )}
                  </th>
                ))}
              </tr>
            ))}
          </thead>
          <tbody>
            {table.getRowModel().rows.map((row) => (
              <tr
                key={row.id}
                className="border-b hover:bg-gray-50 cursor-pointer"
                onClick={() => onRowClick?.(row.original)}
              >
                {selectable && (
                  <td className="p-2">
                    <input
                      type="checkbox"
                      checked={rowSelection[row.index] || false}
                      onChange={(e) => {
                        setRowSelection(prev => ({
                          ...prev,
                          [row.index]: e.target.checked,
                        }))
                      }}
                      onClick={(e) => e.stopPropagation()}
                    />
                  </td>
                )}
                {row.getVisibleCells().map((cell) => (
                  <td key={cell.id} className="p-3">
                    {flexRender(cell.column.columnDef.cell, cell.getContext())}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {filteredData.length === 0 && (
        <div className="text-center py-8 text-gray-500">
          No data found
        </div>
      )}
    </div>
  )
}
```

### 5. Dynamic Sidebar

```typescript
// src/components/Navigation/DynamicSidebar.tsx
export function DynamicSidebar() {
  const { modules } = useSystemConfig()
  const { getZoneById, getRegionsByZone } = useGeography()
  const { can } = usePermissions()
  const { selectedZone, selectedRegion } = useUserContext()
  const [collapsed, setCollapsed] = useState(false)

  // Generate visible modules
  const visibleModules = Object.values(modules)
    .filter(m => m.enabled && can(m.id, 'view'))

  return (
    <aside
      className={cn(
        'flex flex-col border-r transition-all',
        collapsed ? 'w-16' : 'w-64'
      )}
    >
      {/* Header */}
      <div className="p-4 border-b flex items-center justify-between">
        {!collapsed && <Logo />}
        <Button
          variant="ghost"
          size="icon"
          onClick={() => setCollapsed(!collapsed)}
        >
          {collapsed ? <ChevronRight /> : <ChevronLeft />}
        </Button>
      </div>

      <div className="flex-1 overflow-y-auto">
        {/* Geographic Context */}
        <div className="p-4 border-b space-y-3">
          {!collapsed && (
            <p className="text-xs font-semibold text-gray-500">YOUR SCOPE</p>
          )}

          <ZoneSelector />
          <RegionMultiSelect />
          <DepotSelector />
        </div>

        {/* Modules Navigation */}
        <nav className="p-4 space-y-2">
          {!collapsed && (
            <p className="text-xs font-semibold text-gray-500 px-2">MODULES</p>
          )}

          {visibleModules.map(module => (
            <NavItem
              key={module.id}
              label={module.label}
              icon={module.icon}
              href={`/${module.id}`}
              collapsed={collapsed}
              badge={module.unreadCount}
            >
              {/* Sub-pages */}
              {module.pages
                .filter(p => can(p.permissions))
                .map(page => (
                  <NavSubItem
                    key={page.id}
                    label={page.label}
                    href={page.path}
                    collapsed={collapsed}
                  />
                ))}
            </NavItem>
          ))}
        </nav>

        {/* Quick Actions */}
        <div className="p-4 border-t space-y-2">
          {!collapsed && (
            <p className="text-xs font-semibold text-gray-500">QUICK ACTIONS</p>
          )}

          <QuickActionButton
            icon={<Plus className="w-4 h-4" />}
            label="New Order"
            collapsed={collapsed}
            onClick={() => navigate('/sales/orders/new')}
          />

          <QuickActionButton
            icon={<UserPlus className="w-4 h-4" />}
            label="New Lead"
            collapsed={collapsed}
            onClick={() => navigate('/crm/leads/new')}
          />
        </div>
      </div>

      {/* Footer */}
      <div className="p-4 border-t space-y-2">
        <Button
          variant="ghost"
          className="w-full justify-start"
          size="sm"
        >
          <Settings className="w-4 h-4 mr-2" />
          {!collapsed && 'Settings'}
        </Button>
      </div>
    </aside>
  )
}
```

### 6. Usage Examples in Components

```typescript
// src/app/providers/AppRoot.tsx
import { useGeography } from '@/core/state'
import { createDynamicRouter } from '@/app/routes'

export function AppRoot() {
  // Initialize geography on app load
  useEffect(() => {
    const { syncFromBackend } = useGeography.getState()
    syncFromBackend()
  }, [])

  // Create dynamic router
  const router = createDynamicRouter()

  return <RouterProvider router={router} />
}

// Usage in a page component
import { ConfigurableDataTable } from '@/design-system'
import { usePermissions } from '@/core/hooks'

export function OrdersListPage() {
  const { canEdit, canDelete, canExport } = usePermissions()
  const { data: orders } = useQuery({
    queryKey: ['orders'],
    queryFn: () => api.get('/orders'),
  })

  const columns = [
    { key: 'id', label: 'Order ID', sortable: true },
    { key: 'client_name', label: 'Client', sortable: true },
    { key: 'total', label: 'Total', type: 'currency' as const },
    { key: 'status', label: 'Status', type: 'status' as const },
    { key: 'created_at', label: 'Date', type: 'date' as const },
  ]

  return (
    <div>
      <PageHeader title="Orders" />
      <ConfigurableDataTable
        data={orders?.data || []}
        columns={columns}
        canEdit={canEdit('orders')}
        canDelete={canDelete('orders')}
        canExport={canExport('orders')}
        onRowClick={(order) => navigate(`/sales/orders/${order.id}`)}
      />
    </div>
  )
}
```

---

## 📦 Installation & Setup

### Step 1: Copy Components from ProtoFrontEnd

```bash
# Copy all UI components
cp -r ProtoForntEnd/components/ui/* \
  frontend/src/design-system/primitives/

# Copy ERP components
cp -r ProtoForntEnd/components/erp/* \
  frontend/src/design-system/composite/

# Copy styling
cp ProtoForntEnd/globals.css \
  frontend/src/index.css
```

### Step 2: Install Dependencies

```bash
# Add Zustand if not present
npm install zustand

# Add TanStack Router if not present
npm install @tanstack/react-router

# Ensure Radix UI is installed
npm install @radix-ui/react-*

# Recharts for charts
npm install recharts
```

### Step 3: Update tsconfig.json

```json
{
  "compilerOptions": {
    "paths": {
      "@/*": ["./src/*"],
      "@/core/*": ["./src/core/*"],
      "@/modules/*": ["./src/modules/*"],
      "@/design-system/*": ["./src/design-system/*"],
      "@/components/*": ["./src/components/*"]
    }
  }
}
```

### Step 4: Create Store Files

```bash
mkdir -p frontend/src/core/state

# Create store files
touch frontend/src/core/state/useGeography.ts
touch frontend/src/core/state/useSystemConfig.ts
touch frontend/src/core/state/useUserContext.ts
touch frontend/src/core/state/useDashboard.ts
```

---

## 🧪 Testing Examples

```typescript
// src/__tests__/stores/geography.test.ts
import { useGeography } from '@/core/state'

describe('useGeography', () => {
  let store

  beforeEach(() => {
    store = useGeography.getState()
  })

  it('should filter regions by zone', () => {
    const mockZone = { id: 'west', name: 'West Coast' } as Zone
    const mockRegions = [
      { id: 'ca', zoneId: 'west', name: 'California' },
      { id: 'tx', zoneId: 'south', name: 'Texas' },
    ] as Region[]

    store.setZones([mockZone])
    store.setRegions(mockRegions)

    const regions = store.getRegionsByZone('west')
    expect(regions).toHaveLength(1)
    expect(regions[0].id).toBe('ca')
  })

  it('should get depots by zone', () => {
    // Test implementation
  })
})
```

---

## 🚀 Deployment Checklist

- [ ] All stores created and tested
- [ ] Dynamic router implemented
- [ ] Permission system enforced
- [ ] Components migrated from prototype
- [ ] Admin panel completed
- [ ] Backend APIs integrated
- [ ] Real-time sync working
- [ ] Offline support verified
- [ ] Performance optimized
- [ ] Security audit passed
- [ ] User documentation ready

---

**Implementation Guide Complete** ✓
Ready for development sprint!
