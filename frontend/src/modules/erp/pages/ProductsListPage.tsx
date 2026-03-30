import { useState } from 'react';
import { DataTable } from '@/design-system/composite/DataTable/DataTable';
import { PageHeader } from '@/design-system/composite/PageHeader/PageHeader';
import { useProducts, useUpdateProductPrice } from '../hooks/useErp';
import { columns } from './ProductsListColumns';
import { Product } from '../api/erpApi';
import { toast } from 'sonner';

export function ProductsListPage() {
  const [page] = useState(1);
  const [pageSize] = useState(50);

  const { data, isLoading } = useProducts({ page, per_page: pageSize });
  const updatePrice = useUpdateProductPrice();

  const handleCellEdit = (rowId: string, columnId: string, value: string) => {
    if (columnId === 'price') {
      const parsed = parseFloat(value);
      if (isNaN(parsed) || parsed < 0) {
        toast.error('Invalid price value. Must be a positive number.');
        return;
      }
      updatePrice.mutate({
        id: parseInt(rowId),
        cmd: { price: parsed }
      });
    }
  };

  const handleView = (product: Product) => {
    toast.info(`View details for product ${product.sku} coming soon...`);
  };

  return (
    <div className="space-y-6">
      <PageHeader
        title="Products Catalog"
        description="Manage master catalog. Price edits map directly to UpdateProductPrice commands."
      />

      <DataTable
        data={data?.data || []}
        columns={columns}
        isLoading={isLoading}
        enableSorting
        enableFiltering
        enablePagination
        enableInlineEditing
        onCellEdit={handleCellEdit}
        pageSize={pageSize}
        pageCount={data?.meta?.last_page || 1}
        meta={{
          onView: handleView,
        }}
      />
    </div>
  );
}
