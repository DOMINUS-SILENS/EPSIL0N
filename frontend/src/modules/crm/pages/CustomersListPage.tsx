import { useState } from 'react'
import { Plus, Search, Filter, Building2, TrendingUp, MapPin, MoreVertical } from 'lucide-react'
import { usePermissions } from '@/core/auth/useAuth'

const MOCK_CUSTOMERS = [
  { id: '1', name: 'Alpha Retail Group', industry: 'Retail', location: 'New York, US', rep: 'John Doe', clv: 125000, tags: ['Key Account', 'Enterprise'], active: true },
  { id: '2', name: 'Beta Logistics Inc', industry: 'Transportation', location: 'London, UK', rep: 'Sarah Connor', clv: 89000, tags: ['Growth'], active: true },
  { id: '3', name: 'Gamma Materials', industry: 'Manufacturing', location: 'Berlin, DE', rep: 'John Doe', clv: 45000, tags: ['Standard'], active: false },
  { id: '4', name: 'Delta Software Systems', industry: 'Technology', location: 'San Francisco, US', rep: 'Emily Davis', clv: 210000, tags: ['Key Account', 'SaaS'], active: true },
]

export function CustomersListPage() {
  const { can } = usePermissions()
  const [search, setSearch] = useState('')

  const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(val)
  }

  return (
    <div className="p-6 max-w-[1600px] mx-auto space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-neutral-900 dark:text-neutral-100">Customers</h1>
          <p className="text-sm text-neutral-500 dark:text-neutral-400">Manage accounts, customer lifetime value, and firmographics</p>
        </div>

        <div className="flex items-center gap-2">
          <button className="flex items-center gap-2 px-4 py-2 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 text-neutral-700 dark:text-neutral-300 rounded-md hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors font-medium text-sm">
            Import/Export
          </button>

          {can('customers.create') && (
            <button className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors font-medium text-sm">
              <Plus className="h-4 w-4" />
              New Customer
            </button>
          )}
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div className="md:col-span-1 space-y-4">
          <div className="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-lg p-5 shadow-sm">
            <h3 className="font-semibold text-neutral-800 dark:text-neutral-200 mb-4 flex items-center gap-2">
              <Filter className="h-4 w-4" />
              Advanced Filters
            </h3>

            <div className="space-y-4">
              <div>
                <label className="text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider mb-2 block">Industry</label>
                <div className="space-y-2">
                  {['Retail', 'Technology', 'Manufacturing', 'Transportation'].map(ind => (
                    <label key={ind} className="flex items-center gap-2 text-sm text-neutral-700 dark:text-neutral-300">
                      <input type="checkbox" className="rounded border-neutral-300 text-primary focus:ring-primary/50" />
                      {ind}
                    </label>
                  ))}
                </div>
              </div>

              <div>
                <label className="text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider mb-2 block">Account Tags</label>
                <div className="flex flex-wrap gap-2">
                  {['Key Account', 'Enterprise', 'Growth', 'Standard', 'Churn Risk'].map(tag => (
                    <span key={tag} className="px-2 py-1 bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 rounded-md text-xs cursor-pointer hover:bg-neutral-200 dark:hover:bg-neutral-700 transition-colors">
                      {tag}
                    </span>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="md:col-span-3 bg-white dark:bg-neutral-900 shadow-sm border border-neutral-200 dark:border-neutral-800 rounded-lg overflow-hidden flex flex-col">
          <div className="p-4 border-b border-neutral-200 dark:border-neutral-800">
            <div className="relative w-full max-w-md">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-neutral-400" />
              <input
                type="text"
                placeholder="Search customers by name, location, or tag..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="w-full pl-9 pr-4 py-2 bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm"
              />
            </div>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm whitespace-nowrap">
              <thead className="bg-neutral-50 dark:bg-neutral-800/50 text-neutral-500 dark:text-neutral-400">
                <tr>
                  <th className="px-6 py-3 font-medium">Customer</th>
                  <th className="px-6 py-3 font-medium">Location</th>
                  <th className="px-6 py-3 font-medium">Tags</th>
                  <th className="px-6 py-3 font-medium">LTV</th>
                  <th className="px-6 py-3 font-medium">Acct Rep</th>
                  <th className="px-6 py-3 font-medium text-right"></th>
                </tr>
              </thead>
              <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                {MOCK_CUSTOMERS.map((customer) => (
                  <tr key={customer.id} className="hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors cursor-pointer group">
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-3">
                        <div className={`h-10 w-10 flex items-center justify-center rounded-lg ${customer.active ? 'bg-primary/10 text-primary' : 'bg-neutral-100 text-neutral-400 dark:bg-neutral-800'}`}>
                          <Building2 className="h-5 w-5" />
                        </div>
                        <div>
                          <div className={`font-semibold ${customer.active ? 'text-neutral-900 dark:text-neutral-100' : 'text-neutral-500 dark:text-neutral-500'}`}>
                            {customer.name}
                          </div>
                          <div className="text-xs text-neutral-500 mt-0.5">{customer.industry}</div>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-neutral-600 dark:text-neutral-400">
                      <div className="flex items-center gap-1.5">
                        <MapPin className="h-3.5 w-3.5" />
                        <span>{customer.location}</span>
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex gap-1">
                        {customer.tags.map(tag => (
                          <span key={tag} className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400">
                            {tag}
                          </span>
                        ))}
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-1.5 font-medium text-neutral-800 dark:text-neutral-200">
                        <TrendingUp className="h-3.5 w-3.5 text-green-500" />
                        {formatCurrency(customer.clv)}
                      </div>
                    </td>
                    <td className="px-6 py-4 text-neutral-600 dark:text-neutral-400">
                      {customer.rep}
                    </td>
                    <td className="px-6 py-4 text-right">
                      <button className="p-1.5 text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100 transition-colors rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-800 opacity-0 group-hover:opacity-100">
                        <MoreVertical className="h-5 w-5" />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  )
}

export default CustomersListPage;
