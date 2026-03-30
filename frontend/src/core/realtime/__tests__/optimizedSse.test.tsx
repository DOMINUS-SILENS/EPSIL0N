import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import {
  useOptimizedServerSentEvents,
  useEventPolling,
  useLegacySSE,
} from '../optimizedSse';
import * as tanstackQuery from '@tanstack/react-query';

// Mock React Query
vi.mock('@tanstack/react-query', () => ({
  useQueryClient: vi.fn(),
}));

// Mock sonner toast
vi.mock('sonner', () => ({
  toast: {
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn(),
    warning: vi.fn(),
  },
}));

describe('optimizedSse', () => {
  const mockFetch = vi.fn();
  const mockInvalidateQueries = vi.fn();
  const mockQueryClient = {
    invalidateQueries: mockInvalidateQueries,
  };

  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers({ shouldAdvanceTime: true });
    global.fetch = mockFetch;
    vi.mocked(tanstackQuery.useQueryClient).mockReturnValue(mockQueryClient as any);
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  describe('useOptimizedServerSentEvents', () => {
    it('should not poll when userId is null', () => {
      renderHook(() => useOptimizedServerSentEvents(null));
      expect(mockFetch).not.toHaveBeenCalled();
    });

    it('should start polling when userId is provided', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({
          events: [],
          meta: { last_event_id: 0, has_more: false },
        }),
      });

      renderHook(() => useOptimizedServerSentEvents('user-1'));

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          expect.stringContaining('/events/long-poll'),
          expect.objectContaining({
            method: 'GET',
            credentials: 'include',
            signal: expect.any(AbortSignal),
          })
        );
      });
    }, 10000);

    it('should handle aggregate_updated events', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({
          events: [{
            id: 1,
            type: 'aggregate_updated',
            aggregate_type: 'Order',
            aggregate_id: 'order-1',
            payload: {},
            timestamp: '2024-01-01T00:00:00Z',
          }],
          meta: { last_event_id: 1, has_more: false },
        }),
      });

      renderHook(() => useOptimizedServerSentEvents('user-1'));

      await waitFor(() => {
        expect(mockInvalidateQueries).toHaveBeenCalledWith({ queryKey: ['erp', 'orders'] });
      }, { timeout: 5000 });

      expect(mockInvalidateQueries).toHaveBeenCalledWith({ queryKey: ['erp', 'orders', 'order-1'] });
    }, 10000);

    it('should abort on unmount', async () => {
      mockFetch.mockImplementation(() => new Promise(() => {}));

      const { unmount } = renderHook(() => useOptimizedServerSentEvents('user-1'));

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalled();
      }, { timeout: 5000 });

      const fetchCall = mockFetch.mock.calls[0];
      const signal = fetchCall[1].signal;

      unmount();

      expect(signal.aborted).toBe(true);
    }, 10000);
  });

  describe('useEventPolling', () => {
    it('should poll at specified interval', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({
          events: [],
          meta: { last_event_id: 0, has_more: false },
        }),
      });

      renderHook(() => useEventPolling('user-1', 1000));

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledTimes(1);
      }, { timeout: 5000 });

      vi.advanceTimersByTime(1000);

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledTimes(2);
      }, { timeout: 5000 });
    }, 10000);
  });

  describe('useLegacySSE', () => {
    it('should create EventSource when userId is provided', () => {
      const mockEventSource = {
        close: vi.fn(),
        onmessage: null,
        onerror: null,
      };

      global.EventSource = vi.fn(() => mockEventSource) as any;

      renderHook(() => useLegacySSE('user-1'));

      expect(global.EventSource).toHaveBeenCalledWith(
        expect.stringContaining('/events/stream'),
        expect.objectContaining({ withCredentials: true })
      );
    });

    it('should close EventSource on unmount', () => {
      const mockClose = vi.fn();
      const mockEventSource = {
        close: mockClose,
        onmessage: null,
        onerror: null,
      };

      global.EventSource = vi.fn(() => mockEventSource) as any;

      const { unmount } = renderHook(() => useLegacySSE('user-1'));

      unmount();

      expect(mockClose).toHaveBeenCalled();
    });

    it('should close on error', () => {
      const mockClose = vi.fn();
      const mockEventSource = {
        close: mockClose,
        onmessage: null,
        onerror: null as any,
      };

      global.EventSource = vi.fn(() => mockEventSource) as any;

      renderHook(() => useLegacySSE('user-1'));

      mockEventSource.onerror();

      expect(mockClose).toHaveBeenCalled();
    });
  });
});
