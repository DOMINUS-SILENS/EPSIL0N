import { createRoute } from '@tanstack/react-router'
import { protectedRoute } from '@/app/routes/base'

export const crmRoute = createRoute({
  getParentRoute: () => protectedRoute,
  path: 'crm',
})
import { LeadsListPage } from './pages/LeadsListPage'
import { LeadDetailPage } from './pages/LeadDetailPage'
import { OpportunitiesListPage } from './pages/OpportunitiesListPage'
import { OpportunityDetailPage } from './pages/OpportunityDetailPage'
import { CustomersListPage } from './pages/CustomersListPage'
import { QuotesListPage } from './pages/QuotesListPage'
import { ActivitiesListPage } from './pages/ActivitiesListPage'
import { CampaignsListPage } from './pages/CampaignsListPage'

export const leadsListRoute = createRoute({
  getParentRoute: () => crmRoute,
  path: 'leads',
  component: LeadsListPage,
})

export const leadDetailRoute = createRoute({
  getParentRoute: () => crmRoute,
  path: 'leads/$id',
  component: LeadDetailPage,
})

export const opportunitiesListRoute = createRoute({
  getParentRoute: () => crmRoute,
  path: 'opportunities',
  component: OpportunitiesListPage,
})

export const opportunityDetailRoute = createRoute({
  getParentRoute: () => crmRoute,
  path: 'opportunities/$id',
  component: OpportunityDetailPage,
})

export const customersListRoute = createRoute({
  getParentRoute: () => crmRoute,
  path: 'customers',
  component: CustomersListPage,
})

export const quotesListRoute = createRoute({
  getParentRoute: () => crmRoute,
  path: 'quotes',
  component: QuotesListPage,
})

export const quoteDetailRoute = createRoute({
  getParentRoute: () => crmRoute,
  path: 'quotes/$id',
  component: QuotesListPage, // Placeholder
})

export const newQuoteRoute = createRoute({
  getParentRoute: () => crmRoute,
  path: 'quotes/new',
  component: QuotesListPage, // Placeholder
})

export const activitiesListRoute = createRoute({
  getParentRoute: () => crmRoute,
  path: 'activities',
  component: ActivitiesListPage,
})

export const campaignsListRoute = createRoute({
  getParentRoute: () => crmRoute,
  path: 'campaigns',
  component: CampaignsListPage,
})

export const crmRouteTree = crmRoute.addChildren([
  leadsListRoute,
  leadDetailRoute,
  opportunitiesListRoute,
  opportunityDetailRoute,
  customersListRoute,
  quotesListRoute,
  quoteDetailRoute,
  newQuoteRoute,
  activitiesListRoute,
  campaignsListRoute,
])

