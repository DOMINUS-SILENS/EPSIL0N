import { ColumnDef } from '@tanstack/react-table';
import { Lead } from '../../api/leadsApi';
import { Badge } from '@/design-system/primitives/Badge/Badge';
import { Button } from '@/design-system/primitives/Button/Button';
import { Eye, Edit, UserPlus } from 'lucide-react';
import { format } from 'date-fns';

const getStateVariant = (state: string) => {
  switch (state) {
    case 'new':
      return 'default';
    case 'contacted':
      return 'secondary';
    case 'qualified':
      return 'outline';
    case 'lost':
      return 'destructive';
    default:
      return 'default';
  }
};

const getStateLabel = (state: string) => {
  switch (state) {
    case 'new':
      return 'New';
    case 'contacted':
      return 'Contacted';
    case 'qualified':
      return 'Qualified';
    case 'lost':
      return 'Lost';
    default:
      return state;
  }
};

export const columns: ColumnDef<Lead>[] = [
  {
    accessorKey: 'reference',
    header: 'Reference',
    size: 120,
  },
  {
    accessorKey: 'first_name',
    header: 'First Name',
    size: 150,
  },
  {
    accessorKey: 'last_name',
    header: 'Last Name',
    size: 150,
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
    accessorKey: 'source',
    header: 'Source',
    size: 100,
  },
  {
    accessorKey: 'state',
    header: 'Status',
    size: 100,
    cell: ({ row }) => {
      const state = row.getValue('state') as string;
      return (
        <Badge variant={getStateVariant(state)}>
          {getStateLabel(state)}
        </Badge>
      );
    },
  },
  {
    accessorKey: 'created_at',
    header: 'Created',
    size: 120,
    cell: ({ row }) => {
      const date = row.getValue('created_at') as string;
      return format(new Date(date), 'MMM dd, yyyy');
    },
  },
  {
    id: 'actions',
    header: 'Actions',
    size: 120,
    cell: ({ row, table }) => {
      const lead = row.original;
      const { meta } = table.options;

      return (
        <div className="flex items-center gap-2">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => meta?.onView?.(lead)}
          >
            <Eye className="h-4 w-4" />
          </Button>
          <Button
            variant="ghost"
            size="sm"
            onClick={() => meta?.onEdit?.(lead)}
          >
            <Edit className="h-4 w-4" />
          </Button>
          {lead.state === 'qualified' && (
            <Button
              variant="ghost"
              size="sm"
              onClick={() => meta?.onConvert?.(lead)}
            >
              <UserPlus className="h-4 w-4" />
            </Button>
          )}
        </div>
      );
    },
  },
];
