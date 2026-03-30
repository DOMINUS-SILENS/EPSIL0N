# EPSILON ERP - Codebase Detailed Report
**Date**: March 27, 2026
**Project**: EPSILON ERP System (Backend & Frontend Analysis)

---

## 📋 Executive Summary

**EPSILON** is a comprehensive ERP system with a **modular, feature-rich architecture**:
- **Backend**: Laravel 11.50 (PHP 8.2+) with gRPC, OpenTelemetry, and Event Sourcing patterns
- **Frontend**: React 18 + TypeScript + Vite with TanStack Router/Query
- **Prototype**: Next.js alternative frontend (ProtoFrontEnd)
- **Status**: **Active Development** with TypeScript compilation issues to resolve

### Key Metrics
- **Backend**: Laravel 11 with Domain-Driven Design patterns
- **Frontend**: ~13 business modules + core infrastructure
- **Database**: MySQL with Redis caching/queues
- **Architecture**: Modular, feature-based with Event-driven patterns

---

## 🏗️ Project Structure

```
EPSILON/
├── erp/                      # Laravel Backend
│   ├── app/
│   │   ├── Aggregates/       # DDD Aggregates
│   │   ├── Commands/         # Command handlers
│   │   ├── Events/           # Domain events
│   │   ├── Services/         # Business logic (Sagas, Projectors, CRDT)
│   │   ├── Http/             # Controllers & API routes
│   │   ├── Models/           # Eloquent models
│   │   ├── GraphQL/          # GraphQL schema & resolvers
│   │   └── Contracts/        # Contract definitions
│   ├── database/             # Migrations & seeders
│   ├── routes/               # API resource routes
│   ├── tests/                # Unit & feature tests
│   ├── config/               # Configuration files
│   └── storage/              # Logs, cache
│
├── frontend/                 # React + Vite Frontend
│   ├── src/
│   │   ├── app/              # App configuration (routing, providers)
│   │   ├── core/             # Core infrastructure
│   │   │   ├── api/          # Axios client + interceptors
│   │   │   ├── auth/         # Authentication hooks
│   │   │   ├── state/        # Zustand stores
│   │   │   ├── offline/      # Offline support
│   │   │   └── realtime/     # WebSocket/realtime
│   │   ├── design-system/    # UI components & design tokens
│   │   ├── features/         # Cross-cutting features
│   │   ├── modules/          # Business domain modules
│   │   │   ├── accounting/
│   │   │   ├── crm/
│   │   │   ├── sales/
│   │   │   ├── inventory/
│   │   │   ├── hr/
│   │   │   ├── delivery/
│   │   │   ├── projects/
│   │   │   ├── purchasing/
│   │   │   └── ...
│   │   ├── components/       # Shared components
│   │   └── types/            # TypeScript interfaces
│   ├── dist/                 # Production build
│   └── node_modules/         # Dependencies
│
└── ProtoFrontEnd/            # Next.js Prototype
    ├── app/                  # App router
    ├── components/           # React components
    └── styles/               # Tailwind CSS
```

---

## 🔧 BACKEND ARCHITECTURE (erp/)

### Technology Stack

| Component | Technology | Version |
|-----------|-----------|---------|
| Framework | Laravel | 11.50.0 |
| Language | PHP | 8.2+ |
| Database | MySQL | - |
| Cache | Redis | - |
| Queue | Redis | - |
| Session | Database | - |
| RPC | gRPC | 1.74 |
| Observability | OpenTelemetry | 1.13 |
| Terminal | Redis Tinker | 2.11.1 |

### Key Dependencies

```json
{
  "production": {
    "laravel/framework": "^11.31",
    "grpc/grpc": "^1.74",
    "open-telemetry/api": "^1.8",
    "open-telemetry/sdk": "^1.13",
    "opis/json-schema": "^2.6",
    "predis/predis": "^3.4"
  },
  "development": {
    "phpunit/phpunit": "^11.0",
    "laravel/pint": "^1.13",
    "laravel/sail": "^1.26",
    "kitloong/laravel-migrations-generator": "^7.3"
  }
}
```

### Core Modules

#### 1. **Architecture Patterns**

**Event Sourcing & CQRS:**
- Location: `app/Services/` + `app/Events/`
- **Aggregates**: Domain aggregates with event handlers
- **Sagas**: Long-running transactions (`app/Services/Sagas/`)
- **Projectors**: Read model generators (`app/Services/Projectors/`)
- **CRDT**: Conflict-free replicated data types for eventual consistency

**Example Flow:**
```
User Action → Command Handler → Aggregate (validates)
→ Event(s) → Saga (orchestrates) → Projector (updates read model)
```

#### 2. **Models & Database**

**Eloquent Models** (`app/Models/`):
- All domain entities as models
- Scopes applied for filtering (`app/Models/Scopes/`)
- Relationships, soft deletes, timestamps

**Database Strategy:**
- MySQL primary database
- Redis for caching (`CACHE_STORE=database`)
- Queue connection: **Redis**
- Broadcast: **Redis**

#### 3. **HTTP Layer**

**Controllers** (`app/Http/Controllers/`):
- RESTful endpoints for each module
- Request validation via Form Requests (`app/Http/Requests/`)
- Response formatting via resource classes

**Authentication:**
- Laravel Sanctum (token-based + SPA cookies)
- CSRF protection
- Stateful domains configured for frontend cross-origin

**Configuration:**
```env
SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173,...
FRONTEND_URL=http://127.0.0.1:4175
```

#### 4. **GraphQL Support**

**Implementation** (`app/GraphQL/`):
- GraphQL Schema definitions (`Schema/`)
- Resolvers for queries/mutations (`Resolvers/`)
- Alternative to REST API

#### 5. **Middleware** (`app/Http/Middleware/`)

Common middleware:
- Authentication verification
- Authorization checks
- CORS handling
- Request/response logging

#### 6. **Commands & Scheduling** (`app/Console/Commands/`)

Custom Artisan commands for:
- Database seeding
- Cache clearing
- Event replay
- Data migration

#### 7. **Testing**

- **PHPUnit** for unit/feature tests
- **Mockery** for mocking
- Test location: `tests/`
- Coverage: Controllers, Services, Models

### Environment Configuration

```env
# Core
APP_ENV=local
APP_DEBUG=true
APP_TIMEZONE=UTC

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=crm_db
DB_USERNAME=laravel

# Cache & Queue
CACHE_STORE=database        # Could optimize to Redis
QUEUE_CONNECTION=redis      # Async processing
BROADCAST_CONNECTION=redis  # Real-time updates

# Mail
MAIL_MAILER=log            # Development only

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120 minutes
```

### Development Command

```bash
composer dev
# Runs concurrently: php artisan serve, queue:listen, pail, npm run dev
```

---

## ⚛️ FRONTEND ARCHITECTURE (frontend/)

### Technology Stack

| Layer | Technology | Purpose |
|-------|-----------|---------|
| Runtime | React 18 | UI library |
| Language | TypeScript 5.9 | Type safety |
| Build Tool | Vite 8 | Fast bundling |
| Routing | TanStack Router 1.168 | File-based routing alternative |
| State | Zustand 4.4 | Lightweight state management |
| Data Fetching | TanStack Query 5.8 | Server state sync |
| Forms | React Hook Form 7.48 | Efficient form handling |
| Validation | Zod 3.22 | Schema validation |
| UI Components | Radix UI + Tailwind | Headless + styling |
| HTTP | Axios 1.6 | API client |
| Offline | Dexie 3.2 | IndexedDB client |
| Maps | Leaflet 1.9 | Geographic data |

### Project Structure

#### **1. Application Entry (`src/app/`)**

**Router** (`src/app/Router.tsx`):
- TanStack Router configuration
- Route tree hierarchy
- Protected routes with AppLayout
- Module-based route composition

**Route Architecture:**
```
root
├── /login (public)
└── /protected (AppLayout)
    ├── / (Dashboard)
    ├── /core/* (Users, Roles, Territories)
    ├── /crm/* (Leads, Opportunities, Customers)
    ├── /sales/* (Orders, Quotes)
    ├── /inventory/* (Warehouses, Stock)
    ├── /accounting/* (Invoices, Bills, Payments)
    ├── /purchasing/* (Purchase Orders)
    ├── /hr/* (Employees, Appraisals)
    ├── /delivery/* (Fleet, Tours)
    ├── /projects/* (Project Management)
    ├── /reports/* (Dashboard, Analytics)
    ├── /ecommerce/* (Orders)
    └── /settings/* (System Configuration)
```

**Providers** (`src/app/providers/`):
- `QueryProvider`: TanStack Query client setup
- Default options: 5min stale time, 30min garbage collection
- Custom error handling for auth failures
- React Query DevTools included (dev only)

#### **2. Core Infrastructure (`src/core/`)**

**API Client** (`src/core/api/client.ts`):
```typescript
// Axios instance
- baseURL: http://localhost:8000/api
- withCredentials: true (for Sanctum)
- Content-Type: application/json

// Interceptors:
- Request: CSRF cookie fetch, idempotency keys
- Response:
  * 401 → /login redirect
  * 403 → "Permission denied" toast
  * 409 → "Conflict detected" toast
  * 422 → Validation errors
  * 429 → Rate limit toast
  * 500+ → Server error toast
```

**Authentication** (`src/core/auth/useAuth.ts`):

```typescript
interface AuthState {
  user: {
    id: number
    name: string
    email: string
    company_id: number
    company_name: string
    permissions: string[]
  }
  isAuthenticated: boolean
  isLoading: boolean
}

// Hooks:
- useAuth() → { user, login, logout, isAuthenticated, isLoading }
- usePermissions() → { can(), canAny(), canAll() }
```

**Auth Flow:**
1. GET `/sanctum/csrf-cookie` (setup)
2. POST `/login` (credentials)
3. Query cache invalidation
4. Redirect to `/dashboard`
5. Logout clears store + cache

**State Management** (`src/core/state/stores/authStore.ts`):

```typescript
interface AuthState {
  user: User | null
  permissions: string[]
  isAuthenticated: boolean
}

interface UIState {
  sidebarCollapsed: boolean
  sidebarWidth: number
  commandPaletteOpen: boolean
  theme: 'light' | 'dark' | 'system'
}

// Zustand with localStorage persistence
```

**Offline Support** (`src/core/offline/`, `src/infra/dexie/`):
- Dexie IndexedDB wrapper
- Sync layer for offline changes
- Real-time data synchronization

#### **3. Design System (`src/design-system/`)**

**Primitives** (`primitives/`):
- Button, Input, Select, Checkbox
- Dialog, Popover, Tooltip, Dropdown
- Card, Separator, Tabs
- Built with Radix UI + Tailwind CSS

**Composite** (`composite/`):
- DataTable: TanStack Table integration
- Card variants
- Complex UI patterns

**Layout** (`layout/AppLayout.tsx`):

```
┌─────────────────────────────────────────┐
│ Sidebar (w-16/w-64) │ Main Content     │
│ ┌─────────────────┐ │ ┌──────────────┐ │
│ │ Header+Logo     │ │ │ TopBar       │ │
│ │ Toggle Collapse │ │ │ Search, User │ │
│ ├─────────────────┤ │ ├──────────────┤ │
│ │ Module Menu     │ │ │ <Outlet />   │ │
│ │ • Dashboard     │ │ │ (Page)       │ │
│ │ • CRM           │ │ │              │ │
│ │ • Sales         │ │ │              │ │
│ │ • ...           │ │ └──────────────┘ │
│ ├─────────────────┤ │                  │
│ │ Context Menu    │ │                  │
│ ├─────────────────┤ │                  │
│ │ Footer/Settings │ │                  │
│ └─────────────────┘ │                  │
└─────────────────────────────────────────┘
```

#### **4. Modules (`src/modules/`)**

**Module Structure (Example: CRM)**

```
src/modules/crm/
├── CrmRouter.tsx             # Route definitions
├── pages/
│   ├── LeadsListPage.tsx      # List view
│   ├── OpportunitiesListPage.tsx
│   └── CustomersListPage.tsx
├── components/               # Feature-specific components
│   ├── LeadForm.tsx
│   ├── LeadKanbanBoard.tsx
│   └── ...
└── hooks/                    # Custom hooks
    ├── useLeads.ts
    └── useOpportunities.ts
```

**Module Pattern:**
- Page per entity (List, Detail, Create/Edit)
- Custom hooks for data fetching with TanStack Query
- Components scoped to module
- Router composed into main Router.tsx

**Active Modules:**
1. **accounting** - Invoices, Bills, Payments, Reconciliation
2. **core** - Users, Roles, Territories, Profile
3. **crm** - Leads, Opportunities, Customers
4. **delivery** - Fleet, Tours, Route Optimization
5. **hr** - Employees, Appraisals, Performance
6. **inventory** - Warehouses, Stock Management
7. **projects** - Project Planning, Tasks
8. **purchasing** - Purchase Orders, Suppliers
9. **reports** - Analytics, Dashboards
10. **sales** - Orders, Quotes, Pipeline
11. **settings** - System Configuration
12. Dashboard
13. eCommerce (orders)

#### **5. Features (`src/features/`)**

Cross-cutting concerns:
- **auth** - Login page
- **commandPalette** - Cmd+K search interface
- **company** - Company switcher
- **notifications** - Notification dropdown
- **user** - User menu/profile

#### **6. Components (`src/components/`)**

**Navigation** (`components/navigation/`):
- `ModuleMenu.tsx` - Vertical module selector
- `ContextMenu.tsx` - Page-specific options
- `TopBar.tsx` - Search, company, notifications, user menu

**Shared** (`components/shared/`):
- Reusable UI patterns
- Cross-module components

#### **7. Styling**

- **Tailwind CSS** v3.3 - Utility-first CSS
- **CSS Variables** - Design tokens
- **Dark Mode** - `.dark` class-based toggle
- **Theme System** - Light/Dark/System preference

### Build Configuration

**Vite Config** (`vite.config.ts`):
```typescript
{
  server: {
    port: 5173,
    proxy: {
      '/api': 'http://localhost:8000',
      '/sanctum': 'http://localhost:8000'
    }
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src')
    }
  }
}
```

**TypeScript Config** (`tsconfig.json`):
- Strict mode enabled
- ESNext target
- Path aliases configured
- React JSX runtime

### Development & Build Commands

```bash
npm run dev          # Vite dev server (http://localhost:5173)
npm run build        # tsc + Vite build → dist/
npm run preview      # Preview production build
npm run lint         # ESLint validation
npm run type-check   # TypeScript strict checking
```

---

## ⚠️ Current Issues & Technical Debt

### Frontend TypeScript Errors (28 issues in tsc_errors_final.txt)

#### Critical Issues

1. **Missing Module Imports**
   - `src/modules/crm/components/LeadKanbanBoard.tsx` - Missing `leadsApi` module
   - `src/modules/sales/index.ts` - Cannot find `./pages`, `./components`, `./hooks`
   - Impact: Module may not be properly exported

2. **Type Mismatches in CRM Module**
   - `useOpportunities.ts` - `OpportunitiesParams` not assignable to `Record<string, unknown>`
   - `OpportunityDetailPage.tsx` - Type `string | undefined` assigned to `string` (missing null checks)
   - Stage field expecting enum `"prospecting" | "qualification"...` but receiving `string`
   - Impact: Runtime errors possible with improper typing

3. **Unused Imports & Variables**
   - `OpportunityDetailPage.tsx` - Unused import at line 9
   - `useProducts.ts` - Unused variables: `err`, `data`, `error`
   - Impact: Code clutter, misleading analysis

4. **Missing UI Components**
   - `SFAPerformancePage.tsx` - Cannot find `Badge`, `Award` components (lucide-react icons not imported)
   - Impact: Page rendering fails

5. **Generic Type Errors**
   - `useOrders.ts` - `error: unknown` type not properly narrowed
   - `useVisits.ts` - Multiple `error: unknown` type assertions needed
   - Impact: Loss of type safety in error handling

6. **Table Row Typing**
   - `ToursListPage.tsx` - Row object missing `id` property (TanStack Table typing issue)
   - Impact: Cannot access row data

### Summary

| Severity | Count | Category |
|----------|-------|----------|
| 🔴 Critical | 8 | Module imports, type mismatches |
| 🟡 Warning | 12 | Unused code, type narrowing |
| 🟢 Info | 8 | Code quality |

### Root Causes

1. **Incomplete Module Refactoring** - Sales module exports not properly configured
2. **Loose Type Definitions** - API response types too permissive
3. **Missing Error Handling** - Errors typed as `unknown` not narrowed
4. **Component Library Integration** - Icon imports not properly handled

---

## 🚀 Architecture Highlights

### Backend Strengths

✅ **Event-Driven Architecture**
- Domain events as source of truth
- Audit trail via event store
- Sagas handle complex workflows

✅ **Modular Structure**
- Clear separation of concerns (Models, Services, Controllers)
- Easy to test and extend

✅ **Modern PHP Stack**
- Laravel 11 with latest features
- Type hints throughout
- Strong ORM (Eloquent)

✅ **Production-Ready Infrastructure**
- Redis for queues + caching
- OpenTelemetry for observability
- gRPC for high-performance communication

### Frontend Strengths

✅ **Modern React Stack**
- TypeScript for type safety
- Vite for blazing-fast dev/build
- Latest React 18 patterns (hooks, suspense)

✅ **Enterprise-Grade State Management**
- Zustand for UI state
- TanStack Query for server state
- Clear separation of concerns

✅ **Comprehensive Design System**
- Radix UI for accessible components
- Tailwind CSS for consistent styling
- Token-based theming

✅ **Modular Feature Architecture**
- 13+ independent business modules
- Easy to collaborate on (minimal conflicts)
- Router composition pattern

✅ **Advanced Patterns**
- Offline support (Dexie/IndexedDB)
- Real-time data sync
- Permission-based UI rendering

---

## 📊 Code Quality Assessment

### Backend (Laravel)

| Aspect | Status | Notes |
|--------|--------|-------|
| Architecture | ⭐⭐⭐⭐⭐ | Event sourcing, CQRS patterns |
| Testing | ⭐⭐⭐⭐ | PHPUnit setup, needs more coverage |
| Documentation | ⭐⭐⭐ | ARCHITECTURE.md for frontend, minimal backend docs |
| Type Safety | ⭐⭐⭐⭐ | PHP 8.2 with return types |
| Performance | ⭐⭐⭐⭐ | Redis caching, query optimization needed |

### Frontend (React)

| Aspect | Status | Notes |
|--------|--------|-------|
| Architecture | ⭐⭐⭐⭐⭐ | Modular, clean separation |
| Configuration | ⭐⭐⭐⭐ | Vite, TypeScript, path aliases |
| Type Safety | ⭐⭐⭐ | 28 TypeScript errors to resolve |
| Testing | ⭐⭐ | No test files found (Vitest/Jest setup missing) |
| Documentation | ⭐⭐⭐⭐ | Excellent ARCHITECTURE.md |
| Performance | ⭐⭐⭐⭐ | Code splitting, lazy loading ready |

---

## 🎯 Recommendations

### Immediate Priorities (Next Sprint)

1. **Fix TypeScript Compilation** (2-3 hours)
   - Resolve 28 TypeScript errors
   - Run `npm run build` validation
   - Add missing module exports
   - Add proper error type narrowing

2. **Complete Sales Module**
   - Verify `src/modules/sales/` exports (`pages`, `components`, `hooks`)
   - Add missing components.

3. **Add Test Infrastructure** (4 hours)
   - Setup Vitest for unit tests
   - Add testing-library for component tests
   - Target 60%+ coverage

### Medium-Term (2-4 Weeks)

4. **API Type Generation**
   - Consider OpenAPI/Swagger generation from Laravel
   - Auto-generate TypeScript types from backend

5. **Performance Optimization**
   - Profile Vite build size
   - Implement code splitting per module
   - Add performance monitoring (Sentry/LogRocket)

6. **Backend Documentation**
   - Document API endpoints
   - Create GraphQL schema documentation
   - Add architecture decision records (ADRs)

### Long-Term (Next Quarter)

7. **Testing Coverage**
   - Unit tests for hooks (useAuth, usePermissions, etc.)
   - Integration tests for key flows (login, CRUD)
   - E2E tests (Cypress/Playwright)

8. **Observability**
   - Integrate OpenTelemetry frontend (currently backend only)
   - Add error tracking (Sentry)
   - Real-time dashboards

9. **Accessibility**
   - a11y audit (Radix UI requires proper usage)
   - WCAG 2.1 AA compliance
   - Screen reader testing

10. **Mobile / Responsive Design**
    - Ensure all modules are mobile-friendly
    - Test on major breakpoints

---

## 📁 Key Files Reference

### Backend

| File | Purpose |
|------|---------|
| `erp/app/Aggregates/` | Domain aggregates for event sourcing |
| `erp/app/Services/Sagas/` | Long-running transaction handlers |
| `erp/app/Services/Projectors/` | Read model generators |
| `erp/app/Http/Controllers/` | API endpoints |
| `erp/routes/` | API route definitions |
| `erp/database/migrations/` | Database schema |
| `erp/config/` | Configuration files |

### Frontend

| File | Purpose |
|------|---------|
| `frontend/src/app/Router.tsx` | Main router configuration |
| `frontend/src/core/api/client.ts` | API client setup |
| `frontend/src/core/state/stores/authStore.ts` | Auth/UI state |
| `frontend/src/design-system/` | Shared UI components |
| `frontend/src/modules/*/` | Business domain modules |
| `frontend/vite.config.ts` | Vite build configuration |
| `frontend/ARCHITECTURE.md` | Detailed architecture guide |

---

## 🔐 Security Considerations

✅ **Implemented:**
- Laravel Sanctum authentication
- CSRF protection
- SQL injection prevention (Eloquent ORM)
- XSS protection (React escaping)
- Permission-based access control
- Secure session handling

⚠️ **To Review:**
- API rate limiting configuration
- JWT token expiration
- CORS policy validation
- Sensitive data in logs
- Dependency vulnerabilities (run `composer audit`, `npm audit`)

---

## 🎓 Development Guidelines

### Backend Development
```bash
# Setup
composer install
cp .env.example .env
php artisan migrate

# Development
composer dev  # Runs: serve + queue + pail + vite concurrently

# Testing
php artisan test
./vendor/bin/phpunit

# Code Quality
./vendor/bin/pint             # Format
php artisan code:analyze      # Static analysis
```

### Frontend Development
```bash
# Setup
npm install

# Development
npm run dev    # Vite dev server

# Build
npm run build  # TypeScript + Vite build

# Type Checking
npm run type-check

# Linting
npm run lint

# Testing (when setup)
npm test
```

---

## 📞 Contact & Support

For questions about:
- **Architecture**: Review `erp/docs/` and `frontend/ARCHITECTURE.md`
- **API Endpoints**: Check `erp/routes/` and API documentation
- **Frontend Structure**: See `frontend/src/` module organization
- **Type Definitions**: TypeScript types in `frontend/src/types/`

---

**Report Generated**: 2026-03-27
**Analyzed Version**: Latest commit on main branch
