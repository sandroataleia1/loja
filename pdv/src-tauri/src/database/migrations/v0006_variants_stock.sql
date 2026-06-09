-- ============================================================
-- Migration 0006 — stock migra inteiramente para product_variants
-- Todo produto deve ter pelo menos uma variante.
-- Para produtos sem variante, cria-se uma variante padrão
-- herdando SKU, barcode, cost e stock do próprio produto.
-- Após isso, products.stock é removido.
-- ============================================================

INSERT OR IGNORE INTO product_variants
    (uuid, tenant_uuid, product_uuid, sku, barcode, attributes,
     price, cost, stock, is_active, created_at, updated_at)
SELECT
    lower(hex(randomblob(4))) || '-' ||
    lower(hex(randomblob(2))) || '-4' ||
    substr(lower(hex(randomblob(2))),2) || '-' ||
    substr('89ab', abs(random()) % 4 + 1, 1) ||
    substr(lower(hex(randomblob(2))),2) || '-' ||
    lower(hex(randomblob(6))),
    p.tenant_uuid,
    p.uuid,
    p.sku,
    p.barcode,
    '{}',
    NULL,
    p.cost,
    p.stock,
    1,
    datetime('now'),
    datetime('now')
FROM products p
WHERE NOT EXISTS (
    SELECT 1 FROM product_variants v WHERE v.product_uuid = p.uuid
);

-- Agora que todo produto tem pelo menos uma variante, stock é das variantes.
ALTER TABLE products DROP COLUMN stock;
