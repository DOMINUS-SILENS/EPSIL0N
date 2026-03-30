import { ColumnDef } from '@tanstack/react-table';
import { Order } from '../../api/types';

export const columns: ColumnDef<Order>[] = [
  {
    accessorKey: 'id',
    header: 'ID',
  },
  {
    accessorKey: 'customer_name',
    header: 'Customer',
  },
  {
    accessorKey: 'total',
    header: 'Total',
    cell: ({ getValue }) => {
      const value = getValue() as number;
      return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
    },
  },
  {
    accessorKey: 'status',
    header: 'Status',
  },
  {
    accessorKey: 'created_at',
    header: 'Date',
    cell: ({ getValue }) => new Date(getValue() as string).toLocaleDateString(),
  },
];
