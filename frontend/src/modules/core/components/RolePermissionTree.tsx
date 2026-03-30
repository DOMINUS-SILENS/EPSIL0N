import { useState } from 'react';
import { PERMISSIONS } from '@/core/auth/permissions';
import { useUpdateRolePermissions } from '../hooks/useCore';
import { Button } from '@/design-system/primitives/Button/Button';
import { Badge } from '@/design-system/primitives/Badge';
import { Check, ShieldAlert } from 'lucide-react';

interface RolePermissionTreeProps {
  roleId: string;
  initialPermissions: string[];
}

export function RolePermissionTree({ roleId, initialPermissions }: RolePermissionTreeProps) {
  const [selected, setSelected] = useState<Set<string>>(new Set(initialPermissions));
  const { mutate, isPending } = useUpdateRolePermissions();


  const handleSave = () => {
    mutate({
      roleId,
      cmd: { permissions: Array.from(selected) },
    });
  };

  const isChanged = Array.from(selected).sort().join(',') !== [...initialPermissions].sort().join(',');

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between border-b border-neutral-200 pb-4 dark:border-neutral-800">
        <div>
          <h3 className="text-lg font-medium">Static Permission Tree</h3>
          <p className="text-sm text-neutral-500">
            Toggle strict codebase-defined permissions for this role.
          </p>
        </div>
        <Button 
          onClick={handleSave} 
          disabled={!isChanged || isPending}
          className="gap-2"
        >
          {isPending ? 'Dispatching...' : 'Save Role Permissions'}
          {isChanged && <ShieldAlert className="w-4 h-4" />}
        </Button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {Object.entries(PERMISSIONS).map(([moduleName, perms]) => {
          const isAllSelected = perms.every(p => selected.has(p));
          
          const handleSelectAll = (e: React.MouseEvent) => {
            e.preventDefault();
            const next = new Set(selected);
            if (isAllSelected) {
              perms.forEach(p => next.delete(p));
            } else {
              perms.forEach(p => next.add(p));
            }
            setSelected(next);
          };

          return (
          <div key={moduleName} className="p-4 border border-neutral-200 dark:border-neutral-800 rounded-lg bg-neutral-50 dark:bg-neutral-900/50">
            <div className="flex items-center justify-between mb-4">
              <h4 className="font-semibold capitalize text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
                {moduleName} Module
                <Badge variant="outline">{perms.filter(p => selected.has(p)).length}/{perms.length}</Badge>
              </h4>
              <Button size="sm" variant="ghost" className="h-6 text-xs px-2" onClick={handleSelectAll}>
                {isAllSelected ? 'Deselect All' : 'Select All'}
              </Button>
            </div>
            
            <div className="space-y-2">
              {perms.map(perm => {
                const isSelected = selected.has(perm);
                return (
                  <label 
                    key={perm}
                    className="flex items-center gap-3 p-2 rounded hover:bg-white dark:hover:bg-neutral-800 cursor-pointer transition-colors"
                  >
                    <div className={`flex items-center justify-center w-5 h-5 rounded border ${
                      isSelected 
                        ? 'bg-primary border-primary text-primary-foreground' 
                        : 'border-neutral-300 dark:border-neutral-700 bg-transparent'
                    }`}>
                      {isSelected && <Check className="w-3.5 h-3.5" />}
                    </div>
                    <span className="text-sm font-medium text-neutral-700 dark:text-neutral-300">
                      {perm}
                    </span>
                  </label>
                );
              })}
            </div>
          </div>
        )})}
      </div>
    </div>
  );
}
