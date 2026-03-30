# Frontend Architecture Overview

This document outlines the architectural principles, technology stack, and design patterns used in the EPSILON ERP frontend.

## 🚀 Core Technology Stack

| Layer | Technology | Purpose |
| :--- | :--- | :--- |
| **Framework** | **React 18** | UI Library for building interactive user interfaces. |
| **Build Tool** | **Vite** | Fast development server and build tool using ESM. |
| **Language** | **TypeScript** | Static typing for improved developer experience and code reliability. |
| **Routing** | **TanStack Router** | Type-safe, file-based routing with support for nested layouts. |
| **Data Fetching** | **TanStack Query** | Server state management, caching, and synchronization. |
| **State Mgt** | **Zustand** | Lightweight state management for persistent local context (e.g., active company). |
| **HTTP Client** | **Axios** | Promised-based HTTP client for API communication. |
| **Styling** | **Tailwind CSS** | Utility-first CSS framework for rapid and consistent design. |
| **UI Primitives** | **Radix UI** | Unstyled, accessible UI components for complex interactions. |
| **Persistence** | **Dexie.js** | IndexedDB wrapper for offline storage and command queuing. |

---

## 🏗️ Directory Structure & Modularity

The project follows a **Module-First Architecture**, where features are encapsulated into self-contained domains.

```text
src/
├── app/                # Main app configuration (Router, Providers)
├── core/               # Shared global services
│   ├── api/            # Axios client with interceptors
│   ├── auth/           # Authentication and Permission logic
│   ├── offline/        # Dexie DB and Sync Manager
│   ├── realtime/       # SSE listener service
│   └── state/          # Global Zustand stores (Company, UI)
├── design-system/      # Atomic UI components and theme tokens
├── modules/            # Domain-driven features (Accounting, CRM, Fleet, etc.)
│   └── [module]/
│       ├── api/        # Domain-specific services
│       ├── hooks/      # Custom Query/Mutation hooks
│       └── pages/      # Route-mapped components
└── lib/                # Shared utilities (cn helper, date formatters)
```

---

## 🔄 Key Architectural Patterns

### 1. Robust API Communication (`src/core/api`)
The Axios client is configured with interceptors to handle:
-   **CSRF Protection**: Automatically fetches sanctum cookies for stateful requests.
-   **Idempotency**: Generates and attaches `Idempotency-Key` headers to all mutating requests (POST/PUT/PATCH/DELETE), ensuring safe retries.
-   **Error Handling**: Centralized handling for 401 (Session Expired), 403 (Forbidden), and 409 (Conflict) errors with user-friendly toast notifications.

### 2. Offline-First & Command Bus (`src/core/offline`)
Instead of direct API calls, mutating actions often go through a **Command Bus pattern**:
1.  **Queueing**: Mutatations are added to `Dexie.js` as `PendingCommand` with a 'pending' status.
2.  **Optimistic UI**: The UI updates immediately using cached data.
3.  **Synchronization**: `syncManager` attempts to push pending commands to the server when a connection is detected or manually triggered.
4.  **Local Projection**: Projections (read models) are cached in `localStorage` to allow read-access during offline periods.

### 3. Multi-Tenant Context (`src/core/state`)
The **Multi-Company context** is managed via Zustand:
-   Users can switch operating companies via the `CompanySwitcher`.
-   Switching companies triggers a `company-changed` event, which invalidates all active queries in `TanStack Query` to ensure data isolation.

### 4. Event-Driven Real-time (`src/core/realtime`)
-   **SSE Stream**: A persistent event source listens for server-side domain events.
-   **Automatic Invalidation**: When an event like `aggregate_updated` is received, the frontend automatically identifies which query keys to invalidate, ensuring the UI stays in sync without manual refreshes.

---

## 🛠️ Best Practices & Guidelines

1.  **Component Nesting**: Follow Atomic Design. Don't put business logic in `design-system/primitives`. Keep `modules/*/pages` as the main orchestrators.
2.  **Naming Convention**: Use `use[Action]` for mutations (e.g., `useCreateInvoice`) and `use[Resource]` for queries (e.g., `useInvoices`).
3.  **Type Safety**: Always define DTOs in `src/modules/*/api` and use them as generic types in TanStack Query hooks.
4.  **Error Handling**: Use the provided `toast` notifications rather than generic `alert()`.
