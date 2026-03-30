import { ColumnDef } from '@tanstack/react-table';
import { Customer } from '../../api/customersApi';
import { Badge } from '@/design-system/primitives/Badge/Badge';
import { Button } from '@/design-system/primitives/Button/Button';
import { Eye, Edit, ShoppingCart, DollarSign } from 'lucide-react';
import { format } from 'date-fns';

export const columns: ColumnDef<Customer>[] = [
  {
    accessorKey: 'reference',
    header: 'Reference',
    size: 120,
  },
  {
    accessorKey: 'name',
    header: 'Name',
    size: 200,
  },
  {
    accessorKey: 'email',
    header: 'Email',
    size: 200,
  },
  {
    accessorKey: 'phone',
    header: 'Phone',
    size: 120,
  },
  {
    accessorKey: 'city',
    header: 'City',
    size: 100,
  },
  {
    accessorKey: 'country',
    header: 'Country',
    size: 100,
  },
  {
    accessorKey: 'total_orders',
    header: 'Orders',
    size: 80,
    cell: ({ row }) => {
      const orders = row.getValue('total_orders') as number;
      return (
        <div className="flex items-center">
          <ShoppingCart className="mr-1 h-4 w-4" />
          {orders}
        </div>
      );
    },
  },
  {
    accessorKey: 'total_revenue',
    header: 'Revenue',
    size: 120,
    cell: ({ row }) => {
      const revenue = row.getValue('total_revenue') as number;
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
      }).format(revenue);
    },
  },
  {
    accessorKey: 'is_active',
    header: 'Status',
    size: 80,
    cell: ({ row }) => {
      const isActive = row.getValue('is_active') as boolean;
      return (
        <Badge variant={isActive ? 'default' : 'secondary'}>
          {isActive ? 'Active' : 'Inactive'}
        </Badge>
      );
    },
  },
  {
    accessorKey: 'last_order_date',
    header: 'Last Order',
    size: 120,
    cell: ({ row }) => {
      const date = row.getValue('last_order_date') as string;
      return date ? format(new Date(date), 'MMM dd, yyyy') : 'Never';
    },
  },
  {
    id: 'actions',
    header: 'Actions',
    size: 120,
    cell: ({ row, table }) => {
      const customer = row.original;
      const { meta } = table.options;

      return (
        <div className="flex items-center gap-2">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => meta?.onView?.(customer)}
          >
            <Eye className="h-4 w-4" />
          </Button>
          <Button
            variant="ghost"
            size="sm"
            onClick={() => meta?.onEdit?.(customer)}
          >
            <Edit className="h-4 w-4" />
          </Button>
          <Button
            variant="ghost"
            size="sm"
            onClick={() => meta?.onViewOrders?.(customer)}
          >
            <DollarSign className="h-4 w-4" />
          </Button>
        </div>
      );
    },
  },
];
