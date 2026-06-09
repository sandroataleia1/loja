// ---- Products -------------------------------------------------------

pub const FIND: &str = "
    SELECT p.uuid, p.tenant_uuid, p.category_uuid, c.name AS category_name,
           p.name, p.sku, p.barcode, p.description, p.price, p.cost,
           COALESCE((
               SELECT SUM(v.stock)
               FROM product_variants v
               WHERE v.product_uuid = p.uuid AND v.is_active = 1
           ), 0) AS stock,
           p.status, p.created_at, p.updated_at
    FROM products p
    LEFT JOIN product_categories c ON c.uuid = p.category_uuid
    WHERE p.uuid = ?1 AND p.tenant_uuid = ?2";

/// ?1=tenant ?2=status ?3=pattern|NULL ?4=limit ?5=offset
pub const LIST: &str = "
    SELECT p.uuid, p.tenant_uuid, p.category_uuid, c.name AS category_name,
           p.name, p.sku, p.barcode, p.description, p.price, p.cost,
           COALESCE((
               SELECT SUM(v.stock)
               FROM product_variants v
               WHERE v.product_uuid = p.uuid AND v.is_active = 1
           ), 0) AS stock,
           p.status, p.created_at, p.updated_at
    FROM products p
    LEFT JOIN product_categories c ON c.uuid = p.category_uuid
    WHERE p.tenant_uuid = ?1
      AND p.status      = ?2
      AND (?3 IS NULL
           OR LOWER(p.name) LIKE ?3
           OR LOWER(p.sku)  LIKE ?3
           OR p.barcode      LIKE ?3)
    ORDER BY p.name ASC
    LIMIT ?4 OFFSET ?5";

/// ?1=uuid ?2=tenant_uuid ?3=category_uuid ?4=name ?5=sku ?6=barcode
/// ?7=description ?8=price ?9=cost ?10=status ?11=now (created_at = updated_at)
pub const INSERT: &str = "
    INSERT INTO products
        (uuid, tenant_uuid, category_uuid, name, sku, barcode,
         description, price, cost, status, created_at, updated_at)
    VALUES (?1,?2,?3,?4,?5,?6,?7,?8,?9,?10,?11,?11)";

/// ?1=uuid ?2=tenant ?3..?10=COALESCE fields ?11=now
pub const UPDATE: &str = "
    UPDATE products SET
        category_uuid = COALESCE(?3, category_uuid),
        name          = COALESCE(?4, name),
        sku           = COALESCE(?5, sku),
        barcode       = COALESCE(?6, barcode),
        description   = COALESCE(?7, description),
        price         = COALESCE(?8, price),
        cost          = COALESCE(?9, cost),
        status        = COALESCE(?10, status),
        updated_at    = ?11
    WHERE uuid = ?1 AND tenant_uuid = ?2";

/// Soft-delete: status='inactive'. ?1=uuid ?2=tenant ?3=now
pub const SOFT_DELETE: &str = "
    UPDATE products
    SET status = 'inactive', updated_at = ?3
    WHERE uuid = ?1 AND tenant_uuid = ?2";

// ---- Product Variants -----------------------------------------------

pub const FIND_VARIANT: &str = "
    SELECT uuid, tenant_uuid, product_uuid, sku, barcode, attributes,
           price, cost, stock, is_active, created_at, updated_at
    FROM product_variants
    WHERE uuid = ?1 AND tenant_uuid = ?2";

/// ?1=product_uuid ?2=tenant
pub const LIST_VARIANTS: &str = "
    SELECT uuid, tenant_uuid, product_uuid, sku, barcode, attributes,
           price, cost, stock, is_active, created_at, updated_at
    FROM product_variants
    WHERE product_uuid = ?1 AND tenant_uuid = ?2
    ORDER BY sku ASC";

/// ?1=uuid ?2=tenant ?3=product_uuid ?4=sku ?5=barcode ?6=attributes(json)
/// ?7=price|NULL ?8=cost|NULL ?9=stock ?10=now
pub const INSERT_VARIANT: &str = "
    INSERT INTO product_variants
        (uuid, tenant_uuid, product_uuid, sku, barcode, attributes,
         price, cost, stock, is_active, created_at, updated_at)
    VALUES (?1,?2,?3,?4,?5,?6,?7,?8,?9,1,?10,?10)";

/// ?1=uuid ?2=tenant ?3=sku ?4=barcode ?5=attrs ?6=price ?7=cost ?8=stock ?9=is_active ?10=now
pub const UPDATE_VARIANT: &str = "
    UPDATE product_variants SET
        sku        = COALESCE(?3, sku),
        barcode    = COALESCE(?4, barcode),
        attributes = COALESCE(?5, attributes),
        price      = COALESCE(?6, price),
        cost       = COALESCE(?7, cost),
        stock      = COALESCE(?8, stock),
        is_active  = COALESCE(?9, is_active),
        updated_at = ?10
    WHERE uuid = ?1 AND tenant_uuid = ?2";

/// Adjust variant stock by signed delta (negative=decrement). ?1=delta ?2=variant_uuid ?3=now
pub const ADJUST_VARIANT_STOCK: &str = "
    UPDATE product_variants
    SET stock = MAX(0, stock + ?1), updated_at = ?3
    WHERE uuid = ?2";

// ---- Catalog (busca em memória / POS) --------------------------------

/// Carrega todos os produtos ativos com variantes como JSON array.
/// Stock calculado a partir das variantes ativas.
/// ?1=tenant
pub const CATALOG_LOAD_ALL: &str = "
    SELECT p.uuid, p.category_uuid, c.name AS category_name,
           p.name, p.sku, p.barcode, p.description, p.price,
           COALESCE((
               SELECT SUM(v2.stock)
               FROM product_variants v2
               WHERE v2.product_uuid = p.uuid AND v2.is_active = 1
           ), 0) AS stock,
           p.status,
           COALESCE((
               SELECT json_group_array(json_object(
                   'uuid',       v.uuid,
                   'sku',        v.sku,
                   'barcode',    v.barcode,
                   'price',      v.price,
                   'stock',      v.stock,
                   'is_active',  v.is_active,
                   'attributes', json(v.attributes)
               ))
               FROM product_variants v
               WHERE v.product_uuid = p.uuid AND v.is_active = 1
               ORDER BY v.sku ASC
           ), '[]') AS variants
    FROM products p
    LEFT JOIN product_categories c ON c.uuid = p.category_uuid
    WHERE p.tenant_uuid = ?1 AND p.status = 'active'
    ORDER BY p.name ASC";

/// Busca por barcode em produtos E variantes.
/// ?1=barcode ?2=tenant
pub const CATALOG_BY_BARCODE: &str = "
    SELECT p.uuid, p.category_uuid, c.name AS category_name,
           p.name, p.sku, p.barcode, p.description, p.price,
           COALESCE((
               SELECT SUM(v2.stock)
               FROM product_variants v2
               WHERE v2.product_uuid = p.uuid AND v2.is_active = 1
           ), 0) AS stock,
           p.status,
           COALESCE((
               SELECT json_group_array(json_object(
                   'uuid',       v.uuid,
                   'sku',        v.sku,
                   'barcode',    v.barcode,
                   'price',      v.price,
                   'stock',      v.stock,
                   'is_active',  v.is_active,
                   'attributes', json(v.attributes)
               ))
               FROM product_variants v
               WHERE v.product_uuid = p.uuid AND v.is_active = 1
               ORDER BY v.sku ASC
           ), '[]') AS variants,
           (SELECT pv.uuid FROM product_variants pv
            WHERE pv.product_uuid = p.uuid AND pv.barcode = ?1 AND pv.is_active = 1
            LIMIT 1) AS matched_variant_uuid
    FROM products p
    LEFT JOIN product_categories c ON c.uuid = p.category_uuid
    WHERE p.tenant_uuid = ?2 AND p.status = 'active'
      AND (p.barcode = ?1
           OR EXISTS (
               SELECT 1 FROM product_variants pv2
               WHERE pv2.product_uuid = p.uuid AND pv2.barcode = ?1 AND pv2.is_active = 1
           ))
    LIMIT 1";

/// ?1=tenant
pub const CATALOG_CATEGORIES: &str = "
    SELECT uuid, name
    FROM product_categories
    WHERE tenant_uuid = ?1
    ORDER BY name ASC";
