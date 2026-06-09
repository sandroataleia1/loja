import { SYNC_SCHEMA_VERSION } from '../types'
import type { SyncEntityType, SyncEvent, SyncOperation, SyncQueueEntry } from '../types'

export class EventSerializer {
  /**
   * Reconstructs a versioned SyncEvent envelope from a raw SyncQueueEntry.
   * The Rust side stores only the domain payload in sync_queue.payload;
   * the envelope fields come from the other columns.
   */
  static fromQueueEntry(entry: SyncQueueEntry): SyncEvent {
    return {
      v:           SYNC_SCHEMA_VERSION,
      id:          entry.uuid,
      entity_type: entry.entity_type as SyncEntityType,
      entity_uuid: entry.entity_uuid,
      operation:   entry.operation   as SyncOperation,
      tenant_uuid: '',                      // populated by worker from session
      occurred_at: entry.created_at,
      payload:     JSON.parse(entry.payload) as unknown,
    }
  }

  static isVersionSupported(v: number): boolean {
    return v === SYNC_SCHEMA_VERSION
  }
}
