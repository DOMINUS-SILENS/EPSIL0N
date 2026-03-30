import pako from 'pako';

/**
 * Compress payload using gzip (pako)
 */
export async function compressPayload(data: string): Promise<Blob> {
  const compressed = pako.gzip(data, { level: 6 });
  return new Blob([compressed], { type: 'application/octet-stream' });
}

/**
 * Decompress gzipped response
 */
export async function decompressResponse(data: ArrayBuffer): Promise<string> {
  const decompressed = pako.inflate(new Uint8Array(data), { to: 'string' });
  return decompressed;
}

/**
 * Check if compression should be used based on payload size
 */
export function shouldCompress(payloadSize: number): boolean {
  // Compress if payload is larger than 10KB
  return payloadSize > 10 * 1024;
}

/**
 * Calculate payload size
 */
export function getPayloadSize(data: unknown): number {
  return JSON.stringify(data).length;
}
