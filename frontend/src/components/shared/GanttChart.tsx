import { format, addMinutes, differenceInMinutes } from 'date-fns';
import { useState } from 'react';
import { cn } from '@/lib/utils';

export interface GanttTask {
  id: string;
  title: string;
  startTime: Date;
  endTime: Date;
  status: 'pending' | 'in_progress' | 'completed' | 'failed';
  type?: 'stop' | 'delivery' | 'visit' | 'break';
  resourceId?: string;
  resourceName?: string;
  details?: string;
}

export interface GanttResource {
  id: string;
  name: string;
  color?: string;
}

interface GanttChartProps {
  tasks: GanttTask[];
  resources?: GanttResource[];
  startHour?: number;
  endHour?: number;
  rowHeight?: number;
  timeSlotWidth?: number;
  onTaskClick?: (taskId: string) => void;
  onTaskUpdate?: (taskId: string, newStart: Date, newEnd: Date) => void;
  className?: string;
}

const statusColors: Record<string, string> = {
  pending: 'bg-gray-200 border-gray-300',
  in_progress: 'bg-blue-200 border-blue-400',
  completed: 'bg-green-200 border-green-400',
  failed: 'bg-red-200 border-red-400',
};

const typeColors: Record<string, string> = {
  stop: 'border-l-4 border-l-orange-500',
  delivery: 'border-l-4 border-l-blue-500',
  visit: 'border-l-4 border-l-purple-500',
  break: 'border-l-4 border-l-gray-500',
};

export function GanttChart({
  tasks,
  resources = [],
  startHour = 8,
  endHour = 18,
  rowHeight = 60,
  timeSlotWidth = 80,
  onTaskClick,
  onTaskUpdate,
  className = '',
}: GanttChartProps) {
  const [draggedTask, setDraggedTask] = useState<string | null>(null);
  
  const totalMinutes = (endHour - startHour) * 60;
  const totalWidth = (totalMinutes / 30) * timeSlotWidth;

  const hours = Array.from({ length: endHour - startHour + 1 }, (_, i) => startHour + i);

  const getTaskPosition = (task: GanttTask) => {
    const startMinutes = task.startTime.getHours() * 60 + task.startTime.getMinutes() - startHour * 60;
    const duration = differenceInMinutes(task.endTime, task.startTime);
    
    return {
      left: (startMinutes / 30) * timeSlotWidth,
      width: (duration / 30) * timeSlotWidth,
    };
  };

  const handleTaskDragStart = (taskId: string) => {
    if (onTaskUpdate) {
      setDraggedTask(taskId);
    }
  };

  const handleTimeSlotClick = (hour: number, minute: number) => {
    if (draggedTask && onTaskUpdate) {
      const task = tasks.find((t) => t.id === draggedTask);
      if (task) {
        const duration = differenceInMinutes(task.endTime, task.startTime);
        const newStart = new Date(task.startTime);
        newStart.setHours(hour, minute, 0, 0);
        const newEnd = addMinutes(newStart, duration);
        onTaskUpdate(draggedTask, newStart, newEnd);
      }
      setDraggedTask(null);
    }
  };

  const tasksByResource = resources.length > 0
    ? resources.map((r) => ({
        resource: r,
        tasks: tasks.filter((t) => t.resourceId === r.id),
      }))
    : [{ resource: { id: 'default', name: 'Schedule' }, tasks }];

  return (
    <div className={`overflow-auto ${className}`}>
      <div className="min-w-max">
        <div className="flex border-b">
          <div className="w-48 flex-shrink-0 p-2 font-semibold bg-gray-50">Resource</div>
          <div className="flex" style={{ width: totalWidth }}>
            {hours.map((hour) => (
              <div
                key={hour}
                className="flex-shrink-0 border-l text-center text-sm font-medium bg-gray-50 py-2"
                style={{ width: (timeSlotWidth * 2) }}
              >
                {format(new Date().setHours(hour, 0), 'HH:mm')}
              </div>
            ))}
          </div>
        </div>

        {tasksByResource.map(({ resource, tasks: resourceTasks }) => (
          <div key={resource.id} className="flex border-b hover:bg-gray-50">
            <div className="w-48 flex-shrink-0 p-3 border-r bg-white">
              <div className="font-medium">{resource.name}</div>
            </div>
            
            <div className="relative" style={{ width: totalWidth, height: rowHeight }}>
              {hours.map((hour) => (
                <div
                  key={hour}
                  className="absolute top-0 bottom-0 border-l border-dashed border-gray-200"
                  style={{ left: ((hour - startHour) * 2 * timeSlotWidth) }}
                  onClick={() => handleTimeSlotClick(hour, 0)}
                />
              ))}
              
              {resourceTasks.map((task) => {
                const { left, width } = getTaskPosition(task);
                return (
                  <div
                    key={task.id}
                    className={cn(
                      'absolute rounded px-2 py-1 text-xs cursor-pointer border transition-all hover:shadow-md',
                      statusColors[task.status],
                      typeColors[task.type || 'stop'],
                      draggedTask === task.id && 'ring-2 ring-blue-500'
                    )}
                    style={{
                      left,
                      width: Math.max(width, 40),
                      top: 8,
                      height: rowHeight - 16,
                    }}
                    onClick={() => onTaskClick?.(task.id)}
                    onMouseDown={() => handleTaskDragStart(task.id)}
                  >
                    <div className="font-semibold truncate">{task.title}</div>
                    <div className="text-xs opacity-75">
                      {format(task.startTime, 'HH:mm')} - {format(task.endTime, 'HH:mm')}
                    </div>
                    {task.details && (
                      <div className="text-xs opacity-60 truncate">{task.details}</div>
                    )}
                  </div>
                );
              })}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
