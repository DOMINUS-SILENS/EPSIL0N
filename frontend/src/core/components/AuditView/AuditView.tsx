import { AuditEntry, DomainEvent } from '@/core/api/client';
import { Card } from '@/design-system/composite/Card/Card';
import { Badge } from '@/design-system/primitives/Badge/Badge';
import { Button } from '@/design-system/primitives/Button/Button';
import { cn } from '@/lib/utils';
import { format } from 'date-fns';
import { CheckCircle, ChevronDown, ChevronRight, Clock, Database, Shield, XCircle } from 'lucide-react';
import { useState } from 'react';

interface EventTimelineProps {
  events: DomainEvent[];
  aggregateType: string;
  aggregateId: number;
  hashValid?: boolean;
  onVerify?: () => void;
  isVerifying?: boolean;
}

function EventCard({ event, isFirst, isLast }: { event: DomainEvent; isFirst: boolean; isLast: boolean }) {
  const [expanded, setExpanded] = useState(false);

  const getEventColor = (eventType: string) => {
    if (eventType.includes('Created')) return 'bg-green-100 text-green-800 border-green-200';
    if (eventType.includes('Updated')) return 'bg-blue-100 text-blue-800 border-blue-200';
    if (eventType.includes('Deleted')) return 'bg-red-100 text-red-800 border-red-200';
    if (eventType.includes('Transition')) return 'bg-purple-100 text-purple-800 border-purple-200';
    return 'bg-gray-100 text-gray-800 border-gray-200';
  };

  return (
    <div className="relative flex gap-4">
      {/* Timeline line */}
      {!isFirst && <div className="absolute left-6 -top-4 w-px h-8 bg-neutral-200" />}
      {!isLast && <div className="absolute left-6 top-10 w-px h-full bg-neutral-200" />}

      {/* Sequence badge */}
      <div className="relative z-10 flex items-center justify-center w-12 h-12 rounded-full bg-primary text-primary-foreground font-mono text-sm font-medium shrink-0">
        {event.sequence}
      </div>

      {/* Event content */}
      <div className="flex-1 pb-6">
        <Card className={cn("p-4", expanded && "ring-2 ring-primary")}>
          <div className="flex items-start justify-between">
            <div className="flex-1">
              <div className="flex items-center gap-2 mb-2">
                <Badge variant="outline" className={getEventColor(event.event_type)}>
                  {event.event_type}
                </Badge>
                <span className="text-xs text-neutral-500">
                  <Clock className="inline w-3 h-3 mr-1" />
                  {format(new Date(event.event_time), 'MMM dd, yyyy HH:mm:ss')}
                </span>
              </div>

              <div className="text-sm font-mono text-neutral-600 mb-2">
                ID: {event.event_id.substring(0, 16)}...
              </div>

              {expanded && (
                <div className="space-y-3 mt-4 pt-4 border-t border-neutral-100">
                  <div>
                    <label className="text-xs font-medium text-neutral-500 uppercase">Event Data</label>
                    <pre className="mt-1 text-xs bg-neutral-50 p-2 rounded overflow-auto max-h-40">
                      {JSON.stringify(event.event_data, null, 2)}
                    </pre>
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="text-xs font-medium text-neutral-500 uppercase">Previous Hash</label>
                      <p className="mt-1 text-xs font-mono break-all">
                        {event.previous_hash || 'Genesis'}
                      </p>
                    </div>
                    <div>
                      <label className="text-xs font-medium text-neutral-500 uppercase">Event Hash</label>
                      <p className="mt-1 text-xs font-mono break-all">{event.event_hash}</p>
                    </div>
                  </div>

                  <div className="text-xs text-neutral-500">
                    Recorded: {format(new Date(event.recorded_at), 'yyyy-MM-dd HH:mm:ss.SSS')}
                    {event.correlation_id && (
                      <span className="ml-4">Correlation: {event.correlation_id}</span>
                    )}
                  </div>
                </div>
              )}
            </div>

            <Button
              variant="ghost"
              size="sm"
              onClick={() => setExpanded(!expanded)}
              className="ml-2"
            >
              {expanded ? <ChevronDown className="w-4 h-4" /> : <ChevronRight className="w-4 h-4" />}
            </Button>
          </div>
        </Card>
      </div>
    </div>
  );
}

export function EventTimeline({
  events,
  aggregateType,
  aggregateId,
  hashValid,
  onVerify,
  isVerifying = false,
}: EventTimelineProps) {
  const sortedEvents = [...events].sort((a, b) => a.sequence - b.sequence);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h3 className="text-lg font-semibold flex items-center gap-2">
            <Database className="w-5 h-5" />
            Event Timeline
          </h3>
          <p className="text-sm text-neutral-500 mt-1">
            {aggregateType} #{aggregateId} · {events.length} events
          </p>
        </div>

        <div className="flex items-center gap-4">
          {hashValid !== undefined && (
            <div className="flex items-center gap-2">
              {hashValid ? (
                <Badge variant="outline" className="bg-green-50 text-green-700 border-green-200">
                  <CheckCircle className="w-3 h-3 mr-1" />
                  Hash Chain Valid
                </Badge>
              ) : (
                <Badge variant="destructive">
                  <XCircle className="w-3 h-3 mr-1" />
                  Invalid Chain
                </Badge>
              )}
            </div>
          )}

          {onVerify && (
            <Button
              variant="outline"
              size="sm"
              onClick={onVerify}
              disabled={isVerifying}
              className="flex items-center gap-2"
            >
              <Shield className="w-4 h-4" />
              {isVerifying ? 'Verifying...' : 'Verify Chain'}
            </Button>
          )}
        </div>
      </div>

      {/* Events */}
      <div className="space-y-2">
        {sortedEvents.map((event, index) => (
          <EventCard
            key={event.event_id}
            event={event}
            isFirst={index === 0}
            isLast={index === sortedEvents.length - 1}
          />
        ))}
      </div>

      {events.length === 0 && (
        <div className="text-center py-12 text-neutral-500">
          <Database className="w-12 h-12 mx-auto mb-4 opacity-50" />
          <p>No events found for this aggregate</p>
        </div>
      )}
    </div>
  );
}

interface AuditViewProps {
  aggregateType: string;
  aggregateId: number;
  useAudit: (type: string, id: number) => { data?: AuditEntry; isLoading: boolean; refetch: () => void };
}

export function AuditView({ aggregateType, aggregateId, useAudit }: AuditViewProps) {
  const { data: audit, isLoading, refetch } = useAudit(aggregateType, aggregateId);

  if (isLoading) {
    return (
      <Card className="p-8">
        <div className="flex items-center justify-center">
          <div className="animate-spin w-6 h-6 border-2 border-primary border-t-transparent rounded-full" />
        </div>
      </Card>
    );
  }

  if (!audit) {
    return (
      <Card className="p-8">
        <div className="text-center text-neutral-500">
          <Shield className="w-12 h-12 mx-auto mb-4 opacity-50" />
          <p>Failed to load audit data</p>
          <Button variant="outline" size="sm" onClick={() => refetch()} className="mt-4">
            Retry
          </Button>
        </div>
      </Card>
    );
  }

  return (
    <EventTimeline
      events={audit.events}
      aggregateType={audit.aggregate_type}
      aggregateId={audit.aggregate_id}
      hashValid={audit.hash_chain_valid}
      onVerify={() => refetch()}
      isVerifying={isLoading}
    />
  );
}
