import { ColumnDef } from '@tanstack/react-table';
import { Order } from '../../api/types';
import { Badge } from '@/components/ui/badge';
import { formatCurrency, formatDate } from '@/lib/utils';

export const columns: ColumnDef<Order>[] = [
  {
    accessorKey: 'reference',
    header: 'Reference',
    cell: ({ row }) => <span className="font-mono text-sm">{row.getValue('reference')}</span>,
  },
  {
    accessorKey: 'customer_id',
    header: 'Customer ID',
    cell: ({ row }) => row.getValue('customer_id'),
  },
  {
    accessorKey: 'total_amount',
    header: 'Total',
    cell: ({ row }) => formatCurrency(row.getValue('total_amount')),
  },
  {
    accessorKey: 'state',
    header: 'Status',
    cell: ({ row }) => {
      const state = row.getValue('state') as string;
      const variantMap: Record<string, string> = {
        draft: 'outline',
        confirmed: 'default',
        processing: 'warning',
        completed: 'success',
        cancelled: 'destructive',
      };
      return <Badge variant={variantMap[state] as unknown as string}>{state}</Badge>;
    },
  },
  {
    accessorKey: 'order_date',
    header: 'Date',
    cell: ({ row }) => formatDate(row.getValue('order_date')),
  },
];
