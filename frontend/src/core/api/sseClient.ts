import { QueryClient } from '@tanstack/react-query';

interface DomainEvent {
  id: string;
  type: string;
  payload: any;
  timestamp: string;
}

/**
 * Listens to Server-Sent Events pushed from the backend Event Store Outbox.
 * Translates Domain Events into exact TanStack Query invalidations.
 * This guarantees zero-drift projections without polling.
 */
export class SSEClient {
  private eventSource: EventSource | null = null;
  private queryClient: QueryClient;

  constructor(queryClient: QueryClient) {
    this.queryClient = queryClient;
  }

  public connect(token: string) {
    if (this.eventSource) return;

    // Use absolute URL from env, default to local if missing
    const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
    
    // Connect to the generic push endpoint
    this.eventSource = new EventSource(`${baseUrl}/stream?token=${token}`);

    this.eventSource.onmessage = (e) => {
      try {
        const event: DomainEvent = JSON.parse(e.data);
        this.handleDomainEvent(event);
      } catch (err) {
        console.error('Failed to parse domain event payload from SSE', err);
      }
    };

    this.eventSource.onerror = () => {
      console.warn('SSE Connection lost. Reconnecting in 5s...');
      this.eventSource?.close();
      this.eventSource = null;
      setTimeout(() => this.connect(token), 5000);
    };
  }

  public disconnect() {
    if (this.eventSource) {
      this.eventSource.close();
      this.eventSource = null;
    }
  }

  private handleDomainEvent(event: DomainEvent) {
    console.debug(`[SSE] Received domain event: ${event.type}`);

    // Map Domain Events to Query Invalidations
    // By convention, we invalidate the specific bounded context slice.
    
    // Core Module Events
    if (['UserCreated', 'UserRoleChanged', 'UserTerritoryAssigned', 'UserDeactivated'].includes(event.type)) {
      this.queryClient.invalidateQueries({ queryKey: ['core', 'users'] });
      // If we have the payload ID, we can invalidate exactly that query:
      if (event.payload?.id) {
        this.queryClient.invalidateQueries({ queryKey: ['core', 'users', String(event.payload.id)] });
      }
    }
    
    if (['RolePermissionsUpdated'].includes(event.type)) {
      this.queryClient.invalidateQueries({ queryKey: ['core', 'roles'] });
    }

    if (['TerritoryCreated', 'TerritoryMoved'].includes(event.type)) {
      this.queryClient.invalidateQueries({ queryKey: ['core', 'territories'] });
    }

    // CRM Module Events
    if (['LeadCreated', 'LeadStateChanged', 'LeadConverted'].includes(event.type)) {
      this.queryClient.invalidateQueries({ queryKey: ['crm', 'leads'] });
    }
    
    if (['CustomerCreated', 'CustomerUpdated'].includes(event.type)) {
      this.queryClient.invalidateQueries({ queryKey: ['crm', 'customers'] });
    }

    // ERP Module Events
    if (['OrderCreated', 'OrderConfirmed', 'OrderCancelled'].includes(event.type)) {
      this.queryClient.invalidateQueries({ queryKey: ['erp', 'orders'] });
    }
    
    // Inventory
    if (['StockMovementRecorded'].includes(event.type)) {
      this.queryClient.invalidateQueries({ queryKey: ['erp', 'products'] });
      this.queryClient.invalidateQueries({ queryKey: ['erp', 'stock'] });
    }

    // SFA Module Events
    if (['VisitScheduled', 'VisitCompleted'].includes(event.type)) {
      this.queryClient.invalidateQueries({ queryKey: ['sfa', 'visits'] });
    }
    
    // Delivery Module Events
    if (['DeliveryTourStarted', 'ParcelDelivered'].includes(event.type)) {
      this.queryClient.invalidateQueries({ queryKey: ['delivery', 'tours'] });
    }
    // HR Module
    if (['EmployeeCreated', 'EmployeeUpdated', 'PayrollProcessed'].includes(event.type)) {
      this.queryClient.invalidateQueries({ queryKey: ['hr', 'employees'] });
    }

    // Fleet Module
    if (['VehicleRegistered', 'MaintenanceLogged', 'FuelTracked'].includes(event.type)) {
      this.queryClient.invalidateQueries({ queryKey: ['fleet', 'vehicles'] });
    }

    // Purchasing Module
    if (['PurchaseOrderCreated', 'PurchaseOrderReceived'].includes(event.type)) {
      this.queryClient.invalidateQueries({ queryKey: ['purchasing', 'orders'] });
    }

    // Accounting Module
    if (['JournalEntryPosted'].includes(event.type)) {
      this.queryClient.invalidateQueries({ queryKey: ['accounting', 'journal'] });
    }
  }
}
