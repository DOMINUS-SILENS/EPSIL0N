import { Lead } from '@/modules/crm/api/leadsApi';
import { Badge } from '@/design-system/primitives/Badge';
import { Button } from '@/design-system/primitives/Button/Button';
import { UserPlus, Eye, MapPin } from 'lucide-react';
import { format } from 'date-fns';

interface LeadKanbanBoardProps {
  leads: Lead[];
  onView: (lead: Lead) => void;
  onConvert: (lead: Lead) => void;
}

const STAGES = [
  { id: 'new', label: 'New Leads', color: 'border-blue-200 bg-blue-50/50 dark:border-blue-900/50 dark:bg-blue-900/20' },
  { id: 'contacted', label: 'Contacted', color: 'border-amber-200 bg-amber-50/50 dark:border-amber-900/50 dark:bg-amber-900/20' },
  { id: 'qualified', label: 'Qualified', color: 'border-green-200 bg-green-50/50 dark:border-green-900/50 dark:bg-green-900/20' },
  { id: 'lost', label: 'Lost', color: 'border-red-200 bg-red-50/50 dark:border-red-900/50 dark:bg-red-900/20' }
];

export function LeadKanbanBoard({ leads, onView, onConvert }: LeadKanbanBoardProps) {
  // We mock HTML5 drag-and-drop or just use mapping for visual layout
  // Production would use dnd-kit or similar
  
  return (
    <div className="flex gap-4 overflow-x-auto pb-4 h-[calc(100vh-220px)]">
      {STAGES.map(stage => {
        const stageLeads = leads.filter(l => l.state === stage.id);
        
        return (
          <div key={stage.id} className={`flex-shrink-0 w-80 rounded-xl border ${stage.color} flex flex-col`}>
            <div className="p-3 border-b border-black/5 dark:border-white/5 flex justify-between items-center mb-2">
              <h3 className="font-semibold text-sm">{stage.label}</h3>
              <Badge variant="secondary" className="px-1.5 min-w-[20px] justify-center">{stageLeads.length}</Badge>
            </div>
            
            <div className="p-3 flex-1 overflow-y-auto space-y-3">
              {stageLeads.map(lead => (
                <div 
                  key={lead.id} 
                  className="bg-white dark:bg-neutral-900 p-3 rounded-lg border border-neutral-200 dark:border-neutral-800 shadow-sm hover:border-primary/40 transition-colors group cursor-grab active:cursor-grabbing"
                >
                  <div className="flex justify-between items-start mb-2">
                    <span className="font-medium text-sm truncate">{lead.first_name} {lead.last_name}</span>
                    <Button variant="ghost" size="icon" className="h-6 w-6 opacity-0 group-hover:opacity-100" onClick={() => onView(lead)}>
                      <Eye className="w-3.5 h-3.5 text-neutral-500" />
                    </Button>
                  </div>
                  
                  <div className="text-xs text-neutral-500 mb-3 space-y-1">
                    <p>{lead.email}</p>
                    <p className="flex items-center gap-1"><MapPin className="w-3 h-3"/> {(lead as any).territory_id || 'Unassigned Territory'}</p>
                  </div>
                  
                  <div className="flex items-center justify-between mt-auto pt-2 border-t border-neutral-100 dark:border-neutral-800">
                    <div className="text-[10px] text-neutral-400">
                      {lead.created_at ? format(new Date(lead.created_at), 'MMM d') : 'No date'}
                    </div>
                    {lead.state === 'qualified' && (
                      <Button size="sm" variant="outline" className="h-6 text-[10px] px-2" onClick={() => onConvert(lead)}>
                        <UserPlus className="w-3 h-3 mr-1" /> Convert
                      </Button>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>
        );
      })}
    </div>
  );
}
