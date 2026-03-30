import {
  LayoutDashboard,
  ShoppingCart,
  Package,
  FileText,
  Users,
  UserCircle,
  Warehouse,
  ArrowLeftRight,
  Tag,
  Percent,
  Calculator,
  MapPin,
  Route,
  MapPinned,
  BookOpen,
  CreditCard,
  Link as LinkIcon,
  BarChart3,
  TrendingUp,
  Settings,
  Truck,
  Briefcase,
  Store,
  Banknote,
  Activity,
  ClipboardList,
  BoxSelect
} from 'lucide-react'
import { ElementType } from 'react'

export type NavRoute = {
  label: string
  path: string
  icon: ElementType
  permission?: string
}

export type AppModule = {
  key: string
  label: string
  icon: ElementType
  permission?: string
  routes: NavRoute[]
}

export const modulesRegistry: AppModule[] = [
  {
    key: 'dashboard',
    label: 'Dashboard',
    icon: LayoutDashboard,
    routes: [
      { label: 'Overview', path: '/', icon: LayoutDashboard },
    ],
  },
  {
    key: 'core',
    label: 'Core',
    icon: Settings,
    permission: 'core.view',
    routes: [
      { label: 'Users', path: '/core/users', icon: Users, permission: 'users.view' },
      { label: 'Roles', path: '/core/roles', icon: Settings, permission: 'roles.view' },
      { label: 'Territories', path: '/core/territories', icon: MapPinned, permission: 'territories.view' },
      { label: 'Segments', path: '/core/segments', icon: Tag, permission: 'segments.view' },
      { label: 'Company Settings', path: '/core/settings/company', icon: Briefcase, permission: 'core.settings.view' },
      { label: 'Audit Logs', path: '/core/audit-logs', icon: Activity, permission: 'core.audit.view' },
    ],
  },
  {
    key: 'crm',
    label: 'CRM & Sales',
    icon: UserCircle,
    permission: 'crm.view',
    routes: [
      { label: 'Leads', path: '/crm/leads', icon: UserCircle, permission: 'leads.view' },
      { label: 'Opportunities', path: '/crm/opportunities', icon: TrendingUp, permission: 'opportunities.view' },
      { label: 'Customers', path: '/crm/customers', icon: Users, permission: 'customers.view' },
      { label: 'Quotes', path: '/crm/quotes', icon: FileText, permission: 'quotes.view' },
      { label: 'Campaigns', path: '/crm/campaigns', icon: Tag, permission: 'crm.campaigns.view' },
      { label: 'Activities', path: '/crm/activities', icon: Activity, permission: 'crm.activities.view' },
      { label: 'Orders', path: '/sales/orders', icon: ShoppingCart, permission: 'orders.view' },
      { label: 'Demos', path: '/sales/demos', icon: BoxSelect, permission: 'sales.demos.view' },
      { label: 'Sample Orders', path: '/sales/samples', icon: Package, permission: 'sales.samples.view' },
    ],
  },
  {
    key: 'inventory',
    label: 'Inventory',
    icon: Package,
    permission: 'inventory.view',
    routes: [
      { label: 'Products', path: '/inventory/products', icon: Package, permission: 'products.view' },
      { label: 'Warehouses', path: '/inventory/warehouses', icon: Warehouse, permission: 'warehouses.view' },
      { label: 'Stock Movements', path: '/inventory/movements', icon: ArrowLeftRight, permission: 'stock.view' },
      { label: 'Stock Adjustments', path: '/inventory/adjustments', icon: ClipboardList, permission: 'stock.manage' },
      { label: 'Suppliers', path: '/purchasing/suppliers', icon: LinkIcon, permission: 'purchasing.view' },
      { label: 'Purchase Orders', path: '/purchasing/orders', icon: ShoppingCart, permission: 'purchasing.orders.view' },
      { label: 'Purchase Receipts', path: '/purchasing/receipts', icon: ClipboardList, permission: 'stock.view' },
      { label: 'BOM', path: '/inventory/manufacturing/bom', icon: Settings, permission: 'manufacturing.view' },
      { label: 'Mfg Orders', path: '/inventory/manufacturing/orders', icon: Package, permission: 'manufacturing.orders.view' },
    ],
  },


  {
    key: 'commercial',
    label: 'Trade Marketing',
    icon: Percent,
    permission: 'commercial.view',
    routes: [
      { label: 'Price Lists', path: '/commercial/price-lists', icon: FileText, permission: 'prices.view' },
      { label: 'Promotions', path: '/commercial/promotions', icon: Percent, permission: 'promotions.view' },
      { label: 'Coupons', path: '/commercial/coupons', icon: Tag, permission: 'promotions.manage' },
      { label: 'Planograms', path: '/trademkt/planograms', icon: Store, permission: 'trade.view' },
      { label: 'POS Materials', path: '/trademkt/pos-materials', icon: Package, permission: 'trade.manage' },
      { label: 'Audits', path: '/trademkt/audits', icon: ClipboardList, permission: 'trade.audit.view' },
    ],
  },
  {
    key: 'presales',
    label: 'Pre-Sales',
    icon: Briefcase,
    routes: [
      { label: 'Campaigns', path: '/presales', icon: Tag },
    ],
  },
  {
    key: 'fleet',
    label: 'Fleet Management',
    icon: Truck,
    routes: [
      { label: 'Vehicles', path: '/fleet', icon: Truck },
    ],
  },
  {
    key: 'sfa',
    label: 'SFA',
    icon: MapPin,
    permission: 'sfa.view',
    routes: [
      { label: 'Dashboard', path: '/sfa', icon: LayoutDashboard, permission: 'sfa.dashboard.view' },
      { label: 'Visits', path: '/sfa/visits', icon: MapPin, permission: 'visits.view' },
      { label: 'Route Planner', path: '/sfa/planner', icon: Route, permission: 'routes.manage' },
      { label: 'Checklists', path: '/sfa/checklists', icon: ClipboardList, permission: 'visits.manage' },
    ],
  },
  {
    key: 'delivery',
    label: 'Delivery & Logistics',
    icon: Truck,
    permission: 'delivery.view',
    routes: [
      { label: 'Tours', path: '/delivery/tours', icon: Truck, permission: 'delivery.view' },
      { label: 'Fleet', path: '/delivery/fleet', icon: Truck, permission: 'delivery.fleet.manage' },
      { label: 'Routes', path: '/delivery/routes', icon: Route, permission: 'delivery.view' },
      { label: 'Drivers', path: '/delivery/drivers', icon: Users, permission: 'delivery.view' },
      { label: 'Tracking', path: '/delivery/tracking', icon: MapPin, permission: 'delivery.view' },
    ],
  },

  {
    key: 'hr',
    label: 'Human Resources',
    icon: Users,
    permission: 'hr.view',
    routes: [
      { label: 'Employees', path: '/hr/employees', icon: Users, permission: 'hr.employees.view' },
      { label: 'Contracts', path: '/hr/contracts', icon: FileText, permission: 'hr.contracts.view' },
      { label: 'Attendance', path: '/hr/attendance', icon: Activity, permission: 'hr.attendance.view' },
      { label: 'Leave Requests', path: '/hr/leave-requests', icon: FileText, permission: 'hr.leave.view' },
      { label: 'Payroll', path: '/hr/payroll', icon: Banknote, permission: 'hr.payroll.view' },
      { label: 'Recruitment', path: '/hr/recruitment', icon: UserCircle, permission: 'hr.recruitment.view' },
      { label: 'Training', path: '/hr/training', icon: BookOpen, permission: 'hr.training.view' },
      { label: 'Performance', path: '/hr/performance', icon: TrendingUp, permission: 'hr.performance.view' },
      { label: 'Expenses', path: '/hr/expenses', icon: CreditCard, permission: 'hr.expenses.view' },
      { label: 'Commission Plans', path: '/commission/plans', icon: Percent, permission: 'hr.commission.manage' },
      { label: 'Commission Targets', path: '/commission/targets', icon: TrendingUp, permission: 'hr.commission.view' },
    ],
  },
  {
    key: 'accounting',
    label: 'Financials',
    icon: Calculator,
    permission: 'accounting.view',
    routes: [
      { label: 'Chart of Accounts', path: '/accounting/accounts', icon: BookOpen, permission: 'accounting.accounts.view' },
      { label: 'Trial Balance', path: '/accounting/trial-balance', icon: Calculator, permission: 'accounting.reports.view' },
      { label: 'Taxes', path: '/accounting/taxes', icon: Percent, permission: 'accounting.taxes.manage' },
      { label: 'Fiscal Periods', path: '/accounting/periods', icon: FileText, permission: 'accounting.periods.manage' },
      { label: 'Banks', path: '/accounting/banks', icon: Banknote, permission: 'accounting.banks.view' },
      { label: 'Bank Reconcil.', path: '/accounting/bank-reconciliation', icon: Activity, permission: 'accounting.recon.manage' },
      { label: 'Budgets', path: '/accounting/budgets', icon: TrendingUp, permission: 'accounting.budgets.view' },
      { label: 'Payment Methods', path: '/accounting/payments/methods', icon: CreditCard, permission: 'accounting.payments.manage' },
      { label: 'Payments', path: '/accounting/payments/list', icon: Banknote, permission: 'accounting.payments.view' },
      { label: 'Expenses', path: '/accounting/payments/expenses', icon: CreditCard, permission: 'accounting.expenses.view' },

    ],
  },
  {
    key: 'reports',
    label: 'Reporting',
    icon: BarChart3,
    permission: 'reports.view',
    routes: [
      { label: 'Analytics', path: '/reports/dashboard', icon: BarChart3, permission: 'reports.dashboard.view' },
      { label: 'Turnover', path: '/reports/turnover', icon: TrendingUp, permission: 'reports.finance.view' },
      { label: 'SFA Performance', path: '/reports/sfa-performance', icon: Activity, permission: 'reports.sfa.view' },
      { label: 'Acc. Summary', path: '/reports/accounting-summary', icon: FileText, permission: 'reports.finance.view' },
      { label: 'Income Statement', path: '/reports/income-statement', icon: FileText, permission: 'reports.finance.view' },
      { label: 'Balance Sheet', path: '/reports/balance-sheet', icon: BookOpen, permission: 'reports.finance.view' },
      { label: 'Cash Flow', path: '/reports/cash-flow', icon: Banknote, permission: 'reports.finance.view' },
    ],
  },
]


