import { useState, type FormEvent } from 'react';
import { PageHeader } from '@/design-system/composite/PageHeader/PageHeader';
import { Button } from '@/design-system/primitives/Button/Button';
import { useCreateUser, useRoles, useTerritoryTree } from '../hooks/useCore';
import { useNavigate } from '@tanstack/react-router';
import { toast } from 'sonner';

export function UsersCreatePage() {
  const navigate = useNavigate();
  const { mutate: createUser, isPending } = useCreateUser();
  const { data: roles } = useRoles();
  const { data: territories } = useTerritoryTree();

  const [formData, setFormData] = useState({
    name: '',
    email: '',
    role_id: '',
    territory_id: '',
  });

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    if (!formData.name || !formData.email) {
      toast.error('Name and Email are required to dispatch CreateUserCommand');
      return;
    }

    createUser({
      name: formData.name,
      email: formData.email,
      role_id: formData.role_id || null,
      territory_id: formData.territory_id || null,
    }, {
      onSuccess: () => navigate({ to: '/core/users' })
    });
  };

  return (
    <div className="space-y-6 max-w-2xl">
      <PageHeader
        title="Create User Identity"
        description="Emits a CreateUserCommand. Offline-capable dispatcher."
      />

      <form onSubmit={handleSubmit} className="space-y-6 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 p-6 rounded-lg shadow-sm">
        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
              Full Name
            </label>
            <input 
              required
              type="text" 
              className="w-full h-10 px-3 py-2 border border-neutral-300 dark:border-neutral-700 rounded-md bg-transparent"
              value={formData.name}
              onChange={e => setFormData({ ...formData, name: e.target.value })}
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
              Email Address
            </label>
            <input 
              required
              type="email" 
              className="w-full h-10 px-3 py-2 border border-neutral-300 dark:border-neutral-700 rounded-md bg-transparent"
              value={formData.email}
              onChange={e => setFormData({ ...formData, email: e.target.value })}
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                System Role (Pre-assignment)
              </label>
              <select
                className="w-full h-10 px-3 py-2 border border-neutral-300 dark:border-neutral-700 rounded-md bg-transparent"
                value={formData.role_id}
                onChange={e => setFormData({ ...formData, role_id: e.target.value })}
              >
                <option value="">None (No Access)</option>
                {roles?.map(r => (
                  <option key={r.id} value={r.id}>{r.name}</option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                Territorial Boundary
              </label>
              <select
                className="w-full h-10 px-3 py-2 border border-neutral-300 dark:border-neutral-700 rounded-md bg-transparent"
                value={formData.territory_id}
                onChange={e => setFormData({ ...formData, territory_id: e.target.value })}
              >
                <option value="">Global (Full Visibility)</option>
                {/* A proper flat-tree mapper would be here, simplifying for constraints */}
                {territories?.map(t => (
                  <option key={t.id} value={t.id}>{t.name}</option>
                ))}
              </select>
            </div>
          </div>
        </div>

        <div className="flex justify-end gap-3 pt-4 border-t border-neutral-200 dark:border-neutral-800">
          <Button type="button" variant="outline" onClick={() => navigate({ to: '/core/users' })}>
            Cancel
          </Button>
          <Button type="submit" disabled={isPending}>
            {isPending ? 'Queuing Dispatch...' : 'Dispatch CreateUserCommand'}
          </Button>
        </div>
      </form>
    </div>
  );
}
