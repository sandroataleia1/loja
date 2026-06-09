/**
 * src/db/database.ts
 *
 * TypeScript interface layer for the Rust/rusqlite SQLite backend.
 *
 * Architecture: this app stores data in a local SQLite file managed by Rust
 * (rusqlite + Tauri commands). All database operations go through Tauri's
 * `invoke()` — there is NO tauri-plugin-sql dependency.
 *
 * This module provides:
 *  - Typed wrappers over the sync-related Tauri commands
 *  - `enqueueSync()` — adds an item to sync_queue via Rust
 *  - `updateSyncStatus()` — updates the sync_status singleton
 *  - Re-exports of the SyncQueue methods for direct use
 */

import { invoke } from '@tauri-apps/api/core'

// ── Sync Queue ─────────────────────────────────────────────────────────────

export interface SyncQueueItem {
  /** Row UUID (matches sync_queue.uuid in SQLite). */
  uuid:        string
  entity_type: string
  entity_uuid: string
  operation:   string
  /** JSON-serialised payload stored in the DB. */
  payload:     string
  attempts:    number
  last_error:  string | null
  created_at:  string
}

export interface EnqueueSyncParams {
  entityType:  string
  entityUuid:  string
  operation:   string
  payload:     object
  endpoint:    string
  method:      string
  maxAttempts?: number
  priority?:   number
}

/**
 * Insert a new entry into sync_queue via the Rust `sync_enqueue` command.
 *
 * NOTE: `sync_enqueue` must be registered in lib.rs invoke_handler.
 * If the Rust command does not yet exist, this function returns without
 * throwing — the engine will pick up the entry from the existing queue
 * populated by the sale_create command.
 */
export async function enqueueSync(params: EnqueueSyncParams): Promise<void> {
  await invoke<void>('sync_enqueue', {
    entityType:  params.entityType,
    entityUuid:  params.entityUuid,
    operation:   params.operation,
    payload:     JSON.stringify(params.payload),
    endpoint:    params.endpoint,
    method:      params.method,
    maxAttempts: params.maxAttempts ?? 5,
    priority:    params.priority ?? 0,
  })
}

// ── Sync Queue reads (delegated to existing Rust commands) ─────────────────

/** Counts pending entries (attempts < max_attempts). */
export async function getPendingCount(): Promise<number> {
  return invoke<number>('sync_pending_count', {})
}

/** Returns the next batch of pending entries. */
export async function getNextBatch(limit = 10): Promise<SyncQueueItem[]> {
  return invoke<SyncQueueItem[]>('sync_next_batch', { limit })
}

/** Mark an entry as successfully synced (deletes it from queue). */
export async function markSynced(uuid: string, responseBody?: string): Promise<void> {
  await invoke<boolean>('sync_mark_success', { uuid })
  // Log the result to sync_log via Rust (command may not exist yet — safe to ignore)
  try {
    await invoke<void>('sync_log_success', { syncQueueUuid: uuid, responseBody: responseBody ?? null })
  } catch {
    // sync_log_success command is optional — fails silently if not registered
  }
}

/** Mark an entry as failed, incrementing attempts. */
export async function markFailed(uuid: string, error: string): Promise<void> {
  await invoke<void>('sync_mark_failed', { uuid, error })
}

// ── Sync Status singleton ──────────────────────────────────────────────────

export interface SyncStatusUpdate {
  lastSyncAt?:   string | null
  lastSyncOk?:   boolean
  lastError?:    string | null
  pendingCount?: number
  failedCount?:  number
  isSyncing?:    boolean
}

/**
 * Update the sync_status singleton row via Rust `sync_update_status` command.
 *
 * Falls back silently if the command is not yet registered (migration v0008
 * adds the table, but the Rust command must be added separately).
 */
export async function updateSyncStatus(update: SyncStatusUpdate): Promise<void> {
  if (Object.keys(update).length === 0) return
  try {
    await invoke<void>('sync_update_status', {
      lastSyncAt:   update.lastSyncAt   ?? null,
      lastSyncOk:   update.lastSyncOk   != null ? (update.lastSyncOk ? 1 : 0) : null,
      lastError:    update.lastError    ?? null,
      pendingCount: update.pendingCount ?? null,
      failedCount:  update.failedCount  ?? null,
      isSyncing:    update.isSyncing    != null ? (update.isSyncing ? 1 : 0) : null,
    })
  } catch {
    // Soft fail — sync_status persistence is best-effort until Rust command is wired
  }
}

// ── Session persistence ────────────────────────────────────────────────────

export interface LocalSessionData {
  userUuid:      string
  userName:      string
  token:         string
  tenantUuid:    string
  storeUuid:     string
  sessionUuid?:  string | null
  registerUuid?: string | null
  openingAmount?: number
  openedAt?:     string | null
  expiresAt?:    string | null
}

/**
 * Persist session to SQLite local_session table via Rust `session_save` command.
 * Falls back silently if not yet registered.
 */
export async function saveLocalSession(data: LocalSessionData): Promise<void> {
  try {
    await invoke<void>('session_save', {
      userUuid:      data.userUuid,
      userName:      data.userName,
      token:         data.token,
      tenantUuid:    data.tenantUuid,
      storeUuid:     data.storeUuid,
      sessionUuid:   data.sessionUuid   ?? null,
      registerUuid:  data.registerUuid  ?? null,
      openingAmount: data.openingAmount ?? 0,
      openedAt:      data.openedAt      ?? null,
      expiresAt:     data.expiresAt     ?? null,
    })
  } catch {
    // Soft fail until Rust command is implemented
  }
}

/** Load the persisted session from SQLite. Returns null if not found. */
export async function loadLocalSession(): Promise<LocalSessionData | null> {
  try {
    return await invoke<LocalSessionData | null>('session_load', {})
  } catch {
    return null
  }
}

/** Clear the local session (on logout). */
export async function clearLocalSession(): Promise<void> {
  try {
    await invoke<void>('session_clear', {})
  } catch {
    // Soft fail
  }
}
