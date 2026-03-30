import { ArrowLeft, Printer, Loader2 } from 'lucide-react'
import { ordersApi } from '../api/ordersApi'
import { useQuery, useMutation } from '@tanstack/react-query'
import { toast } from 'sonner'
import { useState } from 'react'
import { cn } from '@/lib/utils'
import type { Order } from '../api/types'

export function OrderDetailPage({ id = 'SO-2026-001' }: { id?: string }) {
  const [, setOrderId] = useState<string | null>(null)

  const { data: order, isLoading, refetch } = useQuery<Order>({
    queryKey: ['order', id],
    queryFn: async () => {
      const response = await ordersApi.list({ search: id })
      const items = response.data.data
      if (items && items.length > 0) {
        setOrderId(items[0].id)
        return items[0]
      }
      throw new Error('Order not found')
    },
    enabled: !!id,
  })

  const confirmMutation = useMutation({
    mutationFn: (oid: string) => ordersApi.confirm(oid),
    onSuccess: () => {
      toast.success('Order confirmed successfully')
      refetch()
    },
    onError: (err: Error) => {
      toast.error('Failed to confirm order: ' + (err.message || 'Unknown error'))
    },
  })

  const formatCurrency = (val: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val)

  if (isLoading)
    return (
      <div className="flex items-center justify-center p-20">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
      </div>
    )
  if (!order)
    return <div className="p-20 text-center">Order {id} not found in database.</div>

  return (
    <div className="p-6 max-w-5xl mx-auto space-y-6">
      <div className="flex items-center justify-between">
        <button
          onClick={() => window.history.back()}
          className="flex items-center gap-2 text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-100 transition-colors text-sm font-medium"
        >
          <ArrowLeft className="h-4 w-4" />
          Back to Orders
        </button>
        <div className="flex items-center gap-2">
          <button className="p-2 text-neutral-600 hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded-md transition-colors">
            <Printer className="h-4 w-4" />
          </button>
          <button
            id="confirm-order-btn"
            disabled={confirmMutation.isPending || order.status === 'confirmed'}
            onClick={() => confirmMutation.mutate(order.id)}
            className="flex items-center gap-2 px-4 py-1.5 text-sm bg-primary text-primary-foreground hover:bg-primary/90 rounded-md transition-colors font-medium disabled:opacity-50"
          >
            {confirmMutation.isPending ? 'Confirming...' : 'Confirm Order'}
          </button>
        </div>
      </div>

      <div className="bg-white dark:bg-neutral-900 shadow-sm border border-neutral-200 dark:border-neutral-800 rounded-lg p-8">
        <div className="flex justify-between items-start mb-12 border-b border-neutral-100 dark:border-neutral-800 pb-8">
          <div>
            <h1 className="text-3xl font-bold text-neutral-900 dark:text-neutral-100 mb-2">
              Quotation <span className="text-primary">{order.reference}</span>
            </h1>
            <span
              className={cn(
                'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold',
                order.status === 'confirmed'
                  ? 'bg-blue-100 text-blue-700'
                  : 'bg-neutral-100 text-neutral-700',
              )}
            >
              {order.status}
            </span>
          </div>
          <div className="text-right space-y-1">
            <div className="text-xl font-bold text-neutral-900 dark:text-neutral-100">
              {order.customer_name}
            </div>
          </div>
        </div>

        <div className="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8 text-sm">
          <div>
            <div className="text-neutral-500 mb-1">Date</div>
            <div className="font-medium text-neutral-900 dark:text-neutral-100">
              {new Date(order.created_at).toLocaleDateString()}
            </div>
          </div>
        </div>

        <div className="flex justify-end">
          <div className="w-72 space-y-3 text-sm">
            <div className="flex justify-between text-neutral-600 dark:text-neutral-400">
              <span>Untaxed Amount</span>
              <span>{formatCurrency(Number(order.total_ht))}</span>
            </div>
            <div className="flex justify-between font-bold text-lg text-neutral-900 dark:text-neutral-100 pt-1">
              <span>Total</span>
              <span>{formatCurrency(Number(order.total_ttc))}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default OrderDetailPage
