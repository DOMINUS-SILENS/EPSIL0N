import * as Dialog from '@radix-ui/react-dialog';
import { useUIStore } from '@/core/state/stores/authStore';
import { X } from 'lucide-react';

export function CommandPalette() {
  const { commandPaletteOpen, setCommandPaletteOpen } = useUIStore();

  return (
    <Dialog.Root open={commandPaletteOpen} onOpenChange={setCommandPaletteOpen}>
      <Dialog.Portal>
        <Dialog.Overlay className="fixed inset-0 bg-black/50" />
        <Dialog.Content className="fixed top-[20%] left-1/2 -translate-x-1/2 w-full max-w-lg bg-white dark:bg-neutral-900 rounded-lg shadow-lg focus:outline-none">
          <div className="flex items-center justify-between p-4 border-b border-neutral-200 dark:border-neutral-800">
            <Dialog.Title className="text-sm font-medium">Command Palette</Dialog.Title>
            <Dialog.Close asChild>
              <button className="rounded-sm opacity-70 hover:opacity-100">
                <X className="h-4 w-4" />
              </button>
            </Dialog.Close>
          </div>
          <input
            type="text"
            placeholder="Type a command or search..."
            className="w-full p-4 text-sm outline-none bg-transparent"
            autoFocus
          />
          <div className="p-2 text-xs text-neutral-400">No results</div>
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
}
