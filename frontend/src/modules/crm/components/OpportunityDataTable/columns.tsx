import { ColumnDef } from '@tanstack/react-table';
import { Opportunity } from '../../api/opportunitiesApi';
import { Badge } from '@/design-system/primitives/Badge/Badge';
import { Button } from '@/design-system/primitives/Button/Button';
import { Eye, Edit, Trophy, XCircle } from 'lucide-react';
import { format } from 'date-fns';

const getStageVariant = (stage: string) => {
  switch (stage) {
    case 'prospecting':
      return 'default';
    case 'qualification':
      return 'secondary';
    case 'proposal':
      return 'outline';
    case 'negotiation':
      return 'secondary';
    case 'closed_won':
      return 'default';
    case 'closed_lost':
      return 'destructive';
    default:
      return 'default';
  }
};

const getStageLabel = (stage: string) => {
  switch (stage) {
    case 'prospecting':
      return 'Prospecting';
    case 'qualification':
      return 'Qualification';
    case 'proposal':
      return 'Proposal';
    case 'negotiation':
      return 'Negotiation';
    case 'closed_won':
      return 'Won';
    case 'closed_lost':
      return 'Lost';
    default:
      return stage;
  }
};

export const columns: ColumnDef<Opportunity>[] = [
  {
    accessorKey: 'reference',
    header: 'Reference',
    size: 120,
  },
  {
    accessorKey: 'title',
    header: 'Title',
    size: 200,
  },
  {
    accessorKey: 'lead_name',
    header: 'Lead',
    size: 150,
  },
  {
    accessorKey: 'value',
    header: 'Value',
    size: 120,
    cell: ({ row }) => {
      const value = row.getValue('value') as number;
      const currency = row.original.currency;
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
      }).format(value);
    },
  },
  {
    accessorKey: 'probability',
    header: 'Probability',
    size: 100,
    cell: ({ row }) => {
      const probability = row.getValue('probability') as number;
      return `${probability}%`;
    },
  },
  {
    accessorKey: 'stage',
    header: 'Stage',
    size: 120,
    cell: ({ row }) => {
      const stage = row.getValue('stage') as string;
      return (
        <Badge variant={getStageVariant(stage)}>
          {getStageLabel(stage)}
        </Badge>
      );
    },
  },
  {
    accessorKey: 'expected_close_date',
    header: 'Expected Close',
    size: 120,
    cell: ({ row }) => {
      const date = row.getValue('expected_close_date') as string;
      return format(new Date(date), 'MMM dd, yyyy');
    },
  },
  {
    id: 'actions',
    header: 'Actions',
    size: 120,
    cell: ({ row, table }) => {
      const opportunity = row.original;
      const { meta } = table.options;

      return (
        <div className="flex items-center gap-2">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => meta?.onView?.(opportunity)}
          >
            <Eye className="h-4 w-4" />
          </Button>
          <Button
            variant="ghost"
            size="sm"
            onClick={() => meta?.onEdit?.(opportunity)}
          >
            <Edit className="h-4 w-4" />
          </Button>
          {opportunity.stage === 'negotiation' && (
            <>
              <Button
                variant="ghost"
                size="sm"
                onClick={() => meta?.onWin?.(opportunity)}
              >
                <Trophy className="h-4 w-4" />
              </Button>
              <Button
                variant="ghost"
                size="sm"
                onClick={() => meta?.onLose?.(opportunity)}
              >
                <XCircle className="h-4 w-4" />
              </Button>
            </>
          )}
        </div>
      );
    },
  },
];
