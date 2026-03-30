import { useParams, useNavigate } from '@tanstack/react-router';
import { PageHeader } from '@/design-system/composite/PageHeader/PageHeader';
import { Button } from '@/design-system/primitives/Button/Button';
import { useUser, useChangeUserRole, useAssignUserTerritory } from '../hooks/useCore';
import { ArrowLeft, User as UserIcon, Shield, MapPin } from 'lucide-react';
import { Badge } from '@/design-system/primitives/Badge';

export function UserDetailPage() {
  const { id } = useParams({ strict: false });
  const navigate = useNavigate();
  const { data: user, isLoading } = useUser(id as string);
  
  const roleMutation = useChangeUserRole();
  const terrMutation = useAssignUserTerritory();

  if (isLoading) return <div className="p-8 text-neutral-500">Loading user profile...</div>;
  if (!user) return <div className="p-8 text-red-500">User not found</div>;

  const promoteToAdmin = () => {
    roleMutation.mutate({ userId: user.id, cmd: { role_id: 'Admin' } });
  };
  
  const assignGlobalTerritory = () => {
    terrMutation.mutate({ userId: user.id, cmd: { territory_id: 'GLOBAL_HQ' } });
  };

  return (
    <div className="space-y-6 max-w-4xl mx-auto">
      <Button variant="ghost" className="mb-4" onClick={() => navigate({ to: '/core/users' })}>
        <ArrowLeft className="w-4 h-4 mr-2" /> Back to Users
      </Button>

      <PageHeader 
        title="User Profile 360"
        description="Command-driven detail view. No PATCH arrays; exact mutations only."
      />

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="col-span-1 border border-neutral-200 dark:border-neutral-800 rounded-lg p-6 bg-white dark:bg-neutral-900 shadow-sm flex flex-col items-center text-center">
          <div className="w-24 h-24 bg-neutral-100 dark:bg-neutral-800 rounded-full flex items-center justify-center mb-4">
            <UserIcon className="w-10 h-10 text-neutral-400" />
          </div>
          <h2 className="text-xl font-semibold mb-1">{user.name}</h2>
          <p className="text-neutral-500 text-sm mb-4">{user.email}</p>
          <Badge variant={user.active ? 'success' : 'destructive'} className="uppercase">
            {user.active ? 'Active Account' : 'Deactivated'}
          </Badge>
        </div>

        <div className="col-span-2 space-y-6">
          <div className="border border-neutral-200 dark:border-neutral-800 rounded-lg p-6 bg-white dark:bg-neutral-900 shadow-sm">
            <h3 className="text-lg font-medium flex items-center gap-2 mb-4">
              <Shield className="w-5 h-5 text-primary" />
              Role & Security
            </h3>
            <div className="flex items-center justify-between p-4 bg-neutral-50 dark:bg-neutral-800/50 rounded-md">
              <div>
                <p className="text-sm font-medium text-neutral-900 dark:text-neutral-100 mb-1">Current Role</p>
                <p className="text-sm text-neutral-500">{user.role_id || 'Unassigned'}</p>
              </div>
              <Button size="sm" variant="outline" onClick={promoteToAdmin} disabled={roleMutation.isPending}>
                Dispatch Make Admin
              </Button>
            </div>
          </div>

          <div className="border border-neutral-200 dark:border-neutral-800 rounded-lg p-6 bg-white dark:bg-neutral-900 shadow-sm">
            <h3 className="text-lg font-medium flex items-center gap-2 mb-4">
              <MapPin className="w-5 h-5 text-accent" />
              Functional Boundary (Territory)
            </h3>
            <div className="flex items-center justify-between p-4 bg-neutral-50 dark:bg-neutral-800/50 rounded-md">
              <div>
                <p className="text-sm font-medium text-neutral-900 dark:text-neutral-100 mb-1">Assigned Node</p>
                <p className="text-sm text-neutral-500">{user.territory_id || 'System Global'}</p>
              </div>
              <Button size="sm" variant="outline" onClick={assignGlobalTerritory} disabled={terrMutation.isPending}>
                Assign GlobalHQ
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
