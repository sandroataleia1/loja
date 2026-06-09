// ---- Public API of the sync module ----------------------------------------
// Use `syncEngine` (singleton) to start/stop the engine from App.tsx.
// Import types from '@/sync/types' directly when needed in other modules.

import { SyncEngine, SyncLogger }  from './engine'

export { ConnectivityMonitor }              from './connectivity'
export { nextDelayMs, DEFAULT_RETRY_POLICY,
         shouldRetry, isRetryableStatus }   from './retry'
export { SyncQueue }                        from './queue'
export { EventSerializer,
         serializeSaleEvent }               from './serializers'
export type { SaleApiPayload }              from './serializers'
export { SyncWorker }                       from './workers'
export { SyncEngine, SyncLogger }
export { enqueueSync } from './engine'

export type {
  SyncEvent, SyncQueueEntry, SyncAttemptResult,
  SyncOperation, SyncEntityType, SyncStatus,
  LogLevel, RetryPolicyConfig, SyncEngineConfig,
  SyncState, SyncLogEntry,
} from './types'

/** Pre-built singleton — call syncEngine.start(onStateChange) in App.tsx. */
export const syncEngine = SyncEngine.getInstance()
