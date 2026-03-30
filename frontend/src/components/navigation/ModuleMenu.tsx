import { Link, useLocation } from '@tanstack/react-router'
import { cn } from '@/lib/utils'
import {
  LayoutDashboard,
  UserCircle,
  ShoppingCart,
  Package,
  Factory,
  Truck,
  BarChart3,
  Settings,
} from 'lucide-react'

// 72H Survival Mode — Production Perimeter
const modules = [
  { key: 'dashboard', label: 'Dashboard', path: '/', icon: LayoutDashboard },
  { key: 'production', label: 'Production', path: '/production', icon: Factory },
  { key: 'distribution', label: 'Distribution', path: '/distribution', icon: Truck },
  { key: 'analytics', label: 'Analytics', path: '/analytics', icon: BarChart3 },
  { key: 'crm', label: 'CRM', path: '/crm/leads', icon: UserCircle },
  { key: 'erp', label: 'Articles', path: '/erp/products', icon: Package },
  { key: 'inventory', label: 'Inventory', path: '/inventory/warehouses', icon: Package },
  { key: 'sales', label: 'Orders', path: '/sales/orders', icon: ShoppingCart },
  { key: 'settings', label: 'Settings', path: '/settings', icon: Settings },
]

interface ModuleMenuProps {
  collapsed: boolean
  horizontal?: boolean
}

export function ModuleMenu({ collapsed, horizontal }: ModuleMenuProps) {
  const location = useLocation()

  const activeKey = (() => {
    if (location.pathname === '/') return 'dashboard'
    const segment = location.pathname.split('/')[1]
    const map: Record<string, string> = {
      core: 'core',
      crm: 'crm',
      sales: 'sales',
      inventory: 'inventory',
      production: 'production',
      distribution: 'distribution',
      analytics: 'analytics',
      settings: 'settings',
    }
    return map[segment] || 'dashboard'
  })()

  if (horizontal) {
    return (
      <div className="flex items-center gap-1 overflow-x-auto scrollbar-hide">
        {modules.map(mod => (
          <Link
            key={mod.key}
            to={mod.path}
            className={cn(
              'flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors whitespace-nowrap text-sm',
              mod.key === activeKey
                ? 'bg-primary/10 text-primary font-medium'
                : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800'
            )}
          >
            <mod.icon className="h-4 w-4" />
            <span className="hidden lg:inline">{mod.label}</span>
          </Link>
        ))}
      </div>
    )
  }

  return (
    <div className={cn(collapsed ? 'flex flex-wrap gap-1 p-2' : 'space-y-1 p-2')}>
      {modules.map(mod => (
        <Link
          key={mod.key}
          to={mod.path}
          className={cn(
            ' rounded-md transition-colors',
            collapsed
              ? 'w-10 h-10 justify-center'
              : 'px-3 py-2 gap-3',
            mod.key === activeKey
              ? 'bg-primary/10 text-primary'
              : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800'
          )}
          title={collapsed ? mod.label : undefined}
        >
          <mod.icon className="h-5 w-5" />
          {!collapsed && <span className="text-sm font-medium">{mod.label}</span>}
        </Link>
      ))}
    </div>
  )
}
