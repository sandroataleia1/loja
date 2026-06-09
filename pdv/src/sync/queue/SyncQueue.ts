import { db } from '@/services/database'
import type { SyncQueueEntry } from '../types'

/** Thin wrapper over Tauri sync_queue commands. */
export class SyncQueue {
  async pendingCount(): Promise<number> {
    return db<number>('sync_pending_count', {})
  }

  async drain(limit: number): Promise<SyncQueueEntry[]> {
    return db<SyncQueueEntry[]>('sync_next_batch', { limit })
  }

  async markSuccess(uuid: string): Promise<void> {
    await db<boolean>('sync_mark_success', { uuid })
  }

  async markFailed(uuid: string, error: string): Promise<void> {
    await db<void>('sync_mark_failed', { uuid, error })
  }
}
