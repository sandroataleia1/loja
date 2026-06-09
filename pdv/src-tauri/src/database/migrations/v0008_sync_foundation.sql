-- ============================================================
-- Migration 0008 — Sync foundation & session persistence
-- Adds: local_session, sync_status, sync_log, cash_movements
-- Enhances: sync_queue (status, endpoint, method, priority, max_attempts)
-- ============================================================

-- ── Auth / Session singleton ────────────────────────────────────────────────
-- One row (id=1) per device. Replaces localStorage-based session.

CREATE TABLE IF NOT EXISTS local_session (
    id              INTEGER PRIMARY KEY DEFAULT 1,
    user_uuid       TEXT NOT NULL DEFAULT '',
    user_name       TEXT NOT NULL DEFAULT '',
    token           TEXT NOT NULL DEFAULT '',
    tenant_uuid     TEXT NOT NULL DEFAULT '',
    store_uuid      TEXT NOT NULL DEFAULT '',
    session_uuid    TEXT,
    register_uuid   TEXT,
    opening_amount  INTEGER NOT NULL DEFAULT 0,
    opened_at       TEXT,
    expires_at      TEXT,
    created_at      TEXT NOT NULL DEFAULT (datetime('now'))
);

-- ── Sync queue enhancements ─────────────────────────────────────────────────
-- The original sync_queue used UUID PK + attempts only.
-- We add richer columns for the new sync engine.

ALTER TABLE sync_queue ADD COLUMN status       TEXT NOT NULL DEFAULT 'pending';
ALTER TABLE sync_queue ADD COLUMN max_attempts INTEGER NOT NULL DEFAULT 5;
ALTER TABLE sync_queue ADD COLUMN priority     INTEGER NOT NULL DEFAULT 0;
ALTER TABLE sync_queue ADD COLUMN endpoint     TEXT NOT NULL DEFAULT '/api/v1/sales';
ALTER TABLE sync_queue ADD COLUMN method       TEXT NOT NULL DEFAULT 'POST';
ALTER TABLE sync_queue ADD COLUMN processed_at TEXT;
ALTER TABLE sync_queue ADD COLUMN synced_at    TEXT;

-- Back-fill status for existing rows: rows with attempts >= 5 are 'failed', rest 'pending'.
UPDATE sync_queue SET status = CASE WHEN attempts >= 5 THEN 'failed' ELSE 'pending' END;

-- New index on (status, priority) for the hot query path
CREATE INDEX IF NOT EXISTS idx_sync_queue_status_priority
    ON sync_queue(status, priority DESC, created_at ASC);

-- ── Sync status singleton ───────────────────────────────────────────────────
-- One row (id=1) tracks the global sync state visible to the UI.

CREATE TABLE IF NOT EXISTS sync_status (
    id                   INTEGER PRIMARY KEY DEFAULT 1,
    last_sync_at         TEXT,
    last_sync_ok         INTEGER NOT NULL DEFAULT 1,
    last_error           TEXT,
    pending_count        INTEGER NOT NULL DEFAULT 0,
    failed_count         INTEGER NOT NULL DEFAULT 0,
    is_syncing           INTEGER NOT NULL DEFAULT 0,
    catalog_synced_at    TEXT,
    customers_synced_at  TEXT
);

INSERT OR IGNORE INTO sync_status (id) VALUES (1);

-- ── Sync log ────────────────────────────────────────────────────────────────
-- Append-only audit trail for every sync attempt.

CREATE TABLE IF NOT EXISTS sync_log (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    sync_queue_uuid TEXT REFERENCES sync_queue(uuid) ON DELETE SET NULL,
    entity_type     TEXT NOT NULL,
    entity_uuid     TEXT NOT NULL,
    operation       TEXT NOT NULL,
    status          TEXT NOT NULL,  -- 'success' | 'error'
    response_status INTEGER,        -- HTTP status code
    response_body   TEXT,
    error_message   TEXT,
    duration_ms     INTEGER,
    synced_at       TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_sync_log_entity ON sync_log(entity_type, entity_uuid);
CREATE INDEX IF NOT EXISTS idx_sync_log_date   ON sync_log(synced_at DESC);

-- ── Cash movements ──────────────────────────────────────────────────────────
-- Local ledger for cash-register operations (withdrawals, supplies, opening, closing).

CREATE TABLE IF NOT EXISTS cash_movements (
    uuid         TEXT NOT NULL PRIMARY KEY,
    sale_uuid    TEXT REFERENCES sales(uuid) ON DELETE SET NULL,
    type         TEXT NOT NULL,     -- 'sale' | 'withdrawal' | 'supply' | 'opening' | 'closing'
    amount_cents INTEGER NOT NULL,
    description  TEXT,
    created_at   TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_cash_movements_created ON cash_movements(created_at DESC);
