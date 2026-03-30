import { ColumnDef } from '@tanstack/react-table';
import { Product } from '../../api/productsApi';
import { Badge } from '@/design-system/primitives/Badge/Badge';
import { Button } from '@/design-system/primitives/Button/Button';
import { Eye, Edit, Package, TrendingUp, TrendingDown } from 'lucide-react';

export const columns: ColumnDef<Product>[] = [
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
    accessorKey: 'sku',
    header: 'SKU',
    size: 120,
  },
  {
    accessorKey: 'barcode',
    header: 'Barcode',
    size: 120,
  },
  {
    accessorKey: 'purchase_price',
    header: 'Purchase Price',
    size: 120,
    cell: ({ row }) => {
      const price = row.getValue('purchase_price') as number;
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
      }).format(price);
    },
  },
  {
    accessorKey: 'sale_price',
    header: 'Sale Price',
    size: 120,
    cell: ({ row }) => {
      const price = row.getValue('sale_price') as number;
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
      }).format(price);
    },
  },
  {
    accessorKey: 'stock_qty',
    header: 'Stock',
    size: 80,
    cell: ({ row }) => {
      const stock = row.getValue('stock_qty') as number;
      const isLow = stock < 10;
      return (
        <div className={`flex items-center ${isLow ? 'text-red-600' : 'text-green-600'}`}>
          <Package className="mr-1 h-4 w-4" />
          {stock}
          {isLow && <TrendingDown className="ml-1 h-3 w-3" />}
        </div>
      );
    },
  },
  {
    accessorKey: 'active',
    header: 'Status',
    size: 80,
    cell: ({ row }) => {
      const active = row.getValue('active') as boolean;
      return (
        <Badge variant={active ? 'default' : 'secondary'}>
          {active ? 'Active' : 'Inactive'}
        </Badge>
      );
    },
  },
  {
    id: 'margin',
    header: 'Margin',
    size: 100,
    cell: ({ row }) => {
      const purchasePrice = row.original.purchase_price;
      const salePrice = row.original.sale_price;
      const margin = ((salePrice - purchasePrice) / purchasePrice) * 100;
      return (
        <div className={`flex items-center ${margin > 20 ? 'text-green-600' : 'text-orange-600'}`}>
          {margin.toFixed(1)}%
          {margin > 20 && <TrendingUp className="ml-1 h-3 w-3" />}
        </div>
      );
    },
  },
  {
    id: 'actions',
    header: 'Actions',
    size: 120,
    cell: ({ row, table }) => {
      const product = row.original;
      const { meta } = table.options;

      return (
        <div className="flex items-center gap-2">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => meta?.onView?.(product)}
          >
            <Eye className="h-4 w-4" />
          </Button>
          <Button
            variant="ghost"
            size="sm"
            onClick={() => meta?.onEdit?.(product)}
          >
            <Edit className="h-4 w-4" />
          </Button>
          <Button
            variant="ghost"
            size="sm"
            onClick={() => meta?.onViewStock?.(product)}
          >
            <Package className="h-4 w-4" />
          </Button>
        </div>
      );
    },
  },
];
