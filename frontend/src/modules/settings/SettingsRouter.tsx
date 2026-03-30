import { createRoute } from '@tanstack/react-router'
import { protectedRoute } from '@/app/routes/base'

export const settingsRoute = createRoute({
  getParentRoute: () => protectedRoute,
  path: 'settings',
})
import { GeneralSettingsPage } from './pages/GeneralSettingsPage'
import { SecuritySettingsPage } from './pages/SecuritySettingsPage'
import { SequencesPage } from './pages/SequencesPage'
import { IntegrationsPage } from './pages/IntegrationsPage'

export const generalSettingsRoute = createRoute({
  getParentRoute: () => settingsRoute,
  path: 'general',
  component: GeneralSettingsPage,
})

export const securitySettingsRoute = createRoute({
  getParentRoute: () => settingsRoute,
  path: 'security',
  component: SecuritySettingsPage,
})

export const sequencesRoute = createRoute({
  getParentRoute: () => settingsRoute,
  path: 'sequences',
  component: SequencesPage,
})

export const integrationsRoute = createRoute({
  getParentRoute: () => settingsRoute,
  path: 'integrations',
  component: IntegrationsPage,
})

export const settingsRouteTree = settingsRoute.addChildren([
  generalSettingsRoute,
  securitySettingsRoute,
  sequencesRoute,
  integrationsRoute,
])
