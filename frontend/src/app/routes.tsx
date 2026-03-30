import { createRoute, createRootRoute, Outlet } from '@tanstack/react-router'
import { AppLayout } from '@/design-system/layout/AppLayout/AppLayout'
import { LoginPage } from '@/features/auth/LoginPage'
import { DashboardPage } from '@/app/pages/DashboardPage'
import { ProductionPage } from '@/app/pages/ProductionPage'
import { DistributionPage } from '@/app/pages/DistributionPage'
import { AnalyticsPage } from '@/app/pages/AnalyticsPage'

// 72H Survival Mode — Explicit Route Gating
// ENABLED_MODULES filtered in UI components

// Root layout
export const rootRoute = createRootRoute({
  component: () => <Outlet />,
})

// Authentication
export const authRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/login',
  component: LoginPage,
})

// Protected Application Shell
export const protectedRoute = createRoute({
  getParentRoute: () => rootRoute,
  id: 'protected',
  component: AppLayout,
})

// === New Module Pages ===
export const dashboardRoute = createRoute({
  getParentRoute: () => protectedRoute,
  path: '/',
  component: DashboardPage,
})

export const productionRoute = createRoute({
  getParentRoute: () => protectedRoute,
  path: 'production',
  component: ProductionPage,
})

export const distributionRoute = createRoute({
  getParentRoute: () => protectedRoute,
  path: 'distribution',
  component: DistributionPage,
})

export const analyticsRoute = createRoute({
  getParentRoute: () => protectedRoute,
  path: 'analytics',
  component: AnalyticsPage,
})

// === Base Module Endpoints (Gated) ===
export const coreRoute = createRoute({ getParentRoute: () => protectedRoute, path: 'core' })
export const crmRoute = createRoute({ getParentRoute: () => protectedRoute, path: 'crm' })
export const salesRoute = createRoute({ getParentRoute: () => protectedRoute, path: 'sales' })
export const inventoryRoute = createRoute({ getParentRoute: () => protectedRoute, path: 'inventory' })
export const settingsRoute = createRoute({ getParentRoute: () => protectedRoute, path: 'settings' })

// Note: accounting, purchasing, hr, delivery, reports, projects, ecommerce, etc. are disabled for Day 1
