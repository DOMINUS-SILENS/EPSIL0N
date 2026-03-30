
import { PlugZap, Link, KeyRound, AlertTriangle } from 'lucide-react'

// Dummy logos for visual representation
const IntegrationCard = ({ name, description, active, connected, type }: { name: string; description: string; active: boolean; connected: boolean; type: string }) => (
  <div className={`p-6 bg-white dark:bg-neutral-900 border ${active ? 'border-neutral-200 dark:border-neutral-800' : 'border-neutral-200 dark:border-neutral-800 opacity-60 grayscale'} rounded-xl shadow-sm flex flex-col justify-between`}>
    <div>
      <div className="flex justify-between items-start mb-4">
        <h3 className="font-bold text-neutral-900 dark:text-neutral-100 text-lg flex items-center gap-2">
          {name}
        </h3>
        {connected ? (
          <span className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400">Connected</span>
        ) : (
          <span className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400">Disconnected</span>
        )}
      </div>
      <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-6 line-clamp-2">
        {description}
      </p>
    </div>

    <div className="mt-auto">
      {type === 'api' ? (
        <button className="flex w-full items-center justify-center gap-2 px-4 py-2 bg-neutral-100 hover:bg-neutral-200 dark:bg-neutral-800 dark:hover:bg-neutral-700 text-neutral-700 dark:text-neutral-300 rounded-md transition-colors font-medium text-xs">
          <KeyRound className="h-4 w-4" /> Provider Keys
        </button>
      ) : (
        <button className={`flex w-full items-center justify-center gap-2 px-4 py-2 ${connected ? 'bg-red-50 hover:bg-red-100 text-red-600 dark:bg-red-500/10 dark:hover:bg-red-500/20 dark:text-red-500' : 'bg-primary text-primary-foreground hover:bg-primary/90'} rounded-md transition-colors font-medium text-xs`}>
          <Link className="h-4 w-4" /> {connected ? 'Disconnect App' : 'Connect Account'}
        </button>
      )}
    </div>
  </div>
)

export function IntegrationsPage() {
  return (
    <div className="p-6 max-w-[1400px] mx-auto space-y-8">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-neutral-200 dark:border-neutral-800 pb-6">
        <div>
          <h1 className="text-2xl font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
            <PlugZap className="h-6 w-6 text-primary" /> Third-Party Integrations
          </h1>
          <p className="text-sm text-neutral-500 dark:text-neutral-400 mt-1">Connect Alpha ERP to your favorite platforms via API or OAuth securely</p>
        </div>
      </div>

      <div className="space-y-6">
        <h2 className="text-lg font-bold text-neutral-900 dark:text-neutral-100">Financial Gateways</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
          <IntegrationCard name="Stripe" description="Process B2B credit card payments and sync invoices natively." active={true} connected={true} type="api" />
          <IntegrationCard name="PayPal Braintree" description="Alternative checkout flow for wholesale portals." active={true} connected={false} type="api" />
          <IntegrationCard name="Plaid" description="Direct bank feeds for real-time Accounting reconciliation." active={true} connected={true} type="api" />
          <IntegrationCard name="Square" description="POS integration for physical storefronts." active={false} connected={false} type="oauth" />
        </div>
      </div>

      <div className="space-y-6">
        <h2 className="text-lg font-bold text-neutral-900 dark:text-neutral-100">Productivity & Communication</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
          <IntegrationCard name="Slack" description="Push ERP alerts and Workflow approvals to Slack channels." active={true} connected={true} type="oauth" />
          <IntegrationCard name="Microsoft Teams" description="Microsoft 360 unified communication webhooks." active={true} connected={false} type="oauth" />
          <IntegrationCard name="Google Workspace" description="SSO and Calendar synchronization for HR." active={true} connected={true} type="oauth" />
          <IntegrationCard name="Zendesk" description="Support ticket bi-directional syncing for Helpdesk." active={false} connected={false} type="api" />
        </div>
      </div>

      <div className="bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-lg p-4 flex gap-3 text-amber-800 dark:text-amber-400 mt-8">
        <AlertTriangle className="h-5 w-5 shrink-0 mt-0.5" />
        <div>
          <h4 className="font-bold text-sm">Beta Developer Access</h4>
          <p className="text-sm mt-1">Custom API webhooks and GraphQL terminal generation are currently restricted to Enterprise tier accounts. Contact platform support to enable root-level API limits.</p>
        </div>
      </div>
    </div>
  )
}

export default IntegrationsPage;
