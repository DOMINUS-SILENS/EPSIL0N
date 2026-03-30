import '@testing-library/jest-dom/vitest';
import { vi, beforeEach } from 'vitest';

// Mock crypto.randomUUID for consistent tests
Object.defineProperty(global, 'crypto', {
  value: {
    ...global.crypto,
    randomUUID: vi.fn(() => 'test-uuid-123'),
  },
  writable: true,
  configurable: true,
});

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
};
global.localStorage = localStorageMock as unknown as Storage;

// Mock fetch
global.fetch = vi.fn();

// Reset mocks before each test
beforeEach(() => {
  vi.clearAllMocks();
  localStorageMock.getItem.mockReturnValue(null);
});
