import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  deltaSync,
  syncEntities,
  syncEntity,
  getCheckpoint,
  saveCheckpoint,
  clearAllCheckpoints,
  getSyncStats,
  getSyncPlan,
  resumeSync,
  type DeltaSyncResult,
} from '../deltaSync';
import { db } from '../../dexie/db';

// Mock the database
vi.mock('../../dexie/db', () => ({
  db: {
    events: {
      put: vi.fn(),
      count: vi.fn(),
    },
    outbox: {
      where: vi.fn(),
    },
    customers: {
      put: vi.fn(),
      delete: vi.fn(),
    },
    orders: {
      put: vi.fn(),
      delete: vi.fn(),
    },
    transaction: vi.fn((_, __, callback) => callback()),
  },
}));

describe('deltaSync', () => {
  const mockFetch = vi.fn();
  const mockLocalStorage = {
    getItem: vi.fn(),
    setItem: vi.fn(),
    removeItem: vi.fn(),
  };

  beforeEach(() => {
    vi.clearAllMocks();
    global.fetch = mockFetch;
    Object.defineProperty(global, 'localStorage', {
      value: mockLocalStorage,
      writable: true,
    });
  });

  describe('deltaSync', () => {
    it('should fetch delta data from server', async () => {
      const mockResponse: DeltaSyncResult = {
        data: {
          customers: [{ id: '1', name: 'Test Customer' }],
        },
        meta: {
          sync_timestamp: '2024-01-01T00:00:00Z',
          has_more: false,
        },
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse),
      });

      const result = await deltaSync({
        entities: ['customers'],
        lastSyncAt: '2023-12-01T00:00:00Z',
      });

      expect(result.data.customers).toHaveLength(1);
      expect(result.meta.has_more).toBe(false);
      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('/sync/delta'),
        expect.objectContaining({ method: 'GET' })
      );
    });

    it('should include cursor in request when provided', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({
          data: {},
          meta: { sync_timestamp: '2024-01-01T00:00:00Z', has_more: false },
        }),
      });

      await deltaSync({
        entities: ['customers'],
        cursor: 'cursor-123',
      });

      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('cursor=cursor-123'),
        expect.any(Object)
      );
    });

    it('should handle server errors', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        statusText: 'Server Error',
        json: () => Promise.resolve({ message: 'Sync failed' }),
      });

      await expect(deltaSync({ entities: ['customers'] })).rejects.toThrow('Sync failed');
    });
  });

  describe('syncEntity', () => {
    it('should sync single entity with pagination', async () => {
      const onProgress = vi.fn();

      // First call returns data with more available
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({
          data: { customers: [{ id: '1', name: 'Test' }] },
          meta: {
            sync_timestamp: '2024-01-01T00:00:00Z',
            has_more: true,
            next_cursors: { customers: 'cursor-1' },
          },
        }),
      });

      // Second call returns no more data
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({
          data: { customers: [{ id: '2', name: 'Test 2' }] },
          meta: {
            sync_timestamp: '2024-01-01T00:01:00Z',
            has_more: false,
          },
        }),
      });

      mockLocalStorage.getItem.mockReturnValue(null);

      await syncEntity('customers', onProgress);

      expect(mockFetch).toHaveBeenCalledTimes(2);
      expect(onProgress).toHaveBeenCalledWith('customers', 100);
    });
  });

  describe('syncEntities', () => {
    it('should sync multiple entities sequentially', async () => {
      const onProgress = vi.fn();

      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({
          data: { customers: [], orders: [] },
          meta: { sync_timestamp: '2024-01-01T00:00:00Z', has_more: false },
        }),
      });

      mockLocalStorage.getItem.mockReturnValue(null);

      await syncEntities(['customers', 'orders'], onProgress);

      expect(mockFetch).toHaveBeenCalledTimes(2);
    });
  });

  describe('checkpoints', () => {
    it('getCheckpoint should return null when no checkpoint exists', async () => {
      mockLocalStorage.getItem.mockReturnValue(null);

      const result = await getCheckpoint('customers');

      expect(result).toBeNull();
    });

    it('getCheckpoint should return parsed checkpoint', async () => {
      const checkpoint = {
        entity: 'customers',
        lastSyncAt: '2024-01-01T00:00:00Z',
        cursor: 'cursor-1',
        hasMore: false,
      };
      mockLocalStorage.getItem.mockReturnValue(JSON.stringify(checkpoint));

      const result = await getCheckpoint('customers');

      expect(result).toEqual(checkpoint);
    });

    it('saveCheckpoint should store checkpoint', async () => {
      const checkpoint = {
        entity: 'customers',
        lastSyncAt: '2024-01-01T00:00:00Z',
        hasMore: false,
      };

      await saveCheckpoint(checkpoint);

      expect(mockLocalStorage.setItem).toHaveBeenCalledWith(
        'sync_checkpoint:customers',
        JSON.stringify(checkpoint)
      );
    });

    it('clearAllCheckpoints should remove all checkpoint keys', () => {
      const keys = ['sync_checkpoint:customers', 'sync_checkpoint:orders', 'other-key'];
      // Mock Object.keys for localStorage
      vi.spyOn(Object, 'keys').mockReturnValueOnce(keys as any);

      clearAllCheckpoints();

      expect(mockLocalStorage.removeItem).toHaveBeenCalledWith('sync_checkpoint:customers');
      expect(mockLocalStorage.removeItem).toHaveBeenCalledWith('sync_checkpoint:orders');
      expect(mockLocalStorage.removeItem).not.toHaveBeenCalledWith('other-key');
      
      // Restore
      vi.restoreAllMocks();
    });
  });

  describe('getSyncPlan', () => {
    it('should fetch sync plan from server', async () => {
      const mockPlan = {
        plan: [
          { entity: 'customers', action: 'delta', estimatedCount: 100 },
          { entity: 'orders', action: 'full', reason: 'schema_changed' },
        ],
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockPlan),
      });

      const result = await getSyncPlan('2024-01-01T00:00:00Z');

      expect(result.plan).toHaveLength(2);
      expect(result.plan[0].action).toBe('delta');
    });

    it('should throw on server error', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        json: () => Promise.resolve({}),
      });

      await expect(getSyncPlan('2024-01-01')).rejects.toThrow('Failed to get sync plan');
    });
  });

  describe('resumeSync', () => {
    it('should resume sync from last event id', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({
          events: [{ id: 1, aggregate_id: 'a1', aggregate_type: 'Order', type: 'Created', payload: {}, timestamp: '2024-01-01' }],
          meta: { has_more: false, last_event_id: 1 },
        }),
      });

      const onEvent = vi.fn();
      const result = await resumeSync(0, onEvent);

      expect(result).toBe(1);
      expect(onEvent).toHaveBeenCalled();
    });

    it('should paginate through events', async () => {
      mockFetch
        .mockResolvedValueOnce({
          ok: true,
          json: () => Promise.resolve({
            events: [{ id: 1 }],
            meta: { has_more: true, last_event_id: 1 },
          }),
        })
        .mockResolvedValueOnce({
          ok: true,
          json: () => Promise.resolve({
            events: [{ id: 2 }],
            meta: { has_more: false, last_event_id: 2 },
          }),
        });

      const result = await resumeSync(0);

      expect(result).toBe(2);
      expect(mockFetch).toHaveBeenCalledTimes(2);
    });
  });

  describe('getSyncStats', () => {
    it('should return sync statistics', async () => {
      db.events.count = vi.fn().mockResolvedValue(100);
      db.outbox.where = vi.fn().mockReturnValue({
        anyOf: vi.fn().mockReturnValue({ count: vi.fn().mockResolvedValue(5) }),
        equals: vi.fn().mockReturnValue({ count: vi.fn().mockResolvedValue(2) }),
      });
      mockLocalStorage.getItem.mockReturnValue('2024-01-01T00:00:00Z');

      const result = await getSyncStats();

      expect(result.totalLocalEvents).toBe(100);
      expect(result.pendingOutbox).toBe(5);
      expect(result.failedOutbox).toBe(2);
      expect(result.lastSyncAt).toBe('2024-01-01T00:00:00Z');
    });
  });
});
