# EPSILON Frontend Architecture

## Overview
This is a **React + TypeScript + Vite** ERP frontend with a modular, feature-based architecture using TanStack Router for routing and Zustand for state management.

---

## Project Structure

```
src/
├── app/                    # Application-level configurations
│   ├── providers/          # Context providers (QueryProvider)
│   ├── Router.tsx          # Main routing configuration
│   └── ...
├── core/                   # Core infrastructure
│   ├── api/                # API client (axios-based)
│   ├── auth/               # Authentication hooks & errors
│   ├── state/stores/       # Zustand stores (auth, UI)
│   └── ...
├── components/             # Shared UI components
│   ├── navigation/         # ModuleMenu, ContextMenu
│   └── ...
├── design-system/          # Design system components
│   ├── primitives/         # Button, Input, Card
│   ├── composite/          # DataTable, Card
│   └── layout/             # AppLayout
├── features/               # Feature-specific components
│   ├── auth/               # LoginPage
│   ├── commandPalette/     # CommandPalette
│   ├── company/            # CompanySwitcher
│   ├── notifications/      # NotificationsDropdown
│   └── user/               # UserMenu
├── modules/                # Business domain modules
│   ├── accounting/         # Invoices, Bills, Payments, Reconciliation
│   ├── core/               # Users, Roles, Territories, Profile
│   ├── crm/                # Leads, Opportunities, Customers
│   ├── delivery/           # Fleet, Tours
│   ├── hr/                 # Employees, Appraisals
│   ├── inventory/          # Warehouses, Stock
│   ├── projects/           # Project management
│   ├── purchasing/         # Purchase Orders
│   ├── reports/            # Dashboard, Analytics
│   ├── sales/              # Orders, Quotes
│   └── settings/           # System settings
└── main.tsx                # Application entry point
```

---

## 1. Application Entry Point

**File:** `src/main.tsx`

```typescript
import React from 'react'
import ReactDOM from 'react-dom/client'
import { RouterProvider } from '@tanstack/react-router'
import { QueryProvider } from './app/providers/QueryProvider'
import { router } from './app/Router'
import './index.css'

ReactDOM.createRoot(document.getElementById('root')!).render(
  <React.StrictMode>
    <QueryProvider>
      <RouterProvider router={router} />
    </QueryProvider>
  </React.StrictMode>
)
```

**Key Points:**
- Uses React 18's `createRoot` API
- Wraps app with `QueryProvider` (TanStack Query) for data fetching
- Uses `RouterProvider` from TanStack Router

---

## 2. Routing System

**File:** `src/app/Router.tsx`

### Route Hierarchy

```
root ( Outlet only )
├── /login                  → LoginPage (public)
└── protected (AppLayout)
    ├── /                   → DashboardPage
    ├── /core/*             → Core module
    ├── /crm/*              → CRM module
    ├── /sales/*            → Sales module
    ├── /inventory/*        → Inventory module
    ├── /accounting/*       → Accounting module
    ├── /purchasing/*       → Purchasing module
    ├── /hr/*               → HR module
    ├── /delivery/*         → Delivery module
    ├── /reports/*          → Reports module
    ├── /projects/*         → Projects module
    ├── /ecommerce/*        → Ecommerce module
    └── /settings/*         → Settings module
```

### Route Definition Pattern

```typescript
// Base route creation
const rootRoute = createRootRoute({ component: () => <Outlet /> })

// Layout routes
const protectedRoute = createRoute({
  getParentRoute: () => rootRoute,
  id: 'protected',
  component: AppLayout,
})

// Module routes import from module routers
const coreRoute = createRoute({ getParentRoute: () => protectedRoute, path: 'core' })
export const coreRouteTree = coreRoute.addChildren([/* child routes */])

// Final tree assembly
const routeTree = rootRoute.addChildren([
  authRoute,
  protectedRoute.addChildren([dashboardRoute, coreRouteTree, crmRouteTree, ...])
])

export const router = createRouter({ routeTree })
```

---

## 3. State Management

### Auth Store (`src/core/state/stores/authStore.ts`)

**Zustand + Persist Middleware**

```typescript
interface AuthState {
  user: User | null           // { id, name, email, company_id, company_name }
  permissions: string[]       // Permission strings
  isAuthenticated: boolean
  setUser: (user) => void
  setPermissions: (perms) => void
  clearAuth: () => void
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      user: null,
      permissions: [],
      isAuthenticated: false,
      setUser: (user) => set({ user, isAuthenticated: !!user }),
      setPermissions: (permissions) => set({ permissions }),
      clearAuth: () => set({ user: null, permissions: [], isAuthenticated: false }),
    }),
    { name: 'auth-storage' }  // localStorage key
  )
)
```

### UI Store (same file)

```typescript
interface UIState {
  sidebarCollapsed: boolean
  sidebarWidth: number
  commandPaletteOpen: boolean
  theme: 'light' | 'dark' | 'system'
  toggleSidebar: () => void
  setCommandPaletteOpen: (open) => void
  setTheme: (theme) => void
}
```

---

## 4. Authentication System

### useAuth Hook (`src/core/auth/useAuth.ts`)

```typescript
export const useAuth = () => {
  const queryClient = useQueryClient()
  const { setUser, setPermissions, clearAuth } = useAuthStore()

  // User query - fetches current user
  const { data: user, isLoading } = useQuery({
    queryKey: ['auth', 'user'],
    queryFn: async () => {
      const { data } = await api.get<User>('/user')
      setUser(data)
      setPermissions(data.permissions)
      return data
    },
    retry: false,
  })

  // Login mutation
  const loginMutation = useMutation({
    mutationFn: async (credentials) => {
      await api.get('/sanctum/csrf-cookie')  // Laravel Sanctum
      const { data } = await api.post('/login', credentials)
      return data
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['auth', 'user'] })
      window.location.href = '/dashboard'
    },
  })

  // Logout mutation
  const logoutMutation = useMutation({
    mutationFn: async () => await api.post('/logout'),
    onSuccess: () => {
      clearAuth()
      queryClient.clear()
      window.location.href = '/login'
    },
  })

  return { user, isLoading, login, logout, isAuthenticated: !!user }
}
```

### usePermissions Hook

```typescript
export const usePermissions = () => {
  const { permissions } = useAuthStore()
  
  return {
    can: (permission) => permissions?.includes(permission) ?? false,
    canAny: (perms) => perms.some(p => permissions?.includes(p)),
    canAll: (perms) => perms.every(p => permissions?.includes(p)),
  }
}
```

---

## 5. API Client

**File:** `src/core/api/client.ts`

### Axios Configuration

```typescript
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  withCredentials: true,  // For Laravel Sanctum cookies
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})
```

### Interceptors

**Request Interceptor:**
- Fetches CSRF cookie for mutating requests (POST/PUT/PATCH/DELETE)
- Adds idempotency keys for command operations

**Response Interceptor:**
- 401 → Redirect to /login
- 403 → "Permission denied" toast
- 409 → "Conflict detected" toast
- 422 → Returns validation errors
- 429 → "Too many requests" toast
- 500+ → "Server error" toast

### Types

```typescript
interface DomainEvent {
  event_id: string
  aggregate_type: string
  aggregate_id: number
  event_type: string
  event_data: Record<string, unknown>
  event_time: string
  sequence: number
  event_hash: string
}

interface CommandResponse<T> {
  success: boolean
  events: DomainEvent[]
  data?: T
  error?: string
}
```

---

## 6. Layout System

**File:** `src/design-system/layout/AppLayout/AppLayout.tsx`

### Layout Structure

```
┌─────────────────────────────────────────────────────────────────┐
│  Sidebar (w-16 or w-64)      │  Main Area                      │
│  ┌─────────────────────────┐ │  ┌───────────────────────────┐  │
│  │ Header (Logo + Toggle)    │ │  │ Top Bar                   │  │
│  ├─────────────────────────┤ │  │ • Search (Cmd+K)          │  │
│  │ Module Menu (vertical)  │ │  │ • Company Switcher        │  │
│  │ • Dashboard             │ │  │ • Notifications           │  │
│  │ • CRM                   │ │  │ • User Menu               │  │
│  │ • Sales                 │ │  └───────────────────────────┘  │
│  │ • Inventory             │ │  ┌───────────────────────────┐  │
│  │ • ...                   │ │  │                           │  │
│  ├─────────────────────────┤ │  │     <Outlet />            │  │
│  │ Context Menu (pages)    │ │  │     (Page content)        │  │
│  ├─────────────────────────┤ │  │                           │  │
│  │ Footer                  │ │  └───────────────────────────┘  │
│  │ • Settings              │ │                                 │
│  │ • Logout                │ │                                 │
│  └─────────────────────────┘ │                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Active Module Detection

```typescript
const activeModuleKey = (() => {
  if (location.pathname === '/') return 'dashboard'
  const segment = location.pathname.split('/')[1]
  const map: Record<string, string> = {
    core: 'core', crm: 'crm', sales: 'sales', inventory: 'inventory',
    purchasing: 'purchasing', accounting: 'accounting', hr: 'hr',
    delivery: 'delivery', reports: 'reports', projects: 'projects',
    ecommerce: 'ecommerce', settings: 'settings'
  }
  return map[segment] || 'dashboard'
})()
```

---

## 7. Module System

### Module Structure (Example: CRM)

```
src/modules/crm/
├── CrmRouter.tsx           # Route definitions
├── pages/
│   ├── LeadsListPage.tsx
│   ├── OpportunitiesListPage.tsx
│   └── CustomersListPage.tsx
└── components/             # CRM-specific components
```

### Router Pattern

```typescript
// CrmRouter.tsx
import { crmRoute } from '@/app/Router'

export const leadsListRoute = createRoute({
  getParentRoute: () => crmRoute,
  path: '/leads',
  component: LeadsListPage,
})

export const crmRouteTree = crmRoute.addChildren([
  leadsListRoute,
  opportunitiesListRoute,
  customersListRoute,
])
```

---

## 8. Navigation Components

### ModuleMenu (`src/components/navigation/ModuleMenu.tsx`)

**Module Configuration:**

```typescript
const modules = [
  { key: 'dashboard', label: 'Dashboard', path: '/', icon: LayoutDashboard },
  { key: 'crm', label: 'CRM', path: '/crm/leads', icon: UserCircle },
  { key: 'sales', label: 'Sales', path: '/sales/orders', icon: ShoppingCart },
  { key: 'inventory', label: 'Inventory', path: '/inventory/warehouses', icon: Package },
  { key: 'purchasing', label: 'Purchasing', path: '/purchasing/orders', icon: Container },
  { key: 'accounting', label: 'Accounting', path: '/accounting/invoices', icon: Calculator },
  { key: 'hr', label: 'HR', path: '/hr/employees', icon: Users },
  { key: 'delivery', label: 'Delivery', path: '/delivery/fleet', icon: Truck },
  { key: 'projects', label: 'Projects', path: '/projects/list', icon: Briefcase },
  { key: 'ecommerce', label: 'eCommerce', path: '/ecommerce/orders', icon: Globe },
  { key: 'reports', label: 'Reports', path: '/reports/dashboard', icon: BarChart3 },
  { key: 'core', label: 'Core Users', path: '/core/users', icon: ShieldAlert },
]
```

### CommandPalette

- Triggered by Cmd+K or search box focus
- Radix UI Dialog-based
- Currently shows placeholder "No results"

---

## 9. Data Fetching (TanStack Query)

**Provider:** `src/app/providers/QueryProvider.tsx`

### Configuration

```typescript
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 5 * 60 * 1000,    // 5 minutes
      gcTime: 30 * 60 * 1000,      // 30 minutes (garbage collection)
      refetchOnWindowFocus: false,
      retry: (failureCount, error) => {
        if (error instanceof AuthError) return false
        return failureCount < 3
      },
    },
  },
})
```

### Features

- React Query Devtools (disabled by default)
- Sonner toast notifications
- AuthError handling (no retry on auth failures)

---

## 10. Design System

### Primitives

- **Button:** `src/design-system/primitives/Button/Button.tsx`
- **Input:** `src/design-system/primitives/Input/Input.tsx`

### Composite

- **Card:** `src/design-system/composite/Card/Card.tsx`
- **DataTable:** `src/design-system/composite/DataTable/DataTable.tsx`

### Styling

- **Tailwind CSS** for styling
- **CSS Variables** for theming (light/dark)
- **Inter** font from Google Fonts
- Dark mode support via `.dark` class

---

## 11. Environment Configuration

### Vite Proxy (vite.config.ts)

```typescript
server: {
  port: 5173,
  proxy: {
    '/api': {
      target: 'http://localhost:8000',
      changeOrigin: true,
    },
    '/sanctum': {
      target: 'http://localhost:8000',
      changeOrigin: true,
    },
  },
}
```

### Environment Variables

```
VITE_API_URL=http://localhost:8000/api
```

---

## 12. Key Dependencies

| Package | Purpose |
|---------|---------|
| react + react-dom | UI library |
| @tanstack/react-router | Routing |
| @tanstack/react-query | Data fetching |
| zustand | State management |
| axios | HTTP client |
| tailwindcss | Styling |
| lucide-react | Icons |
| sonner | Toast notifications |
| @radix-ui/react-dialog | Headless UI primitives |

---

## 13. Build & Dev

### Commands

```bash
# Development
npm run dev          # Starts Vite dev server on port 5173

# Build
npm run build        # TypeScript compile + Vite build
npm run preview      # Preview production build

# Lint
npm run lint         # ESLint check
```

### TypeScript Configuration

- `tsconfig.json` - Base config
- `tsconfig.app.json` - App-specific
- `tsconfig.node.json` - Vite/Node-specific

### Path Aliases

```typescript
// vite.config.ts
resolve: {
  alias: {
    '@': path.resolve(__dirname, './src'),
  },
}
```

Used throughout: `import { something } from '@/core/api/client'`
