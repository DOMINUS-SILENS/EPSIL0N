import { Link, useLocation } from '@tanstack/react-router'
import { cn } from '@/lib/utils'

interface ContextMenuProps {
  collapsed: boolean
  moduleKey: string
}

// 72H Survival Mode — Production Perimeter
const modulePages: Record<string, { label: string; path: string }[]> = {
  dashboard: [
    { label: 'Executive Overview', path: '/' }
  ],
  production: [
    { label: 'Dashboard', path: '/production' },
    { label: 'Work Orders', path: '/production/work-orders' },
    { label: 'Inventory', path: '/production/inventory' },
    { label: 'Quality Control', path: '/production/quality' },
    { label: 'Planning', path: '/production/planning' },
  ],
  distribution: [
    { label: 'Dashboard', path: '/distribution' },
    { label: 'Shipments', path: '/distribution/shipments' },
    { label: 'Warehouses', path: '/distribution/warehouses' },
    { label: 'Deliveries', path: '/distribution/deliveries' },
    { label: 'Routes', path: '/distribution/routes' },
  ],
  analytics: [
    { label: 'Overview', path: '/analytics' },
    { label: 'Sales', path: '/analytics/sales' },
    { label: 'Operations', path: '/analytics/operations' },
    { label: 'Customers', path: '/analytics/customers' },
  ],
  crm: [
    { label: 'Leads Inbox', path: '/crm/leads' },
    { label: 'Sales Pipeline', path: '/crm/opportunities' },
    { label: 'Master Customers', path: '/crm/customers' },
  ],
  sales: [
    { label: 'Sales Orders', path: '/sales/orders' },
  ],
  inventory: [
    { label: 'Products Master', path: '/inventory/products' },
    { label: 'Warehouse Topology', path: '/inventory/warehouses' },
    { label: 'Stock Movements', path: '/inventory/movements' },
  ],
  erp: [
    { label: 'Products Master', path: '/erp/products' },
  ],
  settings: [
    { label: 'General Configuration', path: '/settings/general' },
    { label: 'Security & Audit', path: '/settings/security' },
  ],
}

export function ContextMenu({ collapsed, moduleKey }: ContextMenuProps) {
  const location = useLocation()
  const pages = modulePages[moduleKey] || []

  return (
    <nav className="flex-1 overflow-y-auto p-2 scrollbar-hide">
      {!collapsed && pages.length > 0 && (
        <div className="px-2 mb-2 pt-2 text-xs font-bold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">
          {moduleKey === 'dashboard' ? 'Overview' : moduleKey}
        </div>
      )}
      <div className={cn(collapsed ? 'flex flex-col gap-1' : 'space-y-1')}>
        {pages.map(page => {
          const isActive = location.pathname === page.path || location.pathname.startsWith(page.path + '/')
          return (
            <Link
              key={page.path}
              to={page.path}
              className={cn(
                'flex items-center gap-3 rounded-md transition-all active:scale-[0.98]',
                collapsed
                  ? 'justify-center p-2'
                  : 'px-3 py-2 text-sm',
                isActive
                  ? 'bg-primary/10 text-primary font-bold dark:bg-primary/20 dark:text-primary'
                  : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800 font-medium'
              )}
              title={collapsed ? page.label : undefined}
            >
              <span className={cn('w-6 text-center shrink-0', collapsed ? 'text-lg font-bold' : 'text-xs opacity-60')}>
                {page.label.charAt(0).toUpperCase()}
              </span>
              {!collapsed && <span className="truncate flex-1">{page.label}</span>}
            </Link>
          )
        })}
      </div>
    </nav>
  )
}
