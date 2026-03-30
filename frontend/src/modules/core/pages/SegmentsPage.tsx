import { PageHeader } from '@/design-system/composite/PageHeader/PageHeader';
import { Button } from '@/design-system/primitives/Button/Button';
import { toast } from 'sonner';

export function SegmentsPage() {
  const dispatchCommand = () => {
    toast.success('Dispatched CreateSegmentCommand (Dexie queued)');
  };

  return (
    <div className="space-y-6">
      <PageHeader
        title="Customer Segments"
        description="Manage strict classification segments with simple command CRUD."
        actions={
          <Button onClick={dispatchCommand}>Create Segment</Button>
        }
      />
      <div className="p-12 text-center text-sm text-neutral-500 border border-dashed rounded-lg border-neutral-300 dark:border-neutral-700">
        Segment List DataTable (CQRS only)
      </div>
    </div>
  );
}
