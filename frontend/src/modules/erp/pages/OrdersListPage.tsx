import { useState } from 'react';
import { PageHeader } from '@/design-system/composite/PageHeader/PageHeader';
import { DataTable } from '@/design-system/composite/DataTable/DataTable';
import { Button } from '@/design-system/primitives/Button/Button';
import { useOrders, useOrderAction } from '../hooks/useErp';
import { ColumnDef } from '@tanstack/react-table';
import { Order } from '../api/erpApi';
import { Badge } from '@/design-system/primitives/Badge';
import { Plus, CheckCircle, Truck, FileText, Ban } from 'lucide-react';
import { OrderCreateWizard } from '../components/OrderCreateWizard';

const orderColumns: ColumnDef<Order>[] = [
  { accessorKey: 'reference', header: 'Reference', cell: ({ row }) => <span className="font-mono">{row.getValue('reference')}</span> },
  { accessorKey: 'customer_name', header: 'Customer' },
  { accessorKey: 'totalAmount', header: 'Total', cell: ({ row }) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(row.getValue('totalAmount')) },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) => {
      const status = row.getValue('status') as string;
      const variants: Record<string, any> = { draft: 'secondary', confirmed: 'default', delivered: 'warning', invoiced: 'success', cancelled: 'destructive' };
      return <Badge variant={variants[status] || 'default'} className="uppercase text-[10px]">{status}</Badge>;
    }
  },
  {
    id: 'actions',
    cell: ({ row, table }) => {
      const meta = table.options.meta as any;
      const s = row.original.status;
      return (
        <div className="flex justify-end gap-2">
          {s === 'draft' && <Button size="sm" variant="outline" onClick={() => meta?.onAction('confirm', row.original.id)}><CheckCircle className="w-4 h-4 mr-1" /> Confirm</Button>}
          {s === 'confirmed' && <Button size="sm" variant="outline" onClick={() => meta?.onAction('deliver', row.original.id)}><Truck className="w-4 h-4 mr-1" /> Deliver</Button>}
          {s === 'delivered' && <Button size="sm" variant="outline" onClick={() => meta?.onAction('invoice', row.original.id)}><FileText className="w-4 h-4 mr-1" /> Invoice</Button>}
          {['draft', 'confirmed'].includes(s) && <Button size="sm" variant="outline" className="text-red-500 border-red-200" onClick={() => meta?.onAction('cancel', row.original.id)}><Ban className="w-4 h-4 mr-1" /> Cancel</Button>}
        </div>
      );
    }
  }
];
export function OrdersListPage() {
  const [page] = useState(1);
  const [pageSize] = useState(50);
  const [wizardOpen, setWizardOpen] = useState(false);
  const { data, isLoading } = useOrders({ page, per_page: pageSize });

  // Separate hooks per action
  const confirmObj = useOrderAction('confirm');
  const deliverObj = useOrderAction('deliver');
  const invoiceObj = useOrderAction('invoice');
  const cancelObj = useOrderAction('cancel');

  const actionMap = {
    confirm: confirmObj.mutate,
    deliver: deliverObj.mutate,
    invoice: invoiceObj.mutate,
    cancel: cancelObj.mutate,
  };

  const handleAction = (action: 'confirm' | 'deliver' | 'invoice' | 'cancel', id: number) => {
    actionMap[action](id);
  };

  return (
    <div className="space-y-6">
      <PageHeader
        title="Sales Orders"
        description="Manage the order lifecycle via explicit CQRS commands (Confirm, Deliver, Invoice)."
        actions={
          <Button onClick={() => setWizardOpen(true)}><Plus className="w-4 h-4 mr-2" /> Wizard: Create Order</Button>
        }
      />

      {wizardOpen && <OrderCreateWizard onClose={() => setWizardOpen(false)} />}

      <DataTable
        data={data?.data || []}
        columns={orderColumns}
        isLoading={isLoading}
        enableSorting
        enablePagination
        pageSize={pageSize}
        pageCount={data?.meta?.last_page || 1}
        meta={{ onAction: handleAction }}
      />
    </div>
  );
}
