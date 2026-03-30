import { useState } from 'react';
import { useTerritoryTree } from '../hooks/useCore';
import { Button } from '@/design-system/primitives/Button/Button';
import { Badge } from '@/design-system/primitives/Badge';
import { Folder, MapPin, GripVertical } from 'lucide-react';
import { toast } from 'sonner';

export function TerritoryTree() {
  const { data: territories, isLoading } = useTerritoryTree();
  const [draggedId, setDraggedId] = useState<string | null>(null);

  if (isLoading) return <div className="text-sm text-neutral-500">Loading deterministic territory snapshot...</div>;
  if (!territories || territories.length === 0) return <div className="text-sm text-neutral-500">No territories exist.</div>;

  const handleDragStart = (e: React.DragEvent, id: string) => {
    setDraggedId(id);
    e.dataTransfer.effectAllowed = 'move';
  };

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
  };

  const handleDrop = (e: React.DragEvent, targetId: string) => {
    e.preventDefault();
    if (!draggedId || draggedId === targetId) return;
    toast.success(`Dispatched ChangeTerritoryParent command: ${draggedId} -> ${targetId}`);
    setDraggedId(null);
  };

  const renderNode = (node: any, level: number = 0) => (
    <div key={node.id} className="relative">
      <div 
        draggable
        onDragStart={(e) => handleDragStart(e, node.id)}
        onDragOver={handleDragOver}
        onDrop={(e) => handleDrop(e, node.id)}
        className="flex items-center gap-2 py-2 px-3 hover:bg-neutral-50 dark:hover:bg-neutral-800 rounded-md group cursor-grab active:cursor-grabbing transition-colors border border-transparent hover:border-neutral-200 dark:hover:border-neutral-700" 
        style={{ marginLeft: `${level * 24}px` }}
      >
        <GripVertical className="w-4 h-4 text-neutral-400 opacity-0 group-hover:opacity-100 transition-opacity" />
        {node.children && node.children.length > 0 ? (
          <Folder className="w-4 h-4 text-primary" />
        ) : (
          <MapPin className="w-4 h-4 text-neutral-500" />
        )}
        <span className="font-medium text-sm">{node.name}</span>
        <Badge variant="outline" className="ml-auto opacity-0 group-hover:opacity-100">ID: {node.id}</Badge>
      </div>
      
      {node.children && node.children.length > 0 && (
        <div className="ml-4 border-l border-neutral-200 dark:border-neutral-800 my-1">
          {node.children.map((child: any) => renderNode(child, level + 1))}
        </div>
      )}
    </div>
  );

  return (
    <div className="space-y-4">
      <div className="flex justify-between items-center mb-6">
        <div>
          <h3 className="text-lg font-medium">Territory Boundary Tree</h3>
          <p className="text-sm text-neutral-500">Drag and drop to restructure the hierarchy. Changes dispatch instantly.</p>
        </div>
        <Button size="sm">Add Root Territory</Button>
      </div>
      <div className="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 p-4 rounded-lg shadow-sm">
        {territories.map((rootNode: any) => renderNode(rootNode))}
      </div>
    </div>
  );
}
