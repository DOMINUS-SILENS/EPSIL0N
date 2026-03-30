# Frontend Prototype Porting Plan

## Executive Summary

This plan outlines the migration of components and modules from `ProtoForntEnd` to the production frontend. The prototype contains rich ERP UI components including DataTable, KPI Cards, Charts, and full module pages (Dashboard, Distribution, Analytics, Production) that need to be ported.

---

## Current State Analysis

### Prototype (`ProtoForntEnd/`)
**Framework:** Next.js + shadcn/ui + Tailwind CSS + Recharts
**Components Available:**
- `erp/data-table.tsx` - Generic DataTable with StatusBadge
- `erp/kpi-card.tsx` - KPI card component with trends
- `erp/charts.tsx` - Recharts-based chart components (Area, Bar, Pie, Line)
- `erp/app-sidebar.tsx` - Navigation sidebar
- `erp/app-header.tsx` - App header

**Pages:**
- `/` - Dashboard with KPIs, charts, work orders, inventory
- `/distribution` - Shipments and logistics
- `/analytics` - Analytics dashboard
- `/production` - Production work orders and inventory
- `/commercial` - CRM/Commercial module
- `/settings` - Settings

### Production Frontend (`frontend/`)
**Framework:** React + Vite + Tanstack Router + shadcn/ui + Tailwind CSS
**Current Structure:**
- Uses Tanstack Router for routing
- Has `AppLayout` with navigation
- Routes: `/login`, `/core`, `/crm`, `/sales`, `/inventory`, `/settings`
- Has sync infrastructure (batchSync, deltaSync, etc.)
- Has test suite with Vitest

---

## Phase 1: Component Porting (High Priority)

### 1.1 ERP Components

| Component | Source | Target | Status | Notes |
|-----------|--------|--------|--------|-------|
| `KPICard` | `components/erp/kpi-card.tsx` | `src/components/erp/KPICard.tsx` | ◻️ TODO | Port as-is, uses Lucide icons |
| `DataTable` | `components/erp/data-table.tsx` | `src/components/erp/DataTable.tsx` | ◻️ TODO | Port with StatusBadge |
| `StatusBadge` | `components/erp/data-table.tsx` | `src/components/erp/StatusBadge.tsx` | ◻️ TODO | Extract from DataTable |
| `ChartCard` | `components/erp/charts.tsx` | `src/components/erp/ChartCard.tsx` | ◻️ TODO | Card wrapper for charts |
| `RevenueChart` | `components/erp/charts.tsx` | `src/components/erp/charts/RevenueChart.tsx` | ◻️ TODO | Area chart |
| `OrdersChart` | `components/erp/charts.tsx` | `src/components/erp/charts/OrdersChart.tsx` | ◻️ TODO | Bar chart |
| `ProductionPieChart` | `components/erp/charts.tsx` | `src/components/erp/charts/ProductionPieChart.tsx` | ◻️ TODO | Pie chart |
| `DistributionChart` | `components/erp/charts.tsx` | `src/components/erp/charts/DistributionChart.tsx` | ◻️ TODO | Line chart |

### 1.2 Install Dependencies

```bash
cd /home/badji/EPSILON/frontend
npm install recharts lucide-react
```

### 1.3 Create Component Index

Create `src/components/erp/index.ts`:
```typescript
export { KPICard } from './KPICard'
export { DataTable, StatusBadge } from './DataTable'
export { ChartCard } from './ChartCard'
export { RevenueChart } from './charts/RevenueChart'
export { OrdersChart } from './charts/OrdersChart'
export { ProductionPieChart } from './charts/ProductionPieChart'
export { DistributionChart } from './charts/DistributionChart'
```

---

## Phase 2: Dashboard Implementation (High Priority)

### 2.1 Create Dashboard Route

**File:** `src/app/routes/dashboard.tsx`

Add route to `src/app/routes.tsx`:
```typescript
export const dashboardRoute = createRoute({
  getParentRoute: () => protectedRoute,
  path: '/',
  component: DashboardPage,
})
```

### 2.2 Dashboard Page Structure

**File:** `src/app/pages/DashboardPage.tsx`

**Sections:**
1. **KPI Cards Row** (4 cards)
   - Production Output (with trend)
   - Active Work Orders
   - Inventory Alerts
   - On-Time Delivery Rate

2. **Charts Section** (2 columns)
   - Monthly Orders (BarChart)
   - Production Status (PieChart)

3. **Work Orders Table**
   - Columns: ID, Product, Qty, Status, Priority, Due Date, Progress
   - Uses DataTable + StatusBadge

4. **Inventory Alerts Table**
   - Columns: SKU, Name, Qty, Min Stock, Status, Location
   - Uses DataTable + StatusBadge

### 2.3 Sample Data (for development)

Create `src/app/pages/dashboard/sampleData.ts`:
- productionData (pie chart)
- monthlyOutput (bar chart)
- workOrders (table)
- inventoryItems (table)

---

## Phase 3: Module Pages (Medium Priority)

### 3.1 Distribution Module

**Route:** `/distribution`
**File:** `src/app/pages/DistributionPage.tsx`

**Features:**
- Shipments overview
- Delivery tracking
- Distribution charts (DistributionChart)
- Filters and search

### 3.2 Analytics Module

**Route:** `/analytics`
**File:** `src/app/pages/AnalyticsPage.tsx`

**Features:**
- Revenue chart
- KPI trends over time
- Data export
- Date range filters

### 3.3 Production Module

**Route:** `/production`
**File:** `src/app/pages/ProductionPage.tsx`

**Features:**
- Work order management
- Production schedule
- Inventory management
- Progress tracking

---

## Phase 4: Route Configuration (Medium Priority)

### 4.1 Update Routes

**File:** `src/app/routes.tsx`

Add new routes:
```typescript
// New module routes
export const dashboardRoute = createRoute({
  getParentRoute: () => protectedRoute,
  path: '/',
  component: DashboardPage,
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

export const productionRoute = createRoute({
  getParentRoute: () => protectedRoute,
  path: 'production',
  component: ProductionPage,
})
```

### 4.2 Navigation Update

Update `AppLayout` or sidebar to include new routes in navigation.

---

## Phase 5: Integration & Testing (Medium Priority)

### 5.1 Integration Checklist

- [ ] Components render without errors
- [ ] Charts display with sample data
- [ ] DataTable sorts and filters work
- [ ] StatusBadge shows correct colors
- [ ] KPI Cards display trends correctly
- [ ] Routes navigate correctly
- [ ] Responsive layout on mobile/tablet

### 5.2 Add Tests

Create tests for ported components:
- `src/components/erp/__tests__/KPICard.test.tsx`
- `src/components/erp/__tests__/DataTable.test.tsx`
- `src/components/erp/__tests__/StatusBadge.test.tsx`
- `src/components/erp/__tests__/ChartCard.test.tsx`

---

## Phase 6: Backend Integration (Low Priority - Future)

### 6.1 Replace Sample Data

Once backend endpoints are ready:
- Replace `sampleData.ts` with API calls
- Use `useQuery` for data fetching
- Integrate with batchSync for mutations

### 6.2 Real-time Updates

- Use `useOptimizedServerSentEvents` for live data
- Update KPIs in real-time
- Refresh tables on data changes

---

## File Structure After Implementation

```
frontend/src/
├── components/
│   ├── erp/
│   │   ├── KPICard.tsx
│   │   ├── DataTable.tsx
│   │   ├── StatusBadge.tsx
│   │   ├── ChartCard.tsx
│   │   ├── charts/
│   │   │   ├── RevenueChart.tsx
│   │   │   ├── OrdersChart.tsx
│   │   │   ├── ProductionPieChart.tsx
│   │   │   └── DistributionChart.tsx
│   │   └── index.ts
│   └── ...
├── app/
│   ├── routes.tsx (updated)
│   └── pages/
│       ├── DashboardPage.tsx
│       ├── DistributionPage.tsx
│       ├── AnalyticsPage.tsx
│       └── ProductionPage.tsx
└── ...
```

---

## Migration Notes

### CSS/Styling
- Prototype uses `oklch()` color values - convert to Tailwind classes
- Use Tailwind's color palette (`bg-primary`, `text-destructive`, etc.)
- Keep dark mode support (already in prototype)

### Component Adaptations
- Remove Next.js specific imports (`"use client"`)
- Replace `@/` imports with `@/` (already configured in vite.config.ts)
- Convert Next.js `Link` to Tanstack Router `Link`

### Charts
- Recharts is already compatible with React/Vite
- May need to adjust for responsive containers
- Consider lazy loading chart components

---

## Estimated Timeline

| Phase | Duration | Priority |
|-------|----------|----------|
| Phase 1: Components | 2-3 hours | High |
| Phase 2: Dashboard | 3-4 hours | High |
| Phase 3: Module Pages | 4-6 hours | Medium |
| Phase 4: Routes | 1-2 hours | Medium |
| Phase 5: Testing | 2-3 hours | Medium |
| **Total** | **12-18 hours** | |

---

## Next Steps

1. **Start with Phase 1** - Port the core components (KPICard, DataTable, StatusBadge)
2. **Install recharts** dependency
3. **Create component index** for clean exports
4. **Move to Phase 2** - Build Dashboard page with sample data
5. **Iterate** through remaining phases

Would you like me to start implementing any specific phase?
