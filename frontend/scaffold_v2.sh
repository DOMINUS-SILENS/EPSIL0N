#!/bin/bash
set -e

# Target base path
BASE="src/modules"
mkdir -p $BASE

# Function to create a placeholder page
create_page() {
  local path=$1
  local name=$2
  mkdir -p "$(dirname "$path")"
  echo "import React from 'react';" > "$path"
  echo "" >> "$path"
  echo "export function $name() {" >> "$path"
  echo "  return (" >> "$path"
  echo "    <div className=\"p-6\">" >> "$path"
  echo "      <h1 className=\"text-2xl font-bold mb-4\">$name</h1>" >> "$path"
  echo "      <p className=\"text-neutral-600 dark:text-neutral-400\">Placeholder for $name</p>" >> "$path"
  echo "    </div>" >> "$path"
  echo "  );" >> "$path"
  echo "}" >> "$path"
  echo "" >> "$path"
  echo "export default $name;" >> "$path"
}

# 1. CORE
for page in CompaniesList CompaniesCreate CompanyOverview CompanySettings CompanyIntercompany CompanyUsers \
            CurrenciesList CurrencyRates CurrencyProviders CurrencyHistory \
            DmsFolders DmsFiles DmsUpload DmsPreview DmsVersions DmsOcr \
            WorkflowsList WorkflowBuilder WorkflowExecutions WorkflowApprovals WorkflowLogs \
            CronJobsList CronJobsCreate CronJobsLogs CronJobsFailed \
            EmailTemplatesList EmailTemplatesCreate EmailEditor EmailPreview EmailVariables \
            ApiKeys Webhooks IntegrationsLogs Connectors \
            DataImportUpload DataImportMapping DataImportHistory DataImportErrors \
            SecurityPassword SecuritySessions SecurityIpRestrictions SecurityAudit \
            BackupsList BackupsCreate BackupsRestore BackupsSchedules; do
  create_page "$BASE/core/pages/${page}Page.tsx" "${page}Page"
done

# 2. ACCOUNTING
for page in AssetsList AssetsCreate AssetDepreciation AssetEntries AssetDisposals \
            CostCentersList CostHierarchy CostAllocations CostReports \
            TreasuryDashboard CashPosition CashForecasts TreasuryLoans BankFacilities \
            DeferredRevList DeferredRevSchedules DeferredRevEntries \
            ReconBank ReconManual ReconRules \
            ClosingPeriod ClosingYear ClosingChecklist; do
  create_page "$BASE/accounting/pages/${page}Page.tsx" "${page}Page"
done

# 3. INVENTORY
for page in InvRoutesList InvRoutesCreate InvRoutesRules \
            InvLocTree InvLocBins InvLocPutaway InvLocRemoval \
            InvQcList InvQcTemplates InvQcAlerts \
            InvRepList InvRepForecast InvRepAuto \
            InvOpsReceipts InvOpsPickings InvOpsPackings InvOpsTransfers \
            InvValMethods InvValHistory InvValReports; do
  create_page "$BASE/inventory/pages/${page}Page.tsx" "${page}Page"
done

# 4. SALES
for page in SubPlans SubscriptionsList SubBilling SubRenewals \
            RmaRequests RmaApprovals RmaReturns RmaRefunds \
            LoyaltyPrograms LoyaltyRules LoyaltyCustomers LoyaltyTransactions \
            PortalCustomers PortalOrders PortalPayments PortalTickets; do
  create_page "$BASE/sales/pages/${page}Page.tsx" "${page}Page"
done

# 5. PURCHASING
for page in RfqList RfqCompare RfqAward \
            PriceSupplierList PriceSupplierRules \
            PurchAgreements PurchContracts PurchReleases \
            PurchReceipts PurchQuality PurchPutaway; do
  create_page "$BASE/erp/pages/purchasing/${page}Page.tsx" "${page}Page"
done

# 6. HR
for page in HrProfile HrLeave HrExpenses HrDocuments HrPayslips \
            TimesheetEntries TimesheetCalendar TimesheetProjects TimesheetApproval \
            SuccessionPositions SuccessionCandidates SuccessionRisk \
            HrWorkflowsApprovals; do
  create_page "$BASE/hr/pages/${page}Page.tsx" "${page}Page"
done

# 7. LOGISTICS
for page in LogisticsProviders LogisticsRates LogisticsLabels LogisticsTracking \
            TrackingMap TrackingHistory TrackingExceptions; do
  create_page "$BASE/delivery/pages/${page}Page.tsx" "${page}Page"
done

# 8. REPORTING
for page in RepAdvancedPivot RepAdvancedCube RepAdvancedDrill \
            RepScheduledList RepScheduledCreate RepScheduledHistory \
            RepBuilder; do
  create_page "$BASE/reports/pages/${page}Page.tsx" "${page}Page"
done

# 9. PROJECTS
for page in ProjList ProjTasks ProjKanban ProjGantt ProjTimesheets ProjMilestones; do
  create_page "$BASE/projects/pages/${page}Page.tsx" "${page}Page"
done

# 10. HELPDESK
for page in HelpdeskTickets HelpdeskSla HelpdeskKb HelpdeskPortal; do
  create_page "$BASE/helpdesk/pages/${page}Page.tsx" "${page}Page"
done

# 11. ECOMMERCE
for page in EcomProducts EcomOrders EcomCustomers EcomCheckout EcomPayments EcomThemes; do
  create_page "$BASE/ecommerce/pages/${page}Page.tsx" "${page}Page"
done

# 12. SETTINGS
for page in SetGenCompany SetGenLocalization SetGenFeatures SetGenDefaults \
            SetUiFields SetUiViews SetUiLayouts SetUiAutomation \
            SetSeqList SetSeqRules SetSeqPreview \
            SetUomUnits SetUomCategories SetUomConversions \
            SetAttrAttributes SetAttrValues SetAttrVariants \
            SetNotifyEmail SetNotifyPush \
            SetAuditLogs SetAuditExport; do
  create_page "$BASE/settings/pages/${page}Page.tsx" "${page}Page"
done

# 13. GLOBAL
for page in GlobalSearch GlobalActivityLog GlobalNotifications GlobalDashboard; do
  create_page "$BASE/dashboard/pages/${page}Page.tsx" "${page}Page"
done

echo "Scaffolding complete!"
