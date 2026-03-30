// /home/badji/god-sfa-crm-frontend/src/design-system/layout/AppLayout/AppLayout.tsx
import React, { useState } from 'react'
import { Outlet, Link, useLocation } from '@tanstack/react-router'
import { cn } from '@/lib/utils'
import { Button } from '@/design-system/primitives/Button/Button'
import { useUIStore } from '@/core/state/stores/authStore'
import {
  LayoutDashboard,
  ShoppingCart,
  Package,
  FileText,
  Users,
  UserCircle,
  Settings,
  HelpCircle,
  LogOut,
  Menu,
  Search,
  Bell,
  ChevronLeft,
  ChevronRight,
} from 'lucide-react'
import { CommandPalette } from '@/features/commandPalette/CommandPalette'
import { NotificationsDropdown } from '@/features/notifications/NotificationsDropdown'
import { UserMenu } from '@/features/user/UserMenu'

interface NavItem {
  title: string
  href: string
  icon: React.ElementType
  children?: NavItem[]
  badge?: number
}

const navItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
    icon: LayoutDashboard,
  },
  {
    title: 'Sales',
    href: '/sales',
    icon: ShoppingCart,
    children: [
      { title: 'Orders', href: '/sales/orders', icon: ShoppingCart },
      { title: 'Quotations', href: '/sales/quotations', icon: FileText },
      { title: 'Returns', href: '/sales/returns', icon: Package },
    ],
  },
  {
    title: 'Inventory',
    href: '/inventory',
    icon: Package,
    children: [
      { title: 'Products', href: '/inventory/products', icon: Package },
      { title: 'Stock', href: '/inventory/stock', icon: Package },
      { title: 'Warehouses', href: '/inventory/warehouses', icon: Package },
    ],
  },
  {
    title: 'Accounting',
    href: '/accounting',
    icon: FileText,
    children: [
      { title: 'Journals', href: '/accounting/journals', icon: FileText },
      { title: 'Reports', href: '/accounting/reports', icon: FileText },
    ],
  },
  {
    title: 'HR',
    href: '/hr',
    icon: Users,
    children: [
      { title: 'Employees', href: '/hr/employees', icon: Users },
      { title: 'Payroll', href: '/hr/payroll', icon: FileText },
    ],
  },
  {
    title: 'CRM',
    href: '/crm',
    icon: UserCircle,
    children: [
      { title: 'Customers', href: '/crm/customers', icon: Users },
      { title: 'Leads', href: '/crm/leads', icon: UserCircle },
    ],
  },
]

export function AppLayout() {
  const location = useLocation()
  const { sidebarCollapsed, toggleSidebar, sidebarWidth } = useUIStore()
  const [expandedItems, setExpandedItems] = useState<string[]>([])

  const toggleExpand = (title: string) => {
    setExpandedItems(prev =>
      prev.includes(title) ? prev.filter(t => t !== title) : [...prev, title]
    )
  }

  return (
    <div className="flex h-screen overflow-hidden bg-neutral-50 dark:bg-neutral-950">
      {/* Command Palette */}
      <CommandPalette />

      {/* Sidebar */}
      <aside
        className={cn(
          'flex flex-col border-r border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900 transition-all duration-300',
          sidebarCollapsed ? 'w-16' : 'w-64'
        )}
        style={{ width: sidebarCollapsed ? 64 : sidebarWidth }}
      >
        {/* Logo */}
        <div className="flex h-14 items-center justify-between px-4 border-b border-neutral-200 dark:border-neutral-800">
          {!sidebarCollapsed && (
            <span className="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
              ERP System
            </span>
          )}
          <Button
            variant="ghost"
            size="icon"
            onClick={toggleSidebar}
            className="ml-auto"
          >
            {sidebarCollapsed ? <ChevronRight className="h-4 w-4" /> : <ChevronLeft className="h-4 w-4" />}
          </Button>
        </div>

        {/* Navigation */}
        <nav className="flex-1 overflow-y-auto py-4">
          {navItems.map((item) => (
            <div key={item.title}>
              <Link
                to={item.href}
                className={cn(
                  'flex items-center gap-3 px-4 py-2 text-sm text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800 transition-colors',
                  location.pathname === item.href && 'bg-neutral-100 text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100'
                )}
                onClick={() => item.children && toggleExpand(item.title)}
              >
                <item.icon className="h-5 w-5" />
                {!sidebarCollapsed && (
                  <>
                    <span className="flex-1">{item.title}</span>
                    {item.badge && (
                      <span className="rounded-full bg-primary px-2 py-0.5 text-xs text-white">
                        {item.badge}
                      </span>
                    )}
                  </>
                )}
              </Link>
              {!sidebarCollapsed && item.children && expandedItems.includes(item.title) && (
                <div className="ml-8">
                  {item.children.map((child) => (
                    <Link
                      key={child.title}
                      to={child.href}
                      className={cn(
                        'flex items-center gap-3 px-4 py-2 text-sm text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800 transition-colors',
                        location.pathname === child.href && 'bg-neutral-100 text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100'
                      )}
                    >
                      <child.icon className="h-4 w-4" />
                      <span>{child.title}</span>
                    </Link>
                  ))}
                </div>
              )}
            </div>
          ))}
        </nav>

        {/* Footer */}
        <div className="border-t border-neutral-200 dark:border-neutral-800 p-4">
          {!sidebarCollapsed && (
            <>
              <Link
                to="/settings"
                className="flex items-center gap-3 px-2 py-2 text-sm text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800 rounded-md"
              >
                <Settings className="h-5 w-5" />
                <span>Settings</span>
              </Link>
              <Link
                to="/help"
                className="flex items-center gap-3 px-2 py-2 text-sm text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800 rounded-md"
              >
                <HelpCircle className="h-5 w-5" />
                <span>Help</span>
              </Link>
              <button
                className="flex w-full items-center gap-3 px-2 py-2 text-sm text-error hover:bg-error/10 rounded-md"
                onClick={() => useAuth().logout()}
              >
                <LogOut className="h-5 w-5" />
                <span>Logout</span>
              </button>
            </>
          )}
        </div>
      </aside>

      {/* Main Content */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Header */}
        <header className="flex h-14 items-center justify-between border-b border-neutral-200 bg-white px-6 dark:border-neutral-800 dark:bg-neutral-900">
          <div className="flex items-center gap-4">
            <Button
              variant="ghost"
              size="icon"
              onClick={toggleSidebar}
            >
              <Menu className="h-5 w-5" />
            </Button>
            <div className="relative">
              <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
              <input
                type="text"
                placeholder="Search... (⌘K)"
                className="h-9 w-64 rounded-md border border-neutral-200 pl-9 pr-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-neutral-700"
                onFocus={() => useUIStore.getState().setCommandPaletteOpen(true)}
              />
            </div>
          </div>
          <div className="flex items-center gap-2">
            <NotificationsDropdown />
            <UserMenu />
          </div>
        </header>

        {/* Content */}
        <main className="flex-1 overflow-y-auto p-6">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
