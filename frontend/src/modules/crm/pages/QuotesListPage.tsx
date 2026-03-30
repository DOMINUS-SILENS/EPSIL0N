import { useState } from 'react';
import { useNavigate } from '@tanstack/react-router';
import { PageHeader } from '@/design-system/composite/PageHeader/PageHeader';
import { Card } from '@/design-system/composite/Card/Card';
import { Button } from '@/design-system/primitives/Button/Button';
import { Badge } from '@/design-system/primitives/Badge/Badge';
import { DataTable } from '@/design-system/composite/DataTable/DataTable';
import { useQuotes, useAcceptQuote, useRejectQuote } from '../hooks/useQuotes';
import { formatCurrency, cn } from '@/lib/utils';
import { format } from 'date-fns';
import { FileText, Plus, CheckCircle, XCircle, Eye, Search } from 'lucide-react';
import { Input } from '@/design-system/primitives/Input/Input';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/design-system/primitives/Dialog/Dialog';

const getStateVariant = (state: string): 'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning' => {
  switch (state) {
    case 'accepted':
      return 'success';
    case 'sent':
      return 'secondary';
    case 'draft':
      return 'default';
    case 'rejected':
      return 'destructive';
    case 'expired':
      return 'warning';
    default:
      return 'default';
  }
};

const getStateLabel = (state: string) => {
  return state.charAt(0).toUpperCase() + state.slice(1);
};

export function QuotesListPage() {
  const navigate = useNavigate();
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedQuote, setSelectedQuote] = useState<any>(null);
  const [showRejectDialog, setShowRejectDialog] = useState(false);
  const [rejectReason, setRejectReason] = useState('');

  const { data: quotes, isLoading } = useQuotes({
    page: 1,
    per_page: 50,
  });

  const acceptQuote = useAcceptQuote();
  const rejectQuote = useRejectQuote();

  const filteredQuotes = quotes?.data.filter((quote) =>
    quote.reference.toLowerCase().includes(searchTerm.toLowerCase()) ||
    quote.customer_name.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const handleView = (quote: any) => {
    navigate({ to: `/crm/quotes/${quote.id}` });
  };

  const handleAccept = (quote: any) => {
    acceptQuote.mutate(quote.id);
  };

  const handleReject = (quote: any) => {
    setSelectedQuote(quote);
    setShowRejectDialog(true);
  };

  const confirmReject = () => {
    if (selectedQuote) {
      rejectQuote.mutate({ id: selectedQuote.id, reason: rejectReason });
    }
    setShowRejectDialog(false);
    setRejectReason('');
    setSelectedQuote(null);
  };

  const columns = [
    {
      accessorKey: 'reference',
      header: 'Reference',
      cell: ({ row }: any) => (
        <div className="flex items-center gap-2">
          <FileText className="w-4 h-4 text-neutral-400" />
          <span className="font-medium">{row.original.reference}</span>
        </div>
      ),
    },
    {
      accessorKey: 'customer_name',
      header: 'Customer',
    },
    {
      accessorKey: 'state',
      header: 'Status',
      cell: ({ row }: any) => (
        <Badge variant={getStateVariant(row.original.state)}>
          {getStateLabel(row.original.state)}
        </Badge>
      ),
    },
    {
      accessorKey: 'total',
      header: 'Total',
      cell: ({ row }: any) => formatCurrency(row.original.total),
    },
    {
      accessorKey: 'expiry_date',
      header: 'Expires',
      cell: ({ row }: any) => {
        const isExpired = new Date(row.original.expiry_date) < new Date();
        return (
          <span className={cn(isExpired && row.original.state !== 'expired' && 'text-red-500')}>
            {format(new Date(row.original.expiry_date), 'MMM dd, yyyy')}
          </span>
        );
      },
    },
    {
      id: 'actions',
      header: 'Actions',
      cell: ({ row }: any) => {
        const quote = row.original;
        return (
          <div className="flex gap-1">
            <Button variant="ghost" size="sm" onClick={() => handleView(quote)}>
              <Eye className="w-4 h-4" />
            </Button>
            {quote.state === 'sent' && (
              <>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => handleAccept(quote)}
                  disabled={acceptQuote.isPending}
                  className="text-green-600 hover:text-green-700 hover:bg-green-50"
                >
                  <CheckCircle className="w-4 h-4" />
                </Button>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => handleReject(quote)}
                  disabled={rejectQuote.isPending}
                  className="text-red-600 hover:text-red-700 hover:bg-red-50"
                >
                  <XCircle className="w-4 h-4" />
                </Button>
              </>
            )}
          </div>
        );
      },
    },
  ];

  return (
    <div className="space-y-6">
      <PageHeader
        title="Quotes"
        description="Manage quotes and convert to orders"
        actions={
          <Button onClick={() => navigate({ to: '/crm/quotes/new' })}>
            <Plus className="mr-2 h-4 w-4" />
            New Quote
          </Button>
        }
      />

      <Card>
        <div className="mb-4 flex items-center gap-4">
          <div className="relative flex-1 max-w-sm">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400" />
            <Input
              placeholder="Search quotes..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="pl-10"
            />
          </div>
        </div>

        <DataTable
          data={filteredQuotes || []}
          columns={columns}
          isLoading={isLoading}
          enableSorting
          enablePagination
        />
      </Card>

      {/* Reject Dialog */}
      <Dialog open={showRejectDialog} onOpenChange={setShowRejectDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Reject Quote</DialogTitle>
            <DialogDescription>
              Are you sure you want to reject {selectedQuote?.reference}? This action cannot be undone.
            </DialogDescription>
          </DialogHeader>
          <div className="py-4">
            <label className="text-sm font-medium">Reason (optional)</label>
            <Input
              value={rejectReason}
              onChange={(e) => setRejectReason(e.target.value)}
              placeholder="Enter rejection reason..."
              className="mt-2"
            />
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setShowRejectDialog(false)}>
              Cancel
            </Button>
            <Button variant="destructive" onClick={confirmReject} disabled={rejectQuote.isPending}>
              Reject Quote
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
