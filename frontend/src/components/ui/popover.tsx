import { Bell } from 'lucide-react';
import { Button } from '@/design-system/primitives/Button/Button';

export function NotificationsDropdown() {
  return (
    <Button variant="ghost" size="icon">
      <Bell className="h-5 w-5" />
    </Button>
  );
}
