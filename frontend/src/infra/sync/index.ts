// Batch Sync
export {
  sendBatchToServer,
  processBatchOutbox,
  getSyncStatus,
  SyncConflictError,
  type BatchSyncResult,
  type SyncStats,
} from './batchSync';

// Delta Sync
export {
  deltaSync,
  syncEntities,
  syncEntity,
  resumeSync,
  getSyncPlan,
  getEntitySummary,
  getSyncStats,
  getCheckpoint,
  saveCheckpoint,
  clearAllCheckpoints,
  type DeltaSyncOptions,
  type DeltaSyncResult,
  type SyncCheckpoint,
} from './deltaSync';

// Compression
export {
  compressPayload,
  decompressResponse,
  shouldCompress,
  getPayloadSize,
} from './compression';

// Legacy exports for backward compatibility
export { sendBatchToServer as sendEventsToServer } from './batchSync';
