-- ============================================================
-- Migration 0007 — snapshots históricos em sale_items e cart_items
-- Vendas devem ser imutáveis: qualquer mudança futura no catálogo
-- não pode alterar o histórico de vendas.
-- ============================================================

-- ── sale_items ────────────────────────────────────────────────────────
ALTER TABLE sale_items ADD COLUMN variant_uuid       TEXT REFERENCES product_variants(uuid) ON DELETE SET NULL;
ALTER TABLE sale_items ADD COLUMN name_snapshot      TEXT NOT NULL DEFAULT '';
ALTER TABLE sale_items ADD COLUMN sku_snapshot       TEXT NOT NULL DEFAULT '';
ALTER TABLE sale_items ADD COLUMN attributes_snapshot TEXT NOT NULL DEFAULT '{}';
ALTER TABLE sale_items ADD COLUMN cost_snapshot      INTEGER;

-- Popula snapshots a partir das colunas legadas (retrocompatibilidade)
UPDATE sale_items SET
    name_snapshot = product_name,
    sku_snapshot  = product_sku
WHERE name_snapshot = '';

-- ── cart_items ────────────────────────────────────────────────────────
ALTER TABLE cart_items ADD COLUMN name_snapshot      TEXT NOT NULL DEFAULT '';
ALTER TABLE cart_items ADD COLUMN sku_snapshot       TEXT NOT NULL DEFAULT '';
ALTER TABLE cart_items ADD COLUMN attributes_snapshot TEXT NOT NULL DEFAULT '{}';
ALTER TABLE cart_items ADD COLUMN cost_snapshot      INTEGER;

UPDATE cart_items SET
    name_snapshot = product_name,
    sku_snapshot  = product_sku
WHERE name_snapshot = '';
