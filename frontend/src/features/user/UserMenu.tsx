import { UserCircle, LogOut } from 'lucide-react';
import { Button } from '@/design-system/primitives/Button/Button';
import { useAuth } from '@/core/auth/useAuth';
import * as DropdownMenu from '@radix-ui/react-dropdown-menu';

export function UserMenu() {
  const { user, logout } = useAuth();

  return (
    <DropdownMenu.Root>
      <DropdownMenu.Trigger asChild>
        <Button variant="ghost" size="icon">
          <UserCircle className="h-5 w-5" />
        </Button>
      </DropdownMenu.Trigger>
      <DropdownMenu.Portal>
        <DropdownMenu.Content className="bg-white dark:bg-neutral-900 rounded-md shadow-lg border border-neutral-200 dark:border-neutral-800 p-1 w-48">
          <div className="px-2 py-1.5 text-sm font-medium">{user?.name || 'User'}</div>
          <DropdownMenu.Separator className="h-px bg-neutral-200 dark:bg-neutral-800 my-1" />
          <DropdownMenu.Item onClick={() => logout()} className="text-error cursor-pointer px-2 py-1.5 text-sm outline-none hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded-sm">
            <LogOut className="inline mr-2 h-4 w-4" />
            Logout
          </DropdownMenu.Item>
        </DropdownMenu.Content>
      </DropdownMenu.Portal>
    </DropdownMenu.Root>
  );
}
