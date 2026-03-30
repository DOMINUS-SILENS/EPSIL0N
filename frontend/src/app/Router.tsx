import { createRouter, createRoute } from '@tanstack/react-router'
import { LoginPage } from '@/features/auth/LoginPage'
import { DashboardPage } from '@/app/pages/DashboardPage'

import { rootRoute, protectedRoute } from './routes/base'
import { settingsRouteTree } from '@/modules/settings/SettingsRouter'
import { coreRouteTree } from '@/modules/core/CoreRouter'
import { crmRouteTree } from '@/modules/crm/CrmRouter'
import { inventoryRouteTree } from '@/modules/inventory/InventoryRouter'
import { salesRouteTree } from '@/modules/sales/SalesRouter'

// Authentication
const authRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/login',
  component: LoginPage,
})

// Direct Dashboard
const dashboardRoute = createRoute({
  getParentRoute: () => protectedRoute,
  path: '/',
  component: DashboardPage,
})

// Complete route tree
const routeTree = rootRoute.addChildren([
  authRoute,
  protectedRoute.addChildren([
    dashboardRoute,
    settingsRouteTree,
    coreRouteTree,
    crmRouteTree,
    inventoryRouteTree,
    salesRouteTree,
  ]),
])

export const router = createRouter({ routeTree })


declare module '@tanstack/react-router' {
  interface Register {
    router: typeof router
  }
}
