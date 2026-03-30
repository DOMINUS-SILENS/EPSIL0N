import { createRoute } from '@tanstack/react-router'
import { protectedRoute } from '@/app/routes/base'

export const salesRoute = createRoute({
  getParentRoute: () => protectedRoute,
  path: 'sales',
})
import { OrdersListPage } from './pages/OrdersListPage'
import { OrderDetailPage } from './pages/OrderDetailPage'


export const ordersListRoute = createRoute({
  getParentRoute: () => salesRoute,
  path: 'orders',
  component: OrdersListPage,
})

export const orderDetailRoute = createRoute({
  getParentRoute: () => salesRoute,
  path: 'orders/$id',
  component: OrderDetailPage,
})




export const salesRouteTree = salesRoute.addChildren([
  ordersListRoute,
  orderDetailRoute,
])
