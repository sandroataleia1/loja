import { create } from 'zustand'
import type { SyncState, SyncStatus } from '@/sync/types'

interface SyncStore extends SyncState {
  /** Number of entries that exceeded max_attempts and are permanently failed. */
  failedCount:   number
  /** True while the engine is actively sending requests to the API. */
  isSyncing:     boolean

  setSyncState:  (state: SyncState)                              => void
  setStatus:     (status: SyncStatus)                            => void
  setPending:    (count: number)                                 => void
  setFailed:     (count: number)                                 => void
  setLastSyncAt: (at: string | null)                             => void
  setIsSyncing:  (syncing: boolean)                              => void
  /** Convenience updater used by SyncEngine and db/database.ts. */
  setStatus2:    (update: Partial<Omit<SyncStore, 'setStatus2' | 'setSyncState' | 'setStatus' | 'setPending' | 'setFailed' | 'setLastSyncAt' | 'setIsSyncing'>>) => void
}

export const useSyncStore = create<SyncStore>((set) => ({
  // initial SyncState
  status:       'idle',
  isOnline:     typeof navigator !== 'undefined' ? navigator.onLine : true,
  pending:      0,
  failedCount:  0,
  isSyncing:    false,
  lastSyncAt:   null,
  lastError:    null,

  setSyncState:  (state)     => set(state),
  setStatus:     (status)    => set({ status }),
  setPending:    (count)     => set({ pending: count }),
  setFailed:     (count)     => set({ failedCount: count }),
  setLastSyncAt: (at)        => set({ lastSyncAt: at }),
  setIsSyncing:  (syncing)   => set({ isSyncing: syncing }),
  setStatus2:    (update)    => set(update),
}))
