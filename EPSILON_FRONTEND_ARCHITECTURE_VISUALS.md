# EPSILON Frontend Advanced Architecture - Visual Guide

## 🏗️ System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          EPSILON FRONTEND                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌───────────────────────────────────────────────────────────────────┐ │
│  │                    PRESENTATION LAYER                             │ │
│  ├───────────────────────────────────────────────────────────────────┤ │
│  │                                                                   │ │
│  │  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────────┐ │ │
│  │  │  KPI Dashboards │  │  Data Tables    │  │  Territory Map   │ │ │
│  │  │  (Real-time)    │  │  (Configurable) │  │  (Geofencing)    │ │ │
│  │  └─────────────────┘  └─────────────────┘  └──────────────────┘ │ │
│  │                                                                   │ │
│  │  ┌─────────────────────────────────────────────────────────────┐ │ │
│  │  │        Dynamic Sidebar Context                             │ │ │
│  │  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │ │ │
│  │  │  │ Zone Select  │  │ Region Multi │  │ Depot Switch │      │ │ │
│  │  │  └──────────────┘  └──────────────┘  └──────────────┘      │ │ │
│  │  └─────────────────────────────────────────────────────────────┘ │ │
│  │                                                                   │ │
│  └───────────────────────────────────────────────────────────────────┘ │
│                                  ▲                                    │
│                                  │                                    │
│  ┌───────────────────────────────────────────────────────────────────┐ │
│  │              CONFIGURATION & PERMISSION LAYER                     │ │
│  ├───────────────────────────────────────────────────────────────────┤ │
│  │                                                                   │ │
│  │  Admin Panel                  ┌──────────────────────────────┐   │ │
│  │  ┌──────────────────────┐     │  Dynamic Route Generator    │   │ │
│  │  │ Module Manager       │     │  - Filter by permissions    │   │ │
│  │  │ User & Permissions   │     │  - Filter by geography      │   │ │
│  │  │ Role Configuration   │     │  - Generate nav dynamically │   │ │
│  │  │ Zone/Region/Depot    │     └──────────────────────────────┘   │ │
│  │  │ Field Visibility     │                                        │ │
│  │  └──────────────────────┘     Permission Engine                 │ │
│  │                               ┌──────────────────────────────┐   │ │
│  │                               │ - Check resource access      │   │ │
│  │                               │ - Check scope (zone/region)  │   │ │
│  │                               │ - Check conditions           │   │ │
│  │                               │ - Row-level security         │   │ │
│  │                               └──────────────────────────────┘   │ │
│  │                                                                   │ │
│  └───────────────────────────────────────────────────────────────────┘ │
│                                  ▲                                    │
│                                  │                                    │
│  ┌───────────────────────────────────────────────────────────────────┐ │
│  │                    STATE MANAGEMENT LAYER                         │ │
│  ├───────────────────────────────────────────────────────────────────┤ │
│  │                                                                   │ │
│  │  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────┐   │ │
│  │  │ useGeography     │  │ useSystemConfig  │  │ useUserContext   │ │
│  │  │  (Zustand)       │  │  (Zustand)       │  │  (Zustand)   │   │ │
│  │  │                  │  │                  │  │              │   │ │
│  │  │ - Zones          │  │ - Modules        │  │ - User info  │   │ │
│  │  │ - Regions        │  │ - Permissions    │  │ - Context    │   │ │
│  │  │ - Depots         │  │ - Roles          │  │ - Permissions│   │ │
│  │  │ - Hierarchical   │  │ - Feature flags  │  │ - Accessible │   │ │
│  │  │   queries        │  │ - Clients        │  │   data       │   │ │
│  │  └──────────────────┘  └──────────────────┘  └──────────────┘   │ │
│  │                                                                   │ │
│  │  useDashboardStore (Zustand)                                     │ │
│  │  ┌──────────────────────────────────────────────────────────┐   │ │
│  │  │ - Widget definitions                                    │   │ │
│  │  │ - User layout preferences                              │   │ │
│  │  │ - Role default configurations                          │   │ │
│  │  └──────────────────────────────────────────────────────────┘   │ │
│  │                                                                   │ │
│  └───────────────────────────────────────────────────────────────────┘ │
│                                  ▲                                    │
│                                  │                                    │
│  ┌───────────────────────────────────────────────────────────────────┐ │
│  │                      API & SYNC LAYER                             │ │
│  ├───────────────────────────────────────────────────────────────────┤ │
│  │                                                                   │ │
│  │  Configuration API      TanStack Query      Real-time Sync      │ │
│  │  ┌──────────────────┐   ┌──────────────┐   ┌──────────────────┐ │ │
│  │  │ GET zones        │   │ useQuery()   │   │ Server-Sent      │ │ │
│  │  │ GET regions      │   │ useMutation()│   │ Events (SSE)     │ │ │
│  │  │ GET depots       │───│             │───│ Dexie IndexedDB  │ │ │
│  │  │ GET permissions  │   │ Auto-cache  │   │ Offline support  │ │ │
│  │  │ GET modules      │   │ Auto-retry  │   │                  │ │ │
│  │  └──────────────────┘   └──────────────┘   └──────────────────┘ │ │
│  │                                                                   │ │
│  └───────────────────────────────────────────────────────────────────┘ │
│                                  ▲                                    │
│                                  │ HTTP/SSE                          │
└──────────────────────────────────┼───────────────────────────────────┘
                                   │
                    ┌──────────────┴──────────────┐
                    │                             │
              ┌─────▼─────┐             ┌─────────▼───────┐
              │   Laravel  │             │    Redis        │
              │   Backend  │             │ Real-time Sync  │
              │            │             │                 │
              │ - DB       │             │ - Config cache  │
              │ - API      │             │ - User presence │
              │ - Auth     │             │ - Pub/Sub       │
              └────────────┘             └─────────────────┘
```

---

## 🔐 Permission System Hierarchy

```
PERMISSIONS
    │
    ├── Resource Level
    │   ├── crm.*
    │   ├── sales.*
    │   ├── inventory.*
    │   ├── accounting.*
    │   └── system.*
    │
    ├── Action Level
    │   ├── view
    │   ├── create
    │   ├── update
    │   ├── delete
    │   ├── export
    │   └── approve
    │
    └── Scope Level
        ├── own (only own records)
        ├── zone (zone-level access)
        ├── region (region-level access)
        ├── depot (depot-level access)
        ├── client (client-level access)
        └── all (global access)


ROLE COMPOSITION
    │
    ├── Role: "Sales Manager"
    │   ├── Permissions
    │   │   ├── crm.view (scope: zone)
    │   │   ├── crm.create (scope: zone)
    │   │   ├── orders.view (scope: zone)
    │   │   ├── orders.create (scope: zone)
    │   │   └── reports.view (scope: zone)
    │   ├── Modules
    │   │   ├── CRM ✓
    │   │   ├── Sales ✓
    │   │   ├── Inventory ✓ (read-only)
    │   │   └── Settings ✗
    │   └── Features
    │       ├── advanced_filters: true
    │       ├── bulk_actions: true
    │       ├── export: true
    │       └── offline_sync: true
    │
    ├── Role: "Warehouse Manager"
    │   ├── Permissions
    │   │   ├── inventory.view (scope: depot)
    │   │   ├── inventory.update (scope: depot)
    │   │   ├── orders.view (scope: depot)
    │   │   ├── shipments.view (scope: depot)
    │   │   └── reports.view (scope: depot)
    │   ├── Modules
    │   │   ├── Inventory ✓
    │   │   ├── Orders ✓
    │   │   ├── CRM ✗
    │   │   └── Settings ✗
    │   └── Features
    │       ├── barcode_scanning: true
    │       ├── mobile_app: true
    │       └── bulk_actions: true
    │
    └── Role: "System Admin"
        ├── Permissions: ALL (scope: all)
        ├── Modules: ALL
        └── Features: ALL


USER ASSIGNMENT HIERARCHY
    │
    ├── User: John Smith (Sales Manager)
    │   ├── Role: Regional Sales Manager
    │   ├── Primary Zone: West Coast
    │   ├── Regions: CA, OR, WA, NV
    │   ├── Assigned Clients: Ford, GM, Tesla
    │   ├── Data Visibility:
    │   │   ├── Can see: All transactions in assigned regions
    │   │   ├── Can edit: Transactions assigned to him
    │   │   └── Cannot see: Other regions, financial data
    │   └── Dashboard:
    │       ├── Regional Revenue Chart
    │       ├── Territory Pipeline
    │       ├── My Opportunities
    │       └── Team Performance
    │
    └── User: Maria Garcia (Warehouse Manager)
        ├── Role: Depot Operations Manager
        ├── Depot: LA Distribution Center
        ├── Access Regions: CA
        ├── Assigned Clients: All (serving all)
        ├── Data Visibility:
        │   ├── Can see: All inventory in LA depot
        │   ├── Can edit: Stock levels, transfers
        │   └── Cannot see: Financial data, other depots
        └── Dashboard:
            ├── Real-time Stock Levels
            ├── Inbound Shipments
            ├── Outbound Orders
            └── Capacity Utilization
```

---

## 📊 Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                         USER INTERACTION                             │
│                                                                      │
│  1. Admin configures zones/regions/depots in UI                    │
│  2. Admin assigns users + roles + permissions                      │
│  3. Changes pushed to backend → stored in DB                       │
│  4. Changes broadcast via SSE → all connected users                │
│  5. Frontend stores sync with backend                              │
│  6. UI automatically adapts to new config                          │
│                                                                      │
└──────────────────────────────┬──────────────────────────────────────┘
                               │
                    ┌──────────▼──────────┐
                    │   Configuration     │
                    │   Changes Detected  │
                    └──────────┬──────────┘
                               │
                ┌──────────────┼──────────────┐
                │              │              │
        ┌───────▼────────┐     │     ┌───────▼────────┐
        │ Geography      │     │     │ Permission     │
        │ Store Updated  │     │     │ Store Updated  │
        │ (useGeo...)    │     │     │ (useSystem...) │
        └────────┬───────┘     │     └────────┬───────┘
                 │             │             │
                 └─────────────┬─────────────┘
                               │
                    ┌──────────▼──────────┐
                    │  Router Regenerated │
                    │  (Routes filtered)  │
                    └──────────┬──────────┘
                               │
                    ┌──────────▼──────────┐
                    │  Sidebar Regenerated│
                    │  (Nav adapted)      │
                    └──────────┬──────────┘
                               │
                    ┌──────────▼──────────┐
                    │  UI Components      │
                    │  Re-render          │
                    │  with new config    │
                    └─────────────────────┘


EXAMPLE: Adding a new Zone
───────────────────────────

┌─────────────────────────────────────────────────────────────────┐
│  Frontend                    Backend                Redis       │
├──────────────────────┬─────────────────────┬─────────────────┤
│                      │                     │                 │
│ [Admin adds:]        │                     │                 │
│ "South America Zone" │                     │                 │
│         │            │                     │                 │
│         ├─POST /api/zones/create ─────────>│                 │
│         │            │   { name, ... }    │                 │
│         │            │                     │                 │
│         │            ├─ Validate          │                 │
│         │            │ Validate           │                 │
│         │            ├─ Save to DB        │                 │
│         │            │                     │                 │
│         │            ├─ Publish SSE ─────────────> pub/channels
│         │            │   "zone:created"   │   subscription   │
│         │            │                     │                 │
│         │<─ SSE broadcast ──────────────────<─ receive event
│         │   "zone:created"                │                 │
│         │   { id: "sa", name: "..." }    │                 │
│         │                                 │                 │
│         ├─ useGeo.setState()             │                 │
│         │   (add new zone)                │                 │
│         │                                 │                 │
│         ├─ Router regenerated            │                 │
│         │   (new route: /zone/sa/...) ─┐ │                 │
│         │                               │ │                 │
│         ├─ Sidebar regenerated          │ │                 │
│         │   (shows new zone) ────────┐  │ │                 │
│         │                            │  │ │                 │
│         └─ UI re-renders             │  │ │                 │
│            with updated state        │  │ │                 │
│                                      │  │ │                 │
│            [User sees new zone]  <───┘  │ │                 │
│                                         │ │                 │
│            [User can navigate] ◄────────┘ │                 │
│            to /zone/sa/dashboard         │                 │
│                                          │                 │
└──────────────────────────────────────────┴─────────────────┘
```

---

## 🎨 UI Layout Transformation

### BEFORE: Static Navigation
```
┌──────────────────────────────────────┐
│ EPSILON ERP                    🔍 👤 │
├──────────────────────────────────────┤
│                                      │
│ Sidebar                   Dashboard  │
│ ┌─────────────┐  ┌──────────────┐   │
│ │ Dashboard   │  │ Welcome Page │   │
│ │             │  │              │   │
│ │ CRM         │  │ Some static  │   │
│ │  • Leads    │  │ content      │   │
│ │  • Opps     │  │              │   │
│ │  • Custs    │  │ No context   │   │
│ │             │  │ awareness    │   │
│ │ Sales       │  │              │   │
│ │  • Orders   │  │              │   │
│ │  • Quotes   │  │              │   │
│ │             │  │              │   │
│ │ Inventory   │  └──────────────┘   │
│ │  • Stock    │                     │
│ │  • Transfers                      │
│ │             │                     │
│ └─────────────┘                     │
│                                      │
└──────────────────────────────────────┘

Problems:
❌ Same for everyone
❌ Hardcoded routes
❌ No geographic context
❌ No permission filtering
❌ Static dashboards
```

### AFTER: Dynamic, Context-Aware UI
```
┌──────────────────────────────────────────────────────┐
│ EPSILON ERP                            🔍 👤 🌙 ⚙️  │
├──────────────────────────────────────────────────────┤
│                                                      │
│ Sidebar              Dashboard                      │
│ ┌─────────────────┐  ┌────────────────────────────┐│
│ │ Your Scope      │  │ REVENUE METRICS            ││
│ │ ─────────────   │  │ ┌──────────┐  ┌──────────┐││
│ │ Zone:           │  │ │ $2.5M    │  │ +15%     │││
│ │ [West Coast ▼]  │  │ │ Revenue  │  │ vs Last  │││
│ │                 │  │ │          │  │ Month    │││
│ │ Region:         │  │ └──────────┘  └──────────┘││
│ │ [CA] [OR] [WA]  │  │ ┌──────────┐  ┌──────────┐││
│ │  ✓CA  ✓OR ✓WA   │  │ │ 324      │  │ 8%       │││
│ │                 │  │ │ Pipeline │  │ Conv %   │││
│ │ Depot:          │  │ │ Items    │  │          │││
│ │ [LA DC ▼]       │  │ └──────────┘  └──────────┘││
│ │                 │  │                            ││
│ │ Modules         │  │ TERRITORY CHART            ││
│ │ ─────────────   │  │ ┌─────────────────────┐   ││
│ │ ✓ CRM           │  │ │ Revenue by Region   │   ││
│ │  • Leads        │  │ │ CA: $1.2M           │   ││
│ │  • Opps         │  │ │ OR: $800K           │   ││
│ │  • Custs        │  │ │ WA: $500K           │   ││
│ │                 │  │ │ [Chart rendering]   │   ││
│ │ ✓ Sales         │  │ └─────────────────────┘   ││
│ │  • Orders       │  │                            ││
│ │  • Quotes       │  │ MY DASHBOARD               ││
│ │  • Territory    │  │ [Customizable widgets]     ││
│ │                 │  │ [Drag to reorder]          ││
│ │ ✓ Inventory     │  │ + [Add Widget]             ││
│ │ ✓ Reports       │  │                            ││
│ │                 │  └────────────────────────────┘│
│ │ Quick Actions   │                               │
│ │ [Create Order]  │                               │
│ │ [New Lead]      │                               │
│ │ [Stock Check]   │                               │
│ │                 │                               │
│ └─────────────────┘                               │
│                                                      │
└──────────────────────────────────────────────────────┘

Benefits:
✅ Different for each user
✅ Dynamically generated routes
✅ Zone/Region/Depot context aware
✅ Permission-filtered navigation
✅ Customizable dashboards with KPIs
✅ Real-time data
✅ Beautiful charts & metrics
✅ Responsive design
```

---

## 🔄 Configuration UI Flows

### Flow 1: Create New Zone
```
┌─────────────────┐
│  System Admin   │
│   Dashboard     │
└────────┬────────┘
         │
         ├─> Click [Settings]
         │
         ├─> Geographic Hierarchy
         │
         ├─> [+ New Zone]
         │
         │   ┌──────────────────────────┐
         │   │ Create Zone              │
         │   ├──────────────────────────┤
         │   │ Name: _____________      │
         │   │ Country: [Select ▼]      │
         │   │ Timezone: [Select ▼]     │
         │   │ Currency: [Select ▼]     │
         │   │ Description: ________    │
         │   │                          │
         │   │ [Cancel] [Create]        │
         │   └──────────────────────────┘
         │
         ├─> Backend: POST /zones
         │
         ├─> Redis broadcast
         │
         ├─> All clients notified
         │
         └─> New zone appears in:
             • Zone selector
             • Reports
             • User assignments
```

### Flow 2: Set User Permissions
```
┌─────────────────┐
│  System Admin   │
│   Dashboard     │
└────────┬────────┘
         │
         ├─> Click [Users]
         │
         ├─> Find user "John Smith"
         │
         ├─> Click [Edit]
         │
         │   ┌─────────────────────────────────────┐
         │   │ Edit User: john.smith@company.com   │
         │   ├─────────────────────────────────────┤
         │   │ Basic Info                          │
         │   │ Name: John Smith                    │
         │   │ Email: john.smith@company.com       │
         │   │ Role: [Regional Sales Manager ▼]   │
         │   │                                     │
         │   │ Geographic Assignment               │
         │   │ ☐ Global                           │
         │   │ ☑ Zone-based                       │
         │   │   Primary: [West Coast ▼]          │
         │   │   Secondary: [Select ▼]            │
         │   │ Regions: ☑CA ☑OR ☑WA ☐NV         │
         │   │                                     │
         │   │ Clients Assignment                  │
         │   │ ☑ Ford Motors                       │
         │   │ ☑ Tesla Motors                      │
         │   │ ☑ GM Holdings                       │
         │   │ ☐ Toyota Motors                     │
         │   │                                     │
         │   │ Explicit Permissions                │
         │   │ ☑ crm.view                          │
         │   │ ☑ crm.create                        │
         │   │ ☑ crm.edit (zone scope)             │
         │   │ ☑ orders.view (zone scope)          │
         │   │ ☑ orders.create                     │
         │   │ ☐ inventory.edit                    │
         │   │ ☐ system.admin                      │
         │   │                                     │
         │   │ [Cancel] [Save]                     │
         │   └─────────────────────────────────────┘
         │
         ├─> Backend: PUT /users/john
         │
         ├─> User's stores updated
         │
         └─> John sees:
             • Only West Coast data
             • Only CRM + Sales modules
             • Only assigned clients
```

---

## 📱 Responsive Design Adaptation

```
DESKTOP (1920px)
┌──────────────────────────────────────────────────────┐
│ Sidebar │ Header                                  ◀▶ │
├─────────┼──────────────────────────────────────────┤
│         │ ┌─────────────────────────────────────┐  │
│ Modules │ │ Dashboard                           │  │
│         │ │ ┌────────┐  ┌────────┐  ┌────────┐ │  │
│ ├ CRM   │ │ │  KPI 1 │  │  KPI 2 │  │  KPI 3 │ │  │
│ ├ Sales │ │ └────────┘  └────────┘  └────────┘ │  │
│ ├ Inv   │ │                                     │  │
│ └ Rpts  │ │ ┌────────────────────────────────┐ │  │
│         │ │ │         Chart                  │ │  │
│ Zone    │ │ │       ┌─────────┐              │ │  │
│ [Zone▼] │ │ │     │░░░░░░░░░░│              │ │  │
│         │ │ │     │░ $2.5M   │              │ │  │
│ Region  │ │ │     │░░░░░░░░░░│              │ │  │
│ [Reg▼]  │ │ │     └─────────┘              │ │  │
│         │ │ └────────────────────────────────┘ │  │
│ Depot   │ │                                     │  │
│ [Depot▼]│ └─────────────────────────────────────┘  │
│         │                                           │
│         │ ┌─────────────── Table ──────────────┐   │
│         │ │ Orders                             │   │
│         │ │ ┌── ──── ────── ───── ────────────┐│   │
│         │ │ │ID│Zone│Region│Depot│Status     ││   │
│         │ │ │──┼────┼──────┼──────┼─────────→ ││   │
│         │ │ │  │    │      │      │           ││   │
│         │ │ └── ──── ────── ───── ────────────┘│   │
│         │ └────────────────────────────────────┘   │
│         │                                           │
└─────────┴───────────────────────────────────────────┘

TABLET (768px)
┌───────────────────────────────────────┐
│ [☰] Sidebar  Dashboard           [◀▶] │
├───────────────────────────────────────┤
│ Zone [West Coast ▼]                   │
│ Region [CA▼] [OR▼]                    │
│ Depot [LA DC ▼]                       │
├───────────────────────────────────────┤
│ ┌───────────────────────────────────┐ │
│ │ KPI 1        KPI 2        KPI 3   │ │
│ │ $2.5M        324          8%      │ │
│ │ Revenue      Pipeline     Conv    │ │
│ └───────────────────────────────────┘ │
│                                       │
│ ┌───────────────────────────────────┐ │
│ │ Revenue Chart                     │ │
│ │         [Chart rendering]         │ │
│ └───────────────────────────────────┘ │
│                                       │
│ ┌───────────────────────────────────┐ │
│ │ Orders Table                      │ │
│ │ Order│Zone │ Status │ Total      │ │
│ │ O123 │West │Pending │ $5,000    │ │
│ │ O124 │West │Shipped │ $3,200    │ │
│ └───────────────────────────────────┘ │
│                                       │
└───────────────────────────────────────┘

MOBILE (375px)
┌──────────────────────┐
│[☰] EPSILON  [👤][🌙]│
├──────────────────────┤
│Zone: [West Coast  ▼] │
│Region: [CA]          │
│Depot: [LA DC ▼]      │
├──────────────────────┤
│Revenue                │
│$2.5M    +15%         │
├──────────────────────┤
│Pipeline               │
│324 items  8% conv    │
├──────────────────────┤
│[+] Add Widget         │
├──────────────────────┤
│Orders (23)            │
│                       │
│O123 - Pending - $5K  │
│[View Details] →      │
│                       │
│O124 - Shipped - $3K  │
│[View Details] →      │
│                       │
│[Load More...]         │
└──────────────────────┘
```

---

## ✅ Checklist for Implementation

### Phase 1: Foundation
- [ ] Design database schema (zones, regions, depots, users)
- [ ] Create Zustand stores for configuration
- [ ] Build backend APIs for configuration
- [ ] Implement permission system logic
- [ ] Create store tests

### Phase 2: UI
- [ ] Copy components from ProtoFrontEnd
- [ ] Create KPI Dashboard
- [ ] Create DataTable
- [ ] Create Dynamic Sidebar
- [ ] Create Territory Map

### Phase 3: Configuration
- [ ] Build Module Manager
- [ ] Build User Manager
- [ ] Build Permission Manager
- [ ] Build Zone/Region/Depot Manager
- [ ] Build Role Manager

### Phase 4: Integration
- [ ] Connect to backend APIs
- [ ] Implement real-time sync
- [ ] Add audit logging
- [ ] Performance optimization
- [ ] Security review

---

**Visual Guide Complete** ✓
All diagrams ready for presentation to stakeholders.
