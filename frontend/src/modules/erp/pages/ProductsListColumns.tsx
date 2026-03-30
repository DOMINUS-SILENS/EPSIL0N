import { ColumnDef } from '@tanstack/react-table';
import { Product } from '../api/erpApi';
import { Badge } from '@/design-system/primitives/Badge';
import { ImageIcon } from 'lucide-react';
import { Button } from '@/design-system/primitives/Button/Button';

export const columns: ColumnDef<Product>[] = [
  {
    accessorKey: 'image_url',
    header: 'Image',
    cell: (info: any) => {
      const { row } = info;
      const url = row.getValue('image_url') as string | undefined;
      return url ? (
        <img src={url} alt={row.original.name} className="w-10 h-10 rounded-md object-cover" />
      ) : (
        <div className="w-10 h-10 rounded-md bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center">
          <ImageIcon className="w-4 h-4 text-neutral-400" />
        </div>
      );
    },
  },
  {
    accessorKey: 'sku',
    header: 'SKU',
    cell: ({ row }: any) => <span className="font-mono text-xs">{row.getValue('sku')}</span>,
  },
  {
    accessorKey: 'name',
    header: 'Product Name',
    cell: ({ row }: any) => <span className="font-medium">{row.getValue('name')}</span>,
  },
  {
    accessorKey: 'stock',
    header: 'Available Stock',
    cell: ({ row }: any) => {
      const stock = row.getValue('stock') as number;
      return (
        <Badge variant={stock > 10 ? 'default' : stock > 0 ? 'warning' : 'destructive'}>
          {stock} units
        </Badge>
      );
    },
  },
  {
    accessorKey: 'price',
    header: 'Unit Price',
    meta: {
      editable: true,
      type: 'number'
    } as any,
    cell: ({ row }: any) => {
      const price = parseFloat(row.getValue('price') as string);
      return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price);
    },
  },
  {
    id: 'actions',
    cell: (info: any) => {
      const { row, table } = info;
      const meta = table.options.meta as any;
      return (
        <div className="flex justify-end gap-2">
          <Button variant="ghost" size="sm" onClick={() => meta?.onView?.(row.original)}>
            View details
          </Button>
        </div>
      );
    },
  },
];
