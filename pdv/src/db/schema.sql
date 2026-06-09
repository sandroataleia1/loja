-- ─────────────────────────────────────────────────────────────────────────────
-- PDV Local Database Schema — Reference Copy
-- Version: 1.0.0  (sync_foundation)
--
-- IMPORTANT: This file is a REFERENCE ONLY.
-- The live schema is managed by Rust migrations in:
--   src-tauri/src/database/migrations/
--
-- Do NOT run this file directly. Use the Tauri app — on startup, Rust
-- applies any unapplied migrations automatically (see migrations/mod.rs).
-- ─────────────────────────────────────────────────────────────────────────────

PRAGMA journal_mode = WAL;
PRAGMA foreign_keys = ON;

-- ── Tenants / Stores / Users (v0001) ─────────────────────────────────────────

CREATE TABLE IF NOT EXISTS tenants (
    uuid        TEXT NOT NULL PRIMARY KEY,
    name        TEXT NOT NULL,
    document    TEXT,
    created_at  TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at  TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS stores (
    uuid        TEXT NOT NULL PRIMARY KEY,
    tenant_uuid TEXT NOT NULL REFERENCES tenants(uuid),
    name        TEXT NOT NULL,
    code        TEXT NOT NULL,
    address     TEXT,
    created_at  TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at  TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS users (
    uuid        TEXT NOT NULL PRIMARY KEY,
    tenant_uuid TEXT NOT NULL REFERENCES tenants(uuid),
    store_uuid  TEXT REFERENCES stores(uuid),
    name        TEXT NOT NULL,
    email       TEXT NOT NULL,
    pin         TEXT,
    role        TEXT NOT NULL DEFAULT 'operator',
    is_active   INTEGER NOT NULL DEFAULT 1,
    created_at  TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at  TEXT NOT NULL DEFAULT (datetime('now'))
);

-- ── Catalog (v0001 + v0002 + v0006) ──────────────────────────────────────────

CREATE TABLE IF NOT EXISTS product_categories (
    uuid        TEXT NOT NULL PRIMARY KEY,
    tenant_uuid TEXT NOT NULL,
    name        TEXT NOT NULL,
    created_at  TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at  TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS products (
    uuid          TEXT    NOT NULL PRIMARY KEY,
    tenant_uuid   TEXT    NOT NULL,
    category_uuid TEXT    REFERENCES product_categories(uuid) ON DELETE SET NULL,
    name          TEXT    NOT NULL,
    sku           TEXT    NOT NULL,
    barcode       TEXT,
    description   TEXT,
    price         INTEGER NOT NULL DEFAULT 0,
    cost          INTEGER,
    status        TEXT    NOT NULL DEFAULT 'active',
    created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at    TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS product_variants (
    uuid         TEXT    NOT NULL PRIMARY KEY,
    tenant_uuid  TEXT    NOT NULL,
    product_uuid TEXT    NOT NULL REFERENCES products(uuid) ON DELETE CASCADE,
    sku          TEXT    NOT NULL,
    barcode      TEXT,
    attributes   TEXT    NOT NULL DEFAULT '{}',
    price        INTEGER,
    cost         INTEGER,
    stock        INTEGER NOT NULL DEFAULT 0,
    is_active    INTEGER NOT NULL DEFAULT 1,
    created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at   TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ── Customers (v0001) ────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS customers (
    uuid        TEXT NOT NULL PRIMARY KEY,
    tenant_uuid TEXT NOT NULL,
    name        TEXT NOT NULL,
    cpf         TEXT,
    email       TEXT,
    phone       TEXT,
    created_at  TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at  TEXT NOT NULL DEFAULT (datetime('now'))
);

-- ── Sales (v0001 + v0007) ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS cash_registers (
    uuid           TEXT    NOT NULL PRIMARY KEY,
    store_uuid     TEXT    NOT NULL REFERENCES stores(uuid),
    name           TEXT    NOT NULL,
    is_open        INTEGER NOT NULL DEFAULT 0,
    opened_at      TEXT,
    closed_at      TEXT,
    opening_amount INTEGER NOT NULL DEFAULT 0,
    closing_amount INTEGER,
    created_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at     TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS sales (
    uuid               TEXT    NOT NULL PRIMARY KEY,
    tenant_uuid        TEXT    NOT NULL,
    store_uuid         TEXT    NOT NULL,
    cash_register_uuid TEXT    REFERENCES cash_registers(uuid),
    customer_uuid      TEXT    REFERENCES customers(uuid) ON DELETE SET NULL,
    user_uuid          TEXT    NOT NULL,
    status             TEXT    NOT NULL DEFAULT 'completed',
    subtotal           INTEGER NOT NULL DEFAULT 0,
    discount           INTEGER NOT NULL DEFAULT 0,
    total              INTEGER NOT NULL DEFAULT 0,
    notes              TEXT,
    completed_at       TEXT,
    cancelled_at       TEXT,
    synced_at          TEXT,
    created_at         TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at         TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS sale_items (
    uuid                 TEXT    NOT NULL PRIMARY KEY,
    sale_uuid            TEXT    NOT NULL REFERENCES sales(uuid) ON DELETE CASCADE,
    product_uuid         TEXT    NOT NULL,
    variant_uuid         TEXT    REFERENCES product_variants(uuid) ON DELETE SET NULL,
    product_name         TEXT    NOT NULL,
    product_sku          TEXT    NOT NULL,
    name_snapshot        TEXT    NOT NULL DEFAULT '',
    sku_snapshot         TEXT    NOT NULL DEFAULT '',
    attributes_snapshot  TEXT    NOT NULL DEFAULT '{}',
    cost_snapshot        INTEGER,
    quantity             INTEGER NOT NULL DEFAULT 1,
    unit_price           INTEGER NOT NULL,
    discount             INTEGER NOT NULL DEFAULT 0,
    total                INTEGER NOT NULL,
    created_at           TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS payments (
    uuid       TEXT    NOT NULL PRIMARY KEY,
    sale_uuid  TEXT    NOT NULL REFERENCES sales(uuid) ON DELETE CASCADE,
    method     TEXT    NOT NULL,  -- cash | card_debit | card_credit | pix | voucher
    amount     INTEGER NOT NULL,
    reference  TEXT,
    created_at TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ── Carts on-hold (v0003 + v0007) ────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS carts (
    uuid          TEXT NOT NULL PRIMARY KEY,
    tenant_uuid   TEXT NOT NULL,
    store_uuid    TEXT NOT NULL,
    user_uuid     TEXT NOT NULL,
    customer_uuid TEXT REFERENCES customers(uuid) ON DELETE SET NULL,
    status        TEXT NOT NULL DEFAULT 'on_hold',
    notes         TEXT,
    created_at    TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at    TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS cart_items (
    uuid              TEXT    NOT NULL PRIMARY KEY,
    cart_uuid         TEXT    NOT NULL REFERENCES carts(uuid) ON DELETE CASCADE,
    product_uuid      TEXT    NOT NULL,
    variant_uuid      TEXT    REFERENCES product_variants(uuid) ON DELETE SET NULL,
    product_name      TEXT    NOT NULL,
    product_sku       TEXT    NOT NULL,
    name_snapshot     TEXT    NOT NULL DEFAULT '',
    sku_snapshot      TEXT    NOT NULL DEFAULT '',
    attributes_snapshot TEXT  NOT NULL DEFAULT '{}',
    cost_snapshot     INTEGER,
    quantity          INTEGER NOT NULL DEFAULT 1,
    unit_price        INTEGER NOT NULL,
    discount          INTEGER NOT NULL DEFAULT 0,
    total             INTEGER NOT NULL,
    created_at        TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at        TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ── Sync Queue (v0001 + v0008) ────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS sync_queue (
    uuid        TEXT    NOT NULL PRIMARY KEY,
    entity_type TEXT    NOT NULL,
    entity_uuid TEXT    NOT NULL,
    operation   TEXT    NOT NULL,  -- create | update | delete | cancel
    payload     TEXT    NOT NULL,  -- JSON
    status      TEXT    NOT NULL DEFAULT 'pending', -- pending | synced | failed
    attempts    INTEGER NOT NULL DEFAULT 0,
    max_attempts INTEGER NOT NULL DEFAULT 5,
    priority    INTEGER NOT NULL DEFAULT 0,
    endpoint    TEXT    NOT NULL DEFAULT '/api/v1/sales',
    method      TEXT    NOT NULL DEFAULT 'POST',
    last_error  TEXT,
    processed_at TEXT,
    synced_at   TEXT,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ── Sync Foundation (v0008) ───────────────────────────────────────────────────

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

CREATE TABLE IF NOT EXISTS sync_status (
    id                  INTEGER PRIMARY KEY DEFAULT 1,
    last_sync_at        TEXT,
    last_sync_ok        INTEGER NOT NULL DEFAULT 1,
    last_error          TEXT,
    pending_count       INTEGER NOT NULL DEFAULT 0,
    failed_count        INTEGER NOT NULL DEFAULT 0,
    is_syncing          INTEGER NOT NULL DEFAULT 0,
    catalog_synced_at   TEXT,
    customers_synced_at TEXT
);

CREATE TABLE IF NOT EXISTS sync_log (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    sync_queue_uuid  TEXT REFERENCES sync_queue(uuid) ON DELETE SET NULL,
    entity_type      TEXT NOT NULL,
    entity_uuid      TEXT NOT NULL,
    operation        TEXT NOT NULL,
    status           TEXT NOT NULL,  -- success | error
    response_status  INTEGER,
    response_body    TEXT,
    error_message    TEXT,
    duration_ms      INTEGER,
    synced_at        TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS cash_movements (
    uuid         TEXT    NOT NULL PRIMARY KEY,
    sale_uuid    TEXT    REFERENCES sales(uuid) ON DELETE SET NULL,
    type         TEXT    NOT NULL,  -- sale | withdrawal | supply | opening | closing
    amount_cents INTEGER NOT NULL,
    description  TEXT,
    created_at   TEXT    NOT NULL DEFAULT (datetime('now'))
);
