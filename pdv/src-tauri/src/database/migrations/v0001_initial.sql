-- ============================================================
-- Migration 0001 — schema inicial
-- Prices in cents (integer). UUIDs as TEXT.
-- ============================================================

CREATE TABLE IF NOT EXISTS tenants (
    uuid       TEXT NOT NULL PRIMARY KEY,
    name       TEXT NOT NULL,
    document   TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
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
    stock         INTEGER NOT NULL DEFAULT 0,
    status        TEXT    NOT NULL DEFAULT 'active',
    created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at    TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_products_sku      ON products(tenant_uuid, sku);
CREATE INDEX        IF NOT EXISTS idx_products_tenant  ON products(tenant_uuid);
CREATE INDEX        IF NOT EXISTS idx_products_status  ON products(tenant_uuid, status);
CREATE INDEX        IF NOT EXISTS idx_products_barcode ON products(barcode) WHERE barcode IS NOT NULL;

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

CREATE INDEX IF NOT EXISTS idx_customers_tenant ON customers(tenant_uuid);

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

CREATE INDEX IF NOT EXISTS idx_sales_tenant   ON sales(tenant_uuid);
CREATE INDEX IF NOT EXISTS idx_sales_created  ON sales(tenant_uuid, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_sales_unsynced ON sales(synced_at) WHERE synced_at IS NULL;

CREATE TABLE IF NOT EXISTS sale_items (
    uuid         TEXT    NOT NULL PRIMARY KEY,
    sale_uuid    TEXT    NOT NULL REFERENCES sales(uuid) ON DELETE CASCADE,
    product_uuid TEXT    NOT NULL,
    product_name TEXT    NOT NULL,
    product_sku  TEXT    NOT NULL,
    quantity     INTEGER NOT NULL DEFAULT 1,
    unit_price   INTEGER NOT NULL,
    discount     INTEGER NOT NULL DEFAULT 0,
    total        INTEGER NOT NULL,
    created_at   TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_sale_items_sale ON sale_items(sale_uuid);

CREATE TABLE IF NOT EXISTS payments (
    uuid       TEXT    NOT NULL PRIMARY KEY,
    sale_uuid  TEXT    NOT NULL REFERENCES sales(uuid) ON DELETE CASCADE,
    method     TEXT    NOT NULL,
    amount     INTEGER NOT NULL,
    reference  TEXT,
    created_at TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_payments_sale ON payments(sale_uuid);

CREATE TABLE IF NOT EXISTS sync_queue (
    uuid        TEXT    NOT NULL PRIMARY KEY,
    entity_type TEXT    NOT NULL,
    entity_uuid TEXT    NOT NULL,
    operation   TEXT    NOT NULL,
    payload     TEXT    NOT NULL,
    attempts    INTEGER NOT NULL DEFAULT 0,
    last_error  TEXT,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_sync_pending ON sync_queue(attempts, created_at);

-- Seed: default tenant/store/user so the app works immediately
INSERT OR IGNORE INTO tenants (uuid, name)
VALUES ('00000000-0000-4000-8000-000000000001', 'Loja Fashion');

INSERT OR IGNORE INTO stores (uuid, tenant_uuid, name, code)
VALUES ('00000000-0000-4000-8000-000000000002',
        '00000000-0000-4000-8000-000000000001',
        'Loja Principal', 'LP01');

INSERT OR IGNORE INTO users (uuid, tenant_uuid, store_uuid, name, email, role)
VALUES ('00000000-0000-4000-8000-000000000003',
        '00000000-0000-4000-8000-000000000001',
        '00000000-0000-4000-8000-000000000002',
        'Administrador', 'admin@loja.local', 'admin');
