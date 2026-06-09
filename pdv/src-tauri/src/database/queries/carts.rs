// ---- Carts ----------------------------------------------------------

pub const FIND: &str = "
    SELECT uuid, tenant_uuid, store_uuid, user_uuid, customer_uuid,
           status, notes, created_at, updated_at
    FROM carts
    WHERE uuid = ?1 AND tenant_uuid = ?2";

/// Active on-hold carts for a tenant, most recently updated first.
/// ?1=tenant
pub const LIST_ON_HOLD: &str = "
    SELECT uuid, tenant_uuid, store_uuid, user_uuid, customer_uuid,
           status, notes, created_at, updated_at
    FROM carts
    WHERE tenant_uuid = ?1 AND status = 'on_hold'
    ORDER BY updated_at DESC";

/// ?1=uuid ?2=tenant ?3=store ?4=user ?5=customer|NULL ?6=notes|NULL ?7=now
pub const INSERT: &str = "
    INSERT INTO carts
        (uuid, tenant_uuid, store_uuid, user_uuid, customer_uuid,
         status, notes, created_at, updated_at)
    VALUES (?1,?2,?3,?4,?5,'on_hold',?6,?7,?7)";

/// ?1=status ?2=now ?3=uuid ?4=tenant
pub const UPDATE_STATUS: &str = "
    UPDATE carts
    SET status = ?1, updated_at = ?2
    WHERE uuid = ?3 AND tenant_uuid = ?4";

/// Deletes cart + all cart_items (CASCADE). ?1=uuid ?2=tenant
pub const DELETE: &str = "
    DELETE FROM carts
    WHERE uuid = ?1 AND tenant_uuid = ?2";

// ---- Cart Items -----------------------------------------------------

pub const FIND_ITEMS: &str = "
    SELECT uuid, cart_uuid, product_uuid, variant_uuid, product_name, product_sku,
           quantity, unit_price, discount, total, created_at, updated_at
    FROM cart_items
    WHERE cart_uuid = ?1
    ORDER BY created_at ASC";

/// ?1=uuid ?2=cart_uuid ?3=product_uuid ?4=variant|NULL ?5=name ?6=sku
/// ?7=quantity ?8=unit_price ?9=discount ?10=total ?11=now
pub const INSERT_ITEM: &str = "
    INSERT INTO cart_items
        (uuid, cart_uuid, product_uuid, variant_uuid, product_name, product_sku,
         quantity, unit_price, discount, total, created_at, updated_at)
    VALUES (?1,?2,?3,?4,?5,?6,?7,?8,?9,?10,?11,?11)";

pub const DELETE_ITEMS: &str = "
    DELETE FROM cart_items WHERE cart_uuid = ?1";
