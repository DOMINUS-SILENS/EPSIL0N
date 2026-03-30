import { describe, it, expect } from 'vitest';
import { compressPayload, decompressResponse, shouldCompress, getPayloadSize } from './compression';

describe('compression', () => {
  describe('compressPayload', () => {
    it('should compress string data to blob', async () => {
      const data = JSON.stringify({ test: 'data', large: 'x'.repeat(1000) });
      const result = await compressPayload(data);

      expect(result).toBeInstanceOf(Blob);
      expect(result.type).toBe('application/octet-stream');
      expect(result.size).toBeGreaterThan(0);
      expect(result.size).toBeLessThan(data.length); // Should be smaller
    });

    it('should compress empty string', async () => {
      const result = await compressPayload('');
      expect(result).toBeInstanceOf(Blob);
    });
  });

  describe('decompressResponse', () => {
    it('should decompress gzipped data', async () => {
      const original = JSON.stringify({ test: 'data', nested: { value: 123 } });
      const compressed = await compressPayload(original);
      const arrayBuffer = await compressed.arrayBuffer();

      const result = await decompressResponse(arrayBuffer);
      expect(result).toBe(original);
    });

    it('should handle simple text', async () => {
      const text = 'Hello, World!';
      // First compress
      const blob = await compressPayload(text);
      const arrayBuffer = await blob.arrayBuffer();

      // Then decompress
      const result = await decompressResponse(arrayBuffer);
      expect(result).toBe(text);
    });
  });

  describe('shouldCompress', () => {
    it('should return true for payloads > 10KB', () => {
      expect(shouldCompress(10 * 1024 + 1)).toBe(true);
      expect(shouldCompress(100 * 1024)).toBe(true);
    });

    it('should return false for payloads <= 10KB', () => {
      expect(shouldCompress(10 * 1024)).toBe(false);
      expect(shouldCompress(1024)).toBe(false);
      expect(shouldCompress(0)).toBe(false);
    });
  });

  describe('getPayloadSize', () => {
    it('should calculate stringified JSON size', () => {
      const data = { test: 'value' };
      const size = getPayloadSize(data);
      expect(size).toBe(JSON.stringify(data).length);
    });

    it('should handle nested objects', () => {
      const data = { a: { b: { c: 'deep' } } };
      expect(getPayloadSize(data)).toBeGreaterThan(0);
    });

    it('should handle arrays', () => {
      const data = [1, 2, 3, { nested: true }];
      expect(getPayloadSize(data)).toBe(JSON.stringify(data).length);
    });
  });
});
