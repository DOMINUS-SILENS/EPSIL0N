import { createRoute } from '@tanstack/react-router'
import { protectedRoute } from '@/app/routes/base'

export const inventoryRoute = createRoute({
  getParentRoute: () => protectedRoute,
  path: 'inventory',
})

import { ProductsListPage } from './pages/ProductsListPage'
import { WarehousesListPage } from './pages/WarehousesListPage'
import { StockMovementsPage } from './pages/StockMovementsPage'
import { StockAdjustmentsPage } from './pages/StockAdjustmentsPage'

export const productsListRoute = createRoute({
  getParentRoute: () => inventoryRoute,
  path: 'products',
  component: ProductsListPage,
})

export const productDetailRoute = createRoute({
  getParentRoute: () => inventoryRoute,
  path: 'products/$id',
  component: ProductsListPage, // Placeholder
})

export const newProductRoute = createRoute({
  getParentRoute: () => inventoryRoute,
  path: 'products/new',
  component: ProductsListPage, // Placeholder
})

export const warehousesRoute = createRoute({
  getParentRoute: () => inventoryRoute,
  path: 'warehouses',
  component: WarehousesListPage,
})

export const stockMovementsRoute = createRoute({
  getParentRoute: () => inventoryRoute,
  path: 'movements',
  component: StockMovementsPage,
})

export const stockAdjustmentsRoute = createRoute({
  getParentRoute: () => inventoryRoute,
  path: 'adjustments',
  component: StockAdjustmentsPage,
})

export const inventoryRouteTree = inventoryRoute.addChildren([
  productsListRoute,
  productDetailRoute,
  newProductRoute,
  warehousesRoute,
  stockMovementsRoute,
  stockAdjustmentsRoute,
])
