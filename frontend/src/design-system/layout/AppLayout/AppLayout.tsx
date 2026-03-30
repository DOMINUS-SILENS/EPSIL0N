import { Outlet, Link, useLocation } from '@tanstack/react-router'
import { cn } from '@/lib/utils'
import { useUIStore } from '@/core/state/stores/authStore'
import { useAuth } from '@/core/auth/useAuth'
import {
  Settings,
  ChevronLeft,
  ChevronRight,
  LogOut,
  Menu,
} from 'lucide-react'
import { CommandPalette } from '@/features/commandPalette/CommandPalette'
import { NotificationsDropdown } from '@/features/notifications/NotificationsDropdown'
import { UserMenu } from '@/features/user/UserMenu'
import { CompanySwitcher } from '@/features/company/CompanySwitcher'
import { ModuleMenu } from '@/components/navigation/ModuleMenu'
import { ContextMenu } from '@/components/navigation/ContextMenu'
import { Button } from '@/design-system/primitives/Button/Button'

export function AppLayout() {
  const location = useLocation()
  const { sidebarCollapsed, toggleSidebar } = useUIStore()
  const { logout } = useAuth()

  // Get active module key - Hard-gated for 72H Survival Mode
  const activeModuleKey = (() => {
    if (location.pathname === '/') return 'dashboard'
    const segment = location.pathname.split('/')[1]
    const map: Record<string, string> = {
      dashboard: 'dashboard',
      crm: 'crm',
      sales: 'sales',
      erp: 'erp',
      inventory: 'inventory',
      settings: 'settings',
      // all others disabled
    }
    return map[segment] || 'dashboard'
  })()

  return (
    <div className="flex h-screen overflow-hidden bg-neutral-50 dark:bg-neutral-950">
      <CommandPalette />

      {/* Sidebar */}
      <aside className={cn(
        'flex flex-col border-r border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900',
        sidebarCollapsed ? 'w-16' : 'w-64'
      )}>
        {/* Header */}
        <div className="flex h-14 items-center justify-between px-3 border-b border-neutral-200 dark:border-neutral-800 shrink-0">
          {!sidebarCollapsed && (
            <div className="flex items-center gap-2 px-1">
              <div className="h-6 w-6 rounded bg-primary flex items-center justify-center font-bold text-white text-xs">ε</div>
              <span className="text-lg font-black tracking-tight text-neutral-900 dark:text-neutral-100">EPSILON</span>
            </div>
          )}
          <Button variant="ghost" size="icon" onClick={toggleSidebar}>
            {sidebarCollapsed ? <ChevronRight className="h-4 w-4" /> : <ChevronLeft className="h-4 w-4" />}
          </Button>
        </div>

        {/* Page Navigation for active module */}
        <ContextMenu collapsed={sidebarCollapsed} moduleKey={activeModuleKey} />

        {/* Footer */}
        <div className="border-t border-neutral-200 dark:border-neutral-800 p-2 space-y-1 shrink-0">
          <Link
            to="/settings/general"
            className={cn(
              'flex items-center gap-3 px-3 py-2 rounded-md',
              sidebarCollapsed ? 'justify-center' : 'text-sm font-medium',
              'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800 transition-colors',
              activeModuleKey === 'settings' ? 'bg-primary/10 text-primary' : ''
            )}
            title={sidebarCollapsed ? 'Settings' : undefined}
          >
            <Settings className="h-5 w-5" />
            {!sidebarCollapsed && <span>System Settings</span>}
          </Link>
          <button
            onClick={() => logout()}
            className={cn(
              'flex items-center gap-3 px-3 py-2 rounded-md w-full transition-colors',
              sidebarCollapsed ? 'justify-center' : 'text-sm font-medium',
              'text-red-600 hover:bg-red-50 dark:text-red-500 dark:hover:bg-red-500/10'
            )}
            title={sidebarCollapsed ? 'Logout' : undefined}
          >
            <LogOut className="h-5 w-5" />
            {!sidebarCollapsed && <span>Secure Logout</span>}
          </button>
        </div>
      </aside>

      {/* Main Area */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Top Bar */}
        <header className="flex h-14 shrink-0 items-center justify-between border-b border-neutral-200 bg-white px-4 dark:border-neutral-800 dark:bg-neutral-900 shadow-sm z-10">
          <div className="flex items-center gap-4 flex-1">
            <Button variant="ghost" size="icon" className="md:hidden" onClick={toggleSidebar}>
              <Menu className="h-5 w-5 text-neutral-600 dark:text-neutral-300" />
            </Button>
            {/* Module Menu - Top Horizontal */}
            <ModuleMenu collapsed={false} horizontal />
          </div>
          <div className="flex items-center gap-3">
            <div className="hidden sm:block">
              <CompanySwitcher />
            </div>
            <div className="h-5 w-px bg-neutral-200 dark:bg-neutral-700 mx-1 hidden sm:block"></div>
            <NotificationsDropdown />
            <UserMenu />
          </div>
        </header>

        {/* Content Router Output */}
        <main className="flex-1 overflow-y-auto bg-neutral-50 dark:bg-[#0a0a0a]">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
