import { useState } from 'react'
import { Plus, Search, FileText, Loader2 } from 'lucide-react'
import { usePermissions } from '@/core/auth/useAuth'
import { cn } from '@/lib/utils'
import { ordersApi } from '../api/ordersApi'
import { useQuery } from '@tanstack/react-query'
import type { Order, PaginatedResponse } from '../api/types'

export function OrdersListPage() {
  const { can } = usePermissions()
  const [search, setSearch] = useState('')

  const { data: pagedOrders, isLoading } = useQuery<PaginatedResponse<Order>>({
    queryKey: ['orders', search],
    queryFn: async () => {
      const resp = await ordersApi.list({ search })
      return resp.data
    },
  })

  const orders: Order[] = pagedOrders?.data ?? []

  const formatCurrency = (val: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val)

  const getStatusBadge = (status: string) => {
    switch (status.toLowerCase()) {
      case 'confirmed':
        return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
      case 'draft':
        return 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-400'
      case 'sent':
        return 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'
      default:
        return 'bg-neutral-100 text-neutral-700'
    }
  }

  return (
    <div className="p-6 max-w-[1600px] mx-auto space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-neutral-900 dark:text-neutral-100">
            Sales Orders
          </h1>
          <p className="text-sm text-neutral-500 dark:text-neutral-400">
            Manage quotations and confirmed sales orders
          </p>
        </div>

        <div className="flex items-center gap-2">
          {can('orders.create') && (
            <button className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors font-medium text-sm">
              <Plus className="h-4 w-4" />
              New Quotation
            </button>
          )}
        </div>
      </div>

      <div className="bg-white dark:bg-neutral-900 shadow-sm border border-neutral-200 dark:border-neutral-800 rounded-lg overflow-hidden flex flex-col">
        <div className="p-4 border-b border-neutral-200 dark:border-neutral-800 flex items-center gap-4">
          <div className="relative w-full max-w-md">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-neutral-400" />
            <input
              type="text"
              placeholder="Search by Order ID or Customer..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full pl-9 pr-4 py-2 bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm"
            />
          </div>
        </div>

        {isLoading ? (
          <div className="flex items-center justify-center p-20">
            <Loader2 className="h-8 w-8 animate-spin text-primary" />
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm whitespace-nowrap">
              <thead className="bg-neutral-50 dark:bg-neutral-800/50 text-neutral-500 dark:text-neutral-400">
                <tr>
                  <th className="px-6 py-3 font-medium">Order Number</th>
                  <th className="px-6 py-3 font-medium">Customer</th>
                  <th className="px-6 py-3 font-medium">Order Date</th>
                  <th className="px-6 py-3 font-medium">Total</th>
                  <th className="px-6 py-3 font-medium">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                {orders.map((order) => (
                  <tr
                    key={order.id}
                    className="hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors cursor-pointer"
                    onClick={() => {
                      window.location.href = `/sales/orders/${order.reference}`
                    }}
                  >
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-2 font-medium text-primary">
                        <FileText className="h-4 w-4" />
                        {order.reference}
                      </div>
                    </td>
                    <td className="px-6 py-4 font-semibold text-neutral-900 dark:text-neutral-100">
                      {order.customer_name}
                    </td>
                    <td className="px-6 py-4 text-neutral-600 dark:text-neutral-400">
                      {new Date(order.created_at).toLocaleDateString()}
                    </td>
                    <td className="px-6 py-4 font-medium text-neutral-900 dark:text-neutral-100">
                      {formatCurrency(Number(order.total_ttc))}
                    </td>
                    <td className="px-6 py-4">
                      <span
                        className={cn(
                          'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold',
                          getStatusBadge(order.status),
                        )}
                      >
                        {order.status}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  )
}

export default OrdersListPage
