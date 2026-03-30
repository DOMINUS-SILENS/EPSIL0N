import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const basePath = path.join(__dirname, '../src/modules');

const pagesToCreate = [
  // Core
  'core/pages/ProfilePage.tsx',
  'core/pages/CompanySettingsPage.tsx',
  'core/pages/AuditLogsPage.tsx',
  // CRM
  'crm/pages/CampaignsListPage.tsx',
  'crm/pages/ActivitiesListPage.tsx',
  // Commercial
  'commercial/pages/CouponsListPage.tsx',
  // Inventory
  'inventory/pages/WarehousesListPage.tsx',
  'inventory/pages/StockMovementsPage.tsx',
  'inventory/pages/StockAdjustmentsPage.tsx',
  // ERP (Purchasing & Manufacturing)
  'erp/pages/SuppliersListPage.tsx',
  'erp/pages/PurchaseOrdersListPage.tsx',
  'erp/pages/PurchaseReceiptsPage.tsx',
  'erp/pages/BOMListPage.tsx',
  'erp/pages/ManufacturingOrdersPage.tsx',
  // Accounting
  'accounting/pages/TaxesPage.tsx',
  'accounting/pages/FiscalPeriodsPage.tsx',
  'accounting/pages/BanksPage.tsx',
  'accounting/pages/BankReconciliationPage.tsx',
  'accounting/pages/BudgetsPage.tsx',
  'accounting/pages/PaymentMethodsPage.tsx',
  'accounting/pages/PaymentsListPage.tsx',
  'accounting/pages/ExpenseClaimsPage.tsx',
  // HR
  'hr/pages/ContractsListPage.tsx',
  'hr/pages/AttendancePage.tsx',
  'hr/pages/PayrollPage.tsx',
  'hr/pages/RecruitmentPage.tsx',
  'hr/pages/TrainingPage.tsx',
  'hr/pages/PerformanceReviewsPage.tsx',
  'hr/pages/ExpensesPage.tsx',
  'hr/pages/CommissionPlansPage.tsx',
  'hr/pages/CommissionTargetsPage.tsx',
  // SFA
  'sfa/pages/ChecklistsPage.tsx',
  'sfa/pages/DashboardPage.tsx',
  // Delivery & Fleet
  'delivery/pages/DeliveryToursPage.tsx',
  'fleet/pages/FleetPage.tsx',
  // Trade Marketing
  'trademkt/pages/PlanogramsPage.tsx',
  'trademkt/pages/POSMaterialsPage.tsx',
  'trademkt/pages/AuditsPage.tsx',
  // Pre-sales
  'presales/pages/DemosPage.tsx',
  'presales/pages/SampleOrdersPage.tsx',
  // Reports
  'reports/pages/IncomeStatementPage.tsx',
  'reports/pages/BalanceSheetPage.tsx',
  'reports/pages/CashFlowPage.tsx'
];

const template = (name) => `import React from 'react';

export function ${name}() {
  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold mb-4">${name.replace(/([A-Z])/g, ' $1').trim()}</h1>
      <p className="text-neutral-600 dark:text-neutral-400">
        This is a placeholder page for ${name}.
      </p>
    </div>
  );
}

export default ${name};
`;

pagesToCreate.forEach(pagePath => {
  const fullPath = path.join(basePath, pagePath);
  const dir = path.dirname(fullPath);
  
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }

  const componentName = path.basename(pagePath, '.tsx');
  
  if (!fs.existsSync(fullPath)) {
    fs.writeFileSync(fullPath, template(componentName));
    console.log('Created:', pagePath);
  } else {
    console.log('Exists:', pagePath);
  }
});
