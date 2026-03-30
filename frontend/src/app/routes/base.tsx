import { createRootRoute, createRoute, Outlet } from '@tanstack/react-router'
import { AppLayout } from '@/design-system/layout/AppLayout/AppLayout'

// The root layout
export const rootRoute = createRootRoute({
  component: () => <Outlet />,
})

// Protected Application Shell
export const protectedRoute = createRoute({
  getParentRoute: () => rootRoute,
  id: 'protected',
  component: AppLayout,
})
