-- ============================================================
-- Migration 0002 — variantes de produto
-- Fashion: tamanho (P/M/G/GG), cor, por variante
-- ============================================================

CREATE TABLE IF NOT EXISTS product_variants (
    uuid         TEXT    NOT NULL PRIMARY KEY,
    tenant_uuid  TEXT    NOT NULL,
    product_uuid TEXT    NOT NULL REFERENCES products(uuid) ON DELETE CASCADE,
    sku          TEXT    NOT NULL,
    barcode      TEXT,
    -- JSON: {"size":"M","color":"Preto"} — armazenado como TEXT
    attributes   TEXT    NOT NULL DEFAULT '{}',
    -- NULL = herda preço do produto pai
    price        INTEGER,
    cost         INTEGER,
    stock        INTEGER NOT NULL DEFAULT 0,
    is_active    INTEGER NOT NULL DEFAULT 1,
    created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at   TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_product_variants_sku
    ON product_variants(tenant_uuid, sku);
CREATE INDEX IF NOT EXISTS idx_product_variants_product
    ON product_variants(product_uuid);
CREATE INDEX IF NOT EXISTS idx_product_variants_barcode
    ON product_variants(barcode) WHERE barcode IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_product_variants_active
    ON product_variants(product_uuid, is_active);
