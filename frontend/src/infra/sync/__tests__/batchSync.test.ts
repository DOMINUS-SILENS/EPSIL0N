import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  sendBatchToServer,
  processBatchOutbox,
  getSyncStatus,
  SyncConflictError,
  type BatchSyncResult,
} from '../batchSync';
import { db, type Outbox } from '../../dexie/db';

// Mock the database
vi.mock('../../dexie/db', () => ({
  db: {
    outbox: {
      where: vi.fn(),
      bulkUpdate: vi.fn(),
      update: vi.fn(),
      count: vi.fn(),
    },
    events: {
      bulkUpdate: vi.fn(),
      update: vi.fn(),
      count: vi.fn(),
    },
    conflicts: {
      add: vi.fn(),
    },
    transaction: vi.fn((_, __, callback) => callback()),
  },
}));

describe('batchSync', () => {
  const mockFetch = vi.fn();
  const mockLocalStorage = {
    getItem: vi.fn(),
    setItem: vi.fn(),
  };

  beforeEach(() => {
    vi.clearAllMocks();
    global.fetch = mockFetch;
    Object.defineProperty(global, 'localStorage', {
      value: mockLocalStorage,
      writable: true,
    });
    mockLocalStorage.getItem.mockReturnValue(null);
  });

  describe('sendBatchToServer', () => {
    it('should send batch of events successfully', async () => {
      const events: Outbox[] = [
        {
          id: '1',
          eventId: 'evt-1',
          aggregateId: 'agg-1',
          aggregateType: 'Order',
          sequence: 1,
          payload: { type: 'OrderCreated', version: 1, occurredAt: '2024-01-01' },
          status: 'pending',
          retryCount: 0,
          nextRetryAt: null,
          lastError: null,
          createdAt: new Date(),
          updatedAt: new Date(),
        },
      ];

      const mockResponse: BatchSyncResult = {
        acked: true,
        processed: 1,
        correlation_id: 'corr-1',
        results: [{ eventId: 'evt-1', status: 'ACCEPTED' }],
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse),
      });

      const result = await sendBatchToServer(events);

      expect(result.acked).toBe(true);
      expect(result.processed).toBe(1);
      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('/sync/ingest'),
        expect.objectContaining({
          method: 'POST',
          credentials: 'include',
        })
      );
    });

    it('should handle conflict response (409)', async () => {
      const events: Outbox[] = [
        {
          id: '1',
          eventId: 'evt-1',
          aggregateId: 'agg-1',
          aggregateType: 'Order',
          sequence: 1,
          payload: { type: 'OrderCreated' },
          status: 'pending',
          retryCount: 0,
          nextRetryAt: null,
          lastError: null,
          createdAt: new Date(),
          updatedAt: new Date(),
        },
      ];

      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 409,
        json: () => Promise.resolve({ conflicts: [{ event_id: 'evt-1', conflict_type: 'version_mismatch' }] }),
      });

      await expect(sendBatchToServer(events)).rejects.toThrow(SyncConflictError);
    });

    it('should handle server error', async () => {
      const events: Outbox[] = [];

      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
        statusText: 'Internal Server Error',
        json: () => Promise.resolve({ message: 'Server error' }),
      });

      await expect(sendBatchToServer(events)).rejects.toThrow('Server error');
    });

    it('should compress large payloads', async () => {
      const largePayload = 'x'.repeat(20 * 1024); // > 10KB
      const events: Outbox[] = [
        {
          id: '1',
          eventId: 'evt-1',
          aggregateId: 'agg-1',
          aggregateType: 'Order',
          sequence: 1,
          payload: { data: largePayload },
          status: 'pending',
          retryCount: 0,
          nextRetryAt: null,
          lastError: null,
          createdAt: new Date(),
          updatedAt: new Date(),
        },
      ];

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ acked: true, processed: 1, results: [] }),
      });

      await sendBatchToServer(events);

      const fetchCall = mockFetch.mock.calls[0];
      expect(fetchCall[1].headers['Content-Encoding']).toBe('gzip');
    });

    it('should update lastSyncAt on successful sync', async () => {
      const events: Outbox[] = [];

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ acked: true, processed: 0, results: [] }),
      });

      await sendBatchToServer(events);

      expect(mockLocalStorage.setItem).toHaveBeenCalledWith(
        'sfa_last_sync_at',
        expect.any(String)
      );
    });
  });

  describe('processBatchOutbox', () => {
    it('should return empty stats when no pending events', async () => {
      const mockWhere = {
        anyOf: vi.fn().mockReturnThis(),
        filter: vi.fn().mockReturnThis(),
        limit: vi.fn().mockReturnThis(),
        toArray: vi.fn().mockResolvedValue([]),
      };
      db.outbox.where = vi.fn().mockReturnValue(mockWhere);

      const result = await processBatchOutbox();

      expect(result.processed).toBe(0);
      expect(result.failed).toBe(0);
      expect(result.conflicts).toBe(0);
    });

    it('should process events in aggregate groups', async () => {
      const pendingEvents: Outbox[] = [
        {
          id: '1',
          eventId: 'evt-1',
          aggregateId: 'order-1',
          aggregateType: 'Order',
          sequence: 1,
          payload: { type: 'OrderCreated' },
          status: 'pending',
          retryCount: 0,
          nextRetryAt: null,
          lastError: null,
          createdAt: new Date(),
          updatedAt: new Date(),
        },
        {
          id: '2',
          eventId: 'evt-2',
          aggregateId: 'order-1',
          aggregateType: 'Order',
          sequence: 2,
          payload: { type: 'OrderUpdated' },
          status: 'pending',
          retryCount: 0,
          nextRetryAt: null,
          lastError: null,
          createdAt: new Date(),
          updatedAt: new Date(),
        },
      ];

      const mockWhere = {
        anyOf: vi.fn().mockReturnThis(),
        filter: vi.fn().mockReturnThis(),
        limit: vi.fn().mockReturnThis(),
        toArray: vi.fn().mockResolvedValue(pendingEvents),
      };
      db.outbox.where = vi.fn().mockReturnValue(mockWhere);

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({
          acked: true,
          processed: 2,
          results: [
            { eventId: 'evt-1', status: 'ACCEPTED' },
            { eventId: 'evt-2', status: 'ACCEPTED' },
          ],
        }),
      });

      const result = await processBatchOutbox();

      expect(result.processed).toBe(2);
    });

    it('should handle causality violations with retry', async () => {
      const pendingEvents: Outbox[] = [
        {
          id: '1',
          eventId: 'evt-1',
          aggregateId: 'order-1',
          aggregateType: 'Order',
          sequence: 1,
          payload: { type: 'OrderCreated' },
          status: 'pending',
          retryCount: 0,
          nextRetryAt: null,
          lastError: null,
          createdAt: new Date(),
          updatedAt: new Date(),
        },
      ];

      const mockWhere = {
        anyOf: vi.fn().mockReturnThis(),
        filter: vi.fn().mockReturnThis(),
        limit: vi.fn().mockReturnThis(),
        toArray: vi.fn().mockResolvedValue(pendingEvents),
      };
      db.outbox.where = vi.fn().mockReturnValue(mockWhere);

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({
          acked: true,
          processed: 0,
          results: [{ eventId: 'evt-1', status: 'CAUSALITY_VIOLATION' }],
        }),
      });

      const result = await processBatchOutbox();

      expect(result.failed).toBe(1);
    });
  });

  describe('getSyncStatus', () => {
    it('should return sync statistics', async () => {
      db.outbox.where = vi.fn().mockReturnValue({
        anyOf: vi.fn().mockReturnValue({ count: vi.fn().mockResolvedValue(5) }),
        equals: vi.fn().mockReturnValue({ count: vi.fn().mockResolvedValue(2) }),
      });
      mockLocalStorage.getItem.mockReturnValue('2024-01-01T00:00:00Z');

      const result = await getSyncStatus();

      expect(result.pending).toBe(5);
      expect(result.failed).toBe(2);
      expect(result.lastSyncAt).toBe('2024-01-01T00:00:00Z');
    });
  });

  describe('SyncConflictError', () => {
    it('should create error with conflicts', () => {
      const conflicts = [{ event_id: 'evt-1', reason: 'version mismatch' }];
      const error = new SyncConflictError('conflict detected', conflicts);

      expect(error.message).toBe('conflict detected');
      expect(error.conflicts).toEqual(conflicts);
      expect(error.name).toBe('SyncConflictError');
    });
  });
});
