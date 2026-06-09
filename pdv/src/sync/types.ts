// ============================================================
// Sync Engine — tipos centrais
// Toda a camada de sincronização usa estas definições.
// ============================================================

/** Versão atual do envelope de evento. Incrementar ao mudar o shape. */
export const SYNC_SCHEMA_VERSION = 1 as const

export type SyncOperation  = 'create' | 'update' | 'delete' | 'cancel'
export type SyncEntityType = 'sale' | 'product' | 'product_variant' | 'customer'
export type SyncStatus     = 'idle' | 'running' | 'error' | 'offline'
export type LogLevel       = 'debug' | 'info' | 'warn' | 'error'

/**
 * Envelope versionado que envolve qualquer evento de sincronização.
 * Armazenado como JSON em sync_queue.payload (lado Rust).
 *
 * Campos do envelope são planos — o payload específico do domínio
 * fica em `payload`. Versionar incrementalmente = adicionar campos
 * opcionais no payload OU incrementar `v` com migração.
 */
export interface SyncEvent<TPayload = unknown> {
  /** Versão do schema. Permite upcasting no futuro. */
  v:           number
  /** Mesmo UUID que sync_queue.uuid — chave de idempotência no servidor. */
  id:          string
  entity_type: SyncEntityType
  entity_uuid: string
  operation:   SyncOperation
  tenant_uuid: string
  /** Quando o evento ocorreu localmente (ISO 8601 UTC). */
  occurred_at: string
  payload:     TPayload
}

/**
 * Linha bruta da tabela sync_queue (via Tauri command sync_next_batch).
 * `payload` é JSON string do objeto de domínio armazenado pelo Rust.
 */
export interface SyncQueueEntry {
  uuid:        string
  entity_type: string
  entity_uuid: string
  operation:   string
  payload:     string  // JSON com dados do domínio
  attempts:    number
  last_error:  string | null
  created_at:  string  // ISO 8601
}

/** Resultado de uma tentativa de sync de um entry. */
export type SyncAttemptResult =
  | { ok: true }
  | { ok: false; retryable: boolean; error: string }

/** Configuração da política de retry. */
export interface RetryPolicyConfig {
  /** Número máximo de tentativas antes de parar. */
  maxAttempts:  number
  /** Delay base em ms para a primeira retry. */
  baseDelayMs:  number
  /** Delay máximo em ms (teto do backoff). */
  maxDelayMs:   number
  /** Multiplicador do backoff exponencial. */
  multiplier:   number
  /** Fator de jitter (0–1). Ex: 0.15 = ±15% de variação aleatória. */
  jitterFactor: number
}

/** Configuração global do SyncEngine. */
export interface SyncEngineConfig {
  /** Intervalo de polling quando online (ms). */
  pollIntervalMs: number
  /** Máximo de entries por ciclo. */
  batchSize:      number
  retryPolicy:    RetryPolicyConfig
  logLevel:       LogLevel
}

/** Estado observável do engine — refletido no syncStore. */
export interface SyncState {
  status:     SyncStatus
  isOnline:   boolean
  pending:    number
  lastSyncAt: string | null
  lastError:  string | null
}

/** Entrada de log estruturado — pode ser persistida no SQLite futuramente. */
export interface SyncLogEntry {
  level:     LogLevel
  message:   string
  context?:  Record<string, unknown>
  timestamp: string
}
