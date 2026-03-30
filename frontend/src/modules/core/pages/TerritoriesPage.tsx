import { PageHeader } from '@/design-system/composite/PageHeader/PageHeader';
import { TerritoryTree } from '../components/TerritoryTree';

export function TerritoriesPage() {
  return (
    <div className="space-y-6">
      <PageHeader
        title="Territories Tree"
        description="Deterministic region partitioning for sales and delivery."
      />
      
      <TerritoryTree />
      
    </div>
  );
}
