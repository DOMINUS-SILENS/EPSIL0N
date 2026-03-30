import { useParams, useNavigate } from '@tanstack/react-router';
import { PageHeader } from '@/design-system/composite/PageHeader/PageHeader';
import { Card } from '@/design-system/composite/Card/Card';
import { Button } from '@/design-system/primitives/Button/Button';
import { Badge } from '@/design-system/primitives/Badge/Badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/design-system/primitives/Tabs/Tabs';
import { useOpportunity, useUpdateOpportunityStage } from '../hooks/useOpportunities';
import { useQuotes } from '../hooks/useQuotes';
import { useInteractions } from '../hooks/useInteractions';
import { DataTable } from '@/design-system/composite/DataTable/DataTable';
import { ArrowLeft, Edit, FileText, CheckCircle, XCircle, Users, MessageSquare } from 'lucide-react';
import { formatCurrency } from '@/lib/utils';
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
      return 'warning';
    case 'closed_won':
      return 'success';
    case 'closed_lost':
      return 'destructive';
    default:
      return 'default';
  }
};

const getStageLabel = (stage: string) => {
  return stage.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
};

const stageOrder = ['prospecting', 'qualification', 'proposal', 'negotiation', 'closed_won', 'closed_lost'];

export function OpportunityDetailPage() {
  const { id } = useParams({ strict: false } as any);
  const navigate = useNavigate();
  const opportunityId = parseInt(id as string) || 0;

  const { data: opportunity, isLoading: isLoadingOpp } = useOpportunity(opportunityId);
  const { data: quotes } = useQuotes({ opportunity_id: opportunityId });
  const { data: interactions } = useInteractions({ opportunity_id: opportunityId });
  const updateStage = useUpdateOpportunityStage();

  const handleBack = () => {
    navigate({ to: '/crm/opportunities' });
  };

  const handleStageChange = (newStage: string) => {
    updateStage.mutate({ id: opportunityId, stage: newStage });
  };

  const handleCreateQuote = () => {
    navigate({ to: '/crm/quotes/new', search: { opportunity_id: opportunityId } as any });
  };

  if (isLoadingOpp) {
    return <div className="flex items-center justify-center h-64"><div className="animate-spin w-8 h-8 border-2 border-primary border-t-transparent rounded-full" /></div>;
  }

  if (!opportunity) {
    return <div className="text-center py-12 text-neutral-500">Opportunity not found</div>;
  }

  const quoteColumns = [
    { accessorKey: 'reference', header: 'Reference' },
    { accessorKey: 'state', header: 'Status', cell: ({ row }: any) => <Badge>{row.original.state}</Badge> },
    { accessorKey: 'total', header: 'Total', cell: ({ row }: any) => formatCurrency(row.original.total || 0) },
    { accessorKey: 'expiry_date', header: 'Expires', cell: ({ row }: any) => format(new Date(row.original.expiry_date || new Date()), 'MMM dd, yyyy') },
  ];

  const interactionColumns = [
    { accessorKey: 'type', header: 'Type' },
    { accessorKey: 'subject', header: 'Subject' },
    { accessorKey: 'created_at', header: 'Date', cell: ({ row }: any) => format(new Date(row.original.created_at || new Date()), 'MMM dd, yyyy') },
  ];

  return (
    <div className="space-y-6">
      <PageHeader
        title={opportunity.title || opportunity.name || 'Opportunity'}
        description={`${opportunity.customer_name || 'N/A'} · ${formatCurrency(opportunity.expected_revenue ?? opportunity.value ?? 0)} expected`}
        actions={
          <div className="flex gap-2">
            <Button variant="outline" onClick={handleBack}>
              <ArrowLeft className="mr-2 h-4 w-4" />
              Back
            </Button>
            <Button variant="outline">
              <Edit className="mr-2 h-4 w-4" />
              Edit
            </Button>
            {opportunity.stage !== 'closed_won' && opportunity.stage !== 'closed_lost' && (
              <Button onClick={handleCreateQuote}>
                <FileText className="mr-2 h-4 w-4" />
                Create Quote
              </Button>
            )}
          </div>
        }
      />

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Main Content */}
        <div className="lg:col-span-2 space-y-6">
          {/* Stage Progression */}
          <Card title="Stage Progression">
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <span className="text-sm text-neutral-500">Current Stage</span>
                <Badge variant={getStageVariant(opportunity.stage || '') as any} className="text-sm">
                  {getStageLabel(opportunity.stage || '')}
                </Badge>
              </div>

              <div className="space-y-2">
                <p className="text-sm font-medium">Change Stage:</p>
                <div className="flex flex-wrap gap-2">
                  {stageOrder.map((stage) => (
                    <Button
                      key={stage}
                      variant={(opportunity.stage || '') === stage ? 'default' : 'outline'}
                      size="sm"
                      onClick={() => handleStageChange(stage)}
                      disabled={updateStage.isPending}
                    >
                      {stage === 'closed_won' && <CheckCircle className="mr-1 h-4 w-4" />}
                      {stage === 'closed_lost' && <XCircle className="mr-1 h-4 w-4" />}
                      {getStageLabel(stage)}
                    </Button>
                  ))}
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4 pt-4 border-t">
                <div>
                  <span className="text-sm text-neutral-500">Probability</span>
                  <p className="text-2xl font-semibold">{opportunity.probability || 0}%</p>
                </div>
                <div>
                  <span className="text-sm text-neutral-500">Expected Close</span>
                  <p className="text-lg font-medium">{format(new Date(opportunity.expected_close_date || new Date()), 'MMM dd, yyyy')}</p>
                </div>
              </div>
            </div>
          </Card>

          {/* Tabs for Quotes and Interactions */}
          <Tabs defaultValue="quotes">
            <TabsList>
              <TabsTrigger value="quotes">
                <FileText className="mr-2 h-4 w-4" />
                Quotes ({quotes?.data?.length || 0})
              </TabsTrigger>
              <TabsTrigger value="interactions">
                <MessageSquare className="mr-2 h-4 w-4" />
                Interactions ({interactions?.data?.length || 0})
              </TabsTrigger>
            </TabsList>

            <TabsContent value="quotes" className="mt-4">
              <Card>
                {quotes?.data?.length ? (
                  <DataTable data={quotes.data} columns={quoteColumns as any} enablePagination={false} />
                ) : (
                  <div className="text-center py-8 text-neutral-500">
                    <FileText className="w-12 h-12 mx-auto mb-4 opacity-50" />
                    <p>No quotes yet</p>
                    <Button variant="outline" size="sm" className="mt-4" onClick={handleCreateQuote}>
                      Create First Quote
                    </Button>
                  </div>
                )}
              </Card>
            </TabsContent>

            <TabsContent value="interactions" className="mt-4">
              <Card>
                {interactions?.data?.length ? (
                  <DataTable data={interactions.data} columns={interactionColumns as any} enablePagination={false} />
                ) : (
                  <div className="text-center py-8 text-neutral-500">
                    <MessageSquare className="w-12 h-12 mx-auto mb-4 opacity-50" />
                    <p>No interactions recorded</p>
                  </div>
                )}
              </Card>
            </TabsContent>
          </Tabs>
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          <Card title="Opportunity Details">
            <div className="space-y-4">
              <div>
                <span className="text-sm text-neutral-500">Customer</span>
                <p className="font-medium flex items-center gap-2">
                  <Users className="w-4 h-4" />
                  {opportunity.customer_name || 'Unknown'}
                </p>
              </div>
              <div>
                <span className="text-sm text-neutral-500">Assigned To</span>
                <p className="font-medium">{opportunity.assigned_to_name || 'Unassigned'}</p>
              </div>
              <div>
                <span className="text-sm text-neutral-500">Source</span>
                <p className="font-medium">{opportunity.source || 'Unknown'}</p>
              </div>
              <div>
                <span className="text-sm text-neutral-500">Campaign</span>
                <p className="font-medium">{opportunity.campaign || 'None'}</p>
              </div>
            </div>
          </Card>

          <Card title="Financial Summary">
            <div className="space-y-3">
              <div className="flex justify-between">
                <span className="text-sm text-neutral-500">Expected Revenue</span>
                <span className="font-medium">{formatCurrency(opportunity.expected_revenue ?? 0)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm text-neutral-500">Weighted Value</span>
                <span className="font-medium">
                  {formatCurrency(((opportunity.expected_revenue ?? opportunity.value ?? 0) * ((opportunity.probability ?? 0) / 100)))}
                </span>
              </div>
            </div>
          </Card>

          <Card title="Timeline">
            <div className="space-y-3 text-sm">
              <div className="flex justify-between">
                <span className="text-neutral-500">Created</span>
                <span>{format(new Date(opportunity.created_at || new Date()), 'MMM dd, yyyy')}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-neutral-500">Last Updated</span>
                <span>{format(new Date(opportunity.updated_at || new Date()), 'MMM dd, yyyy')}</span>
              </div>
            </div>
          </Card>
        </div>
      </div>
    </div>
  );
}
