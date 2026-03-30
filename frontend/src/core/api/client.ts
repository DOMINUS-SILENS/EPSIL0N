import axios from 'axios';
import { toast } from 'sonner';
import { v4 as uuidv4 } from 'uuid';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

// Idempotency key storage for retries
const IDEMPOTENCY_KEY_PREFIX = 'idempotency_key_';

const api = axios.create({
  baseURL: API_URL,
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

/**
 * Generate or retrieve an idempotency key for a command
 * Keys are stored in localStorage for potential retry
 */
export function getIdempotencyKey(commandId: string): string {
  const storageKey = `${IDEMPOTENCY_KEY_PREFIX}${commandId}`;
  let key = localStorage.getItem(storageKey);
  if (!key) {
    key = uuidv4();
    if (key) {
      localStorage.setItem(storageKey, key);
    }
  }
  return key;
}

/**
 * Clear an idempotency key after successful command execution
 */
export function clearIdempotencyKey(commandId: string): void {
  const storageKey = `${IDEMPOTENCY_KEY_PREFIX}${commandId}`;
  localStorage.removeItem(storageKey);
}

// Request interceptor: add idempotency keys
api.interceptors.request.use(async (config) => {
  if (['post', 'put', 'patch', 'delete'].includes(config.method || '')) {
    // Add idempotency key for commands if not already present
    if (config.headers && !config.headers['Idempotency-Key']) {
      const commandId = config.url || 'unknown';
      config.headers['Idempotency-Key'] = getIdempotencyKey(commandId);
    }
  }
  return config;
});

// Response interceptor: handle common errors including 409 Conflict
api.interceptors.response.use(
  (response) => {
    // Clear idempotency key on success
    const config = response.config;
    if (config.url && ['post', 'put', 'patch', 'delete'].includes(config.method || '')) {
      clearIdempotencyKey(config.url);
    }
    return response;
  },
  (error) => {
    if (error.response?.status === 401) {
      if (!window.location.pathname.startsWith('/login')) {
        window.location.href = '/login';
        toast.error('Session expired. Please log in again.');
      }
    } else if (error.response?.status === 403) {
      toast.error('Permission denied');
    } else if (error.response?.status === 409) {
      // Conflict - idempotency key already used or concurrent modification
      const message = error.response?.data?.message || 'Conflict detected. Please retry.';
      toast.error(message);
    } else if (error.response?.status === 422) {
      return Promise.reject(error.response.data.errors);
    } else if (error.response?.status === 429) {
      toast.error('Too many requests. Please slow down.');
    } else if (error.response?.status >= 500) {
      toast.error('Server error. Please try again later.');
    }
    return Promise.reject(error);
  }
);

export { api };

/**
 * Type for API responses from projection endpoints
 */
export interface ProjectionResponse<T> {
  data: T;
  version: number;
  projected_at: string;
}

/**
 * Type for command responses with resulting events
 */
export interface CommandResponse<T = unknown> {
  success: boolean;
  events: DomainEvent[];
  data?: T;
  error?: string;
}

/**
 * Domain event structure
 */
export interface DomainEvent {
  event_id: string;
  aggregate_type: string;
  aggregate_id: number;
  event_type: string;
  event_data: Record<string, unknown>;
  event_time: string;
  sequence: number;
  recorded_at: string;
  correlation_id?: string;
  previous_hash?: string;
  event_hash: string;
}

/**
 * Audit log entry for verification
 */
export interface AuditEntry {
  events: DomainEvent[];
  aggregate_type: string;
  aggregate_id: number;
  hash_chain_valid: boolean;
  total_events: number;
}
