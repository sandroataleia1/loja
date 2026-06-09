use rusqlite::{Connection, Result, params};

struct Migration {
    version: i64,
    name:    &'static str,
    sql:     &'static str,
}

static MIGRATIONS: &[Migration] = &[
    Migration {
        version: 1,
        name:    "initial",
        sql:     include_str!("v0001_initial.sql"),
    },
    Migration {
        version: 2,
        name:    "product_variants",
        sql:     include_str!("v0002_product_variants.sql"),
    },
    Migration {
        version: 3,
        name:    "carts",
        sql:     include_str!("v0003_carts.sql"),
    },
    Migration {
        version: 4,
        name:    "seed_products",
        sql:     include_str!("v0004_seed_products.sql"),
    },
    Migration {
        version: 6,
        name:    "variants_stock",
        sql:     include_str!("v0006_variants_stock.sql"),
    },
    Migration {
        version: 7,
        name:    "sale_items_snapshots",
        sql:     include_str!("v0007_sale_items_snapshots.sql"),
    },
    Migration {
        version: 8,
        name:    "sync_foundation",
        sql:     include_str!("v0008_sync_foundation.sql"),
    },
];

/// Creates `schema_migrations` and applies any unapplied migrations in order.
/// Each migration runs inside its own transaction — failure is atomic.
pub fn run(conn: &Connection) -> Result<()> {
    conn.execute_batch(
        "CREATE TABLE IF NOT EXISTS schema_migrations (
            version    INTEGER NOT NULL PRIMARY KEY,
            name       TEXT    NOT NULL,
            applied_at TEXT    NOT NULL DEFAULT (datetime('now'))
         );",
    )?;

    for m in MIGRATIONS {
        let already_applied: bool = conn.query_row(
            "SELECT EXISTS(SELECT 1 FROM schema_migrations WHERE version = ?1)",
            [m.version],
            |row| row.get(0),
        )?;

        if !already_applied {
            let tx = conn.unchecked_transaction()?;
            tx.execute_batch(m.sql)?;
            tx.execute(
                "INSERT INTO schema_migrations (version, name) VALUES (?1, ?2)",
                params![m.version, m.name],
            )?;
            tx.commit()?;
        }
    }

    Ok(())
}
