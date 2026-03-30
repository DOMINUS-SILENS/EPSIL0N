import { ColumnDef } from '@tanstack/react-table';
import { User } from '../api/coreApi';
import { Badge } from '@/design-system/primitives/Badge';
import { Button } from '@/design-system/primitives/Button/Button';
import { Eye } from 'lucide-react';

export const columns: ColumnDef<User>[] = [
  {
    accessorKey: 'name',
    header: 'Name',
  },
  {
    accessorKey: 'email',
    header: 'Email',
  },
  {
    accessorKey: 'role_id',
    header: 'Role',
    meta: { editable: true } as any,
    cell: ({ row }: any) => {
      const roleId = row.getValue('role_id') as string;
      return <Badge variant={roleId ? 'default' : 'outline'}>{roleId || 'None'}</Badge>;
    },
  },
  {
    accessorKey: 'territory_id',
    header: 'Territory Boundary',
    meta: { editable: true } as any,
    cell: ({ row }: any) => {
      const terrId = row.getValue('territory_id') as string;
      return <Badge variant="secondary">{terrId || 'Global'}</Badge>;
    },
  },
  {
    accessorKey: 'active',
    header: 'Status',
    cell: ({ row }: any) => {
      return (
        <Badge variant={row.getValue('active') ? 'success' : 'destructive'}>
          {row.getValue('active') ? 'Active' : 'Deactivated'}
        </Badge>
      );
    },
  },
  {
    id: 'actions',
    cell: ({ row, table }: any) => {
      const user = row.original;
      return (
        <div className="flex justify-end gap-2">
          {/* Explicit command dispatch instead of arbitrary inline edits */}
          <Button
            variant="ghost"
            size="icon"
            onClick={() => (table.options.meta as any)?.onView(user)}
            title="View details & dispatch commands"
          >
            <Eye className="h-4 w-4" />
          </Button>
        </div>
      );
    },
  },
];
