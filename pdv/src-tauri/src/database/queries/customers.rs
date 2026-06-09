// ---- Customers ------------------------------------------------------

pub const FIND: &str = "
    SELECT uuid, tenant_uuid, name, cpf, email, phone, created_at, updated_at
    FROM customers
    WHERE uuid = ?1 AND tenant_uuid = ?2";

/// ?1=tenant ?2=pattern|NULL ?3=limit ?4=offset
pub const LIST: &str = "
    SELECT uuid, tenant_uuid, name, cpf, email, phone, created_at, updated_at
    FROM customers
    WHERE tenant_uuid = ?1
      AND (?2 IS NULL
           OR LOWER(name)  LIKE ?2
           OR cpf           LIKE ?2
           OR LOWER(email) LIKE ?2)
    ORDER BY name ASC
    LIMIT ?3 OFFSET ?4";

/// ?1=uuid ?2=tenant ?3=name ?4=cpf|NULL ?5=email|NULL ?6=phone|NULL ?7=now
pub const INSERT: &str = "
    INSERT INTO customers
        (uuid, tenant_uuid, name, cpf, email, phone, created_at, updated_at)
    VALUES (?1,?2,?3,?4,?5,?6,?7,?7)";

/// ?1=uuid ?2=tenant ?3..?6=COALESCE fields ?7=now
pub const UPDATE: &str = "
    UPDATE customers SET
        name       = COALESCE(?3, name),
        cpf        = COALESCE(?4, cpf),
        email      = COALESCE(?5, email),
        phone      = COALESCE(?6, phone),
        updated_at = ?7
    WHERE uuid = ?1 AND tenant_uuid = ?2";
