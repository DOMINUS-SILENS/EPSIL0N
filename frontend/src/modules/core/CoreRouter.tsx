import { createRoute } from '@tanstack/react-router'
import { protectedRoute } from '@/app/routes/base'

export const coreRoute = createRoute({
  getParentRoute: () => protectedRoute,
  path: 'core',
})
import { UsersListPage } from './pages/UsersListPage'
import { RolesListPage } from './pages/RolesListPage'
import { TerritoriesListPage } from './pages/TerritoriesListPage'
import { ProfilePage } from './pages/ProfilePage'

export const usersRoute = createRoute({
  getParentRoute: () => coreRoute,
  path: 'users',
  component: UsersListPage,
})

export const rolesRoute = createRoute({
  getParentRoute: () => coreRoute,
  path: 'roles',
  component: RolesListPage,
})

export const territoriesRoute = createRoute({
  getParentRoute: () => coreRoute,
  path: 'territories',
  component: TerritoriesListPage,
})

export const profileRoute = createRoute({
  getParentRoute: () => coreRoute,
  path: 'profile',
  component: ProfilePage,
})


export const coreRouteTree = coreRoute.addChildren([
  usersRoute,
  rolesRoute,
  territoriesRoute,
  profileRoute
])
