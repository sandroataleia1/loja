// ---- Sales ----------------------------------------------------------

pub const FIND: &str = "
    SELECT s.uuid, s.tenant_uuid, s.store_uuid, s.customer_uuid, c.name,
           s.user_uuid, s.status, s.subtotal, s.discount, s.total,
           s.notes, s.completed_at, s.synced_at, s.created_at
    FROM sales s
    LEFT JOIN customers c ON c.uuid = s.customer_uuid
    WHERE s.uuid = ?1 AND s.tenant_uuid = ?2";

/// ?1=tenant ?2=limit ?3=offset
pub const LIST: &str = "
    SELECT s.uuid, s.tenant_uuid, s.store_uuid, s.customer_uuid, c.name,
           s.user_uuid, s.status, s.subtotal, s.discount, s.total,
           s.notes, s.completed_at, s.synced_at, s.created_at
    FROM sales s
    LEFT JOIN customers c ON c.uuid = s.customer_uuid
    WHERE s.tenant_uuid = ?1
    ORDER BY s.created_at DESC
    LIMIT ?2 OFFSET ?3";

/// ?1=uuid ?2=tenant ?3=store ?4=customer|NULL ?5=user
/// ?6=subtotal ?7=discount ?8=total ?9=notes|NULL ?10=now
pub const INSERT: &str = "
    INSERT INTO sales
        (uuid, tenant_uuid, store_uuid, customer_uuid, user_uuid,
         status, subtotal, discount, total, notes, completed_at,
         created_at, updated_at)
    VALUES (?1,?2,?3,?4,?5,'completed',?6,?7,?8,?9,?10,?10,?10)";

// ---- Sale Items -----------------------------------------------------

pub const FIND_ITEMS: &str = "
    SELECT uuid, sale_uuid, product_uuid, variant_uuid,
           product_name, product_sku,
           name_snapshot, sku_snapshot, attributes_snapshot, cost_snapshot,
           quantity, unit_price, discount, total
    FROM sale_items
    WHERE sale_uuid = ?1";

/// ?1=uuid ?2=sale_uuid ?3=product_uuid ?4=variant_uuid|NULL
/// ?5=product_name ?6=product_sku ?7=name_snapshot ?8=sku_snapshot
/// ?9=attributes_snapshot ?10=cost_snapshot|NULL
/// ?11=quantity ?12=unit_price ?13=discount ?14=total ?15=now
pub const INSERT_ITEM: &str = "
    INSERT INTO sale_items
        (uuid, sale_uuid, product_uuid, variant_uuid,
         product_name, product_sku,
         name_snapshot, sku_snapshot, attributes_snapshot, cost_snapshot,
         quantity, unit_price, discount, total, created_at)
    VALUES (?1,?2,?3,?4,?5,?6,?7,?8,?9,?10,?11,?12,?13,?14,?15)";

// ---- Payments -------------------------------------------------------

pub const FIND_PAYMENTS: &str = "
    SELECT uuid, sale_uuid, method, amount, reference
    FROM payments
    WHERE sale_uuid = ?1";

/// ?1=uuid ?2=sale_uuid ?3=method ?4=amount ?5=reference|NULL ?6=now
pub const INSERT_PAYMENT: &str = "
    INSERT INTO payments (uuid, sale_uuid, method, amount, reference, created_at)
    VALUES (?1,?2,?3,?4,?5,?6)";

// ---- Sync Queue -----------------------------------------------------

/// ?1=uuid ?2=entity_type ?3=entity_uuid ?4=operation ?5=payload(json) ?6=now
pub const INSERT_SYNC_ENTRY: &str = "
    INSERT INTO sync_queue
        (uuid, entity_type, entity_uuid, operation, payload, created_at)
    VALUES (?1,?2,?3,?4,?5,?6)";
