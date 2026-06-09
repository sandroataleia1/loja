use rusqlite::{Connection, Result};
use std::path::Path;

/// Opens a SQLite connection with all required PRAGMAs.
/// Call this once at startup — `DbState` holds the single connection.
pub fn open(path: &Path) -> Result<Connection> {
    let conn = Connection::open(path)?;
    conn.execute_batch(
        "PRAGMA journal_mode = WAL;
         PRAGMA synchronous   = NORMAL;
         PRAGMA foreign_keys  = ON;
         PRAGMA temp_store    = MEMORY;
         PRAGMA cache_size    = -16000;
         PRAGMA busy_timeout  = 5000;",
    )?;
    Ok(conn)
}
