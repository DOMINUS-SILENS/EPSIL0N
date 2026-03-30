// Static, versioned permission registry.
// Permissions are assigned to Roles in the backend, but the definition of what permissions exist
// lives here statically to ensure deterministic UI and reliable offline capabilities.

export const PERMISSIONS = {
  core: [
    'users.view', 'users.create', 'users.update', 'users.delete',
    'roles.view', 'roles.manage',
    'territories.view', 'territories.manage',
    'segments.view', 'segments.manage'
  ],
  crm: [
    'leads.view', 'leads.create', 'leads.update', 'leads.convert',
    'customers.view', 'customers.manage',
    'opportunities.view', 'opportunities.manage',
    'quotes.view', 'quotes.create', 'quotes.approve'
  ],
  erp: [
    'orders.view', 'orders.create', 'orders.confirm',
    'products.view', 'products.manage',
    'stock.view', 'stock.manage', 'warehouses.manage'
  ],
  sfa: [
    'visits.view', 'visits.create', 'visits.complete',
    'routes.view', 'routes.manage'
  ],
  delivery: [
    'tours.view', 'tours.start', 'tours.complete',
    'parcels.scan', 'parcels.deliver'
  ],
  commercial: [
    'prices.view', 'prices.manage',
    'promotions.view', 'promotions.manage'
  ],
  accounting: [
    'accounts.view', 'accounts.manage',
    'journal.view', 'journal.create',
    'payments.view', 'payments.process'
  ],
  reports: [
    'reports.view', 'reports.export'
  ]
} as const;

export type PermissionKey = typeof PERMISSIONS[keyof typeof PERMISSIONS][number]

export const ALL_PERMISSIONS = Object.values(PERMISSIONS).flat() as PermissionKey[];
