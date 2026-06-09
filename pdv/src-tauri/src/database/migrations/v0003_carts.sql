-- ============================================================
-- Migration 0003 — carrinhos persistentes (on-hold)
-- Permite pausar um atendimento e retomar depois.
-- O carrinho em memória (Zustand) vira SQL só ao pausar.
-- ============================================================

CREATE TABLE IF NOT EXISTS carts (
    uuid          TEXT NOT NULL PRIMARY KEY,
    tenant_uuid   TEXT NOT NULL,
    store_uuid    TEXT NOT NULL,
    user_uuid     TEXT NOT NULL,
    customer_uuid TEXT REFERENCES customers(uuid) ON DELETE SET NULL,
    -- active | on_hold | converted | cancelled
    status        TEXT NOT NULL DEFAULT 'on_hold',
    notes         TEXT,
    created_at    TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at    TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_carts_tenant_status ON carts(tenant_uuid, status);
CREATE INDEX IF NOT EXISTS idx_carts_user          ON carts(user_uuid, status);

CREATE TABLE IF NOT EXISTS cart_items (
    uuid         TEXT    NOT NULL PRIMARY KEY,
    cart_uuid    TEXT    NOT NULL REFERENCES carts(uuid) ON DELETE CASCADE,
    product_uuid TEXT    NOT NULL,
    -- NULL se o produto não tem variantes
    variant_uuid TEXT    REFERENCES product_variants(uuid) ON DELETE SET NULL,
    product_name TEXT    NOT NULL,
    product_sku  TEXT    NOT NULL,
    quantity     INTEGER NOT NULL DEFAULT 1,
    unit_price   INTEGER NOT NULL,
    discount     INTEGER NOT NULL DEFAULT 0,
    total        INTEGER NOT NULL,
    created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at   TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_cart_items_cart ON cart_items(cart_uuid);
