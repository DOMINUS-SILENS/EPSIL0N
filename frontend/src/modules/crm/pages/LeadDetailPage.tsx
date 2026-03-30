import { useParams, useNavigate } from '@tanstack/react-router';
import { PageHeader } from '@/design-system/composite/PageHeader/PageHeader';
import { Card } from '@/design-system/composite/Card/Card';
import { Button } from '@/design-system/primitives/Button/Button';
import { Badge } from '@/design-system/primitives/Badge/Badge';
import { useLead, useUpdateLead, useConvertLead } from '../hooks/useLeads';
import { ArrowLeft, Edit, UserPlus, Mail, Phone, Building } from 'lucide-react';
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

export function LeadDetailPage() {
  const { id } = useParams({ strict: false });
  const navigate = useNavigate();
  const { data: lead, isLoading } = useLead(parseInt(id as string));
  const updateLead = useUpdateLead();
  const convertLead = useConvertLead();

  const handleBack = () => {
    navigate({ to: '/crm/leads' });
  };

  const handleEdit = () => {
    navigate({ to: `/crm/leads/${id}/edit` });
  };

  const handleConvert = () => {
    if (lead) {
      convertLead.mutate(lead.id);
    }
  };

  const handleStatusChange = (newState: string) => {
    if (lead) {
      updateLead.mutate({
        id: lead.id,
        data: { state: newState },
      });
    }
  };

  if (isLoading) {
    return <div>Loading...</div>;
  }

  if (!lead) {
    return <div>Lead not found</div>;
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title={`${lead.first_name} ${lead.last_name}`}
        description={lead.reference}
        actions={
          <div className="flex gap-2">
            <Button variant="outline" onClick={handleBack}>
              <ArrowLeft className="mr-2 h-4 w-4" />
              Back
            </Button>
            <Button variant="outline" onClick={handleEdit}>
              <Edit className="mr-2 h-4 w-4" />
              Edit
            </Button>
            {lead.state === 'qualified' && (
              <Button onClick={handleConvert}>
                <UserPlus className="mr-2 h-4 w-4" />
                Convert to Customer
              </Button>
            )}
          </div>
        }
      />

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 space-y-6">
          <Card title="Lead Information">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="text-sm font-medium text-gray-500">Reference</label>
                <p className="mt-1">{lead.reference}</p>
              </div>
              <div>
                <label className="text-sm font-medium text-gray-500">Status</label>
                <div className="mt-1">
                  <Badge variant={getStateVariant(lead.state)}>
                    {getStateLabel(lead.state)}
                  </Badge>
                </div>
              </div>
              <div>
                <label className="text-sm font-medium text-gray-500">First Name</label>
                <p className="mt-1">{lead.first_name}</p>
              </div>
              <div>
                <label className="text-sm font-medium text-gray-500">Last Name</label>
                <p className="mt-1">{lead.last_name}</p>
              </div>
              <div className="col-span-2">
                <label className="text-sm font-medium text-gray-500">Email</label>
                <p className="mt-1 flex items-center">
                  <Mail className="mr-2 h-4 w-4" />
                  {lead.email}
                </p>
              </div>
              {lead.phone && (
                <div className="col-span-2">
                  <label className="text-sm font-medium text-gray-500">Phone</label>
                  <p className="mt-1 flex items-center">
                    <Phone className="mr-2 h-4 w-4" />
                    {lead.phone}
                  </p>
                </div>
              )}
              {lead.source && (
                <div>
                  <label className="text-sm font-medium text-gray-500">Source</label>
                  <p className="mt-1 flex items-center">
                    <Building className="mr-2 h-4 w-4" />
                    {lead.source}
                  </p>
                </div>
              )}
            </div>
          </Card>

          <Card title="Status Management">
            <div className="space-y-3">
              <p className="text-sm text-gray-600">Change lead status:</p>
              <div className="flex gap-2">
                {['new', 'contacted', 'qualified', 'lost'].map((state) => (
                  <Button
                    key={state}
                    variant={lead.state === state ? 'default' : 'outline'}
                    size="sm"
                    onClick={() => handleStatusChange(state)}
                  >
                    {getStateLabel(state)}
                  </Button>
                ))}
              </div>
            </div>
          </Card>
        </div>

        <div className="space-y-6">
          <Card title="Timeline">
            <div className="space-y-4">
              <div className="flex justify-between text-sm">
                <span className="text-gray-500">Created</span>
                <span>{format(new Date(lead.created_at), 'MMM dd, yyyy')}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-gray-500">Last Updated</span>
                <span>{format(new Date(lead.updated_at), 'MMM dd, yyyy')}</span>
              </div>
            </div>
          </Card>

          {lead.assigned_to && (
            <Card title="Assigned To">
              <div className="text-sm">
                User ID: {lead.assigned_to}
              </div>
            </Card>
          )}
        </div>
      </div>
    </div>
  );
}
