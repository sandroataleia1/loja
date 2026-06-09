use rusqlite::{Connection, Result, params};

use crate::database::{models::sync::SyncEntry, queries::sync as q};

pub struct SyncRepository<'a> {
    conn: &'a Connection,
}

impl<'a> SyncRepository<'a> {
    pub fn new(conn: &'a Connection) -> Self {
        Self { conn }
    }

    pub fn pending_count(&self) -> Result<i64> {
        self.conn.query_row(q::COUNT_PENDING, [], |row| row.get(0))
    }

    /// Returns the next batch of unsynced entries (attempts < 5), oldest first.
    pub fn next_batch(&self, limit: i64) -> Result<Vec<SyncEntry>> {
        let mut stmt = self.conn.prepare(q::NEXT_BATCH)?;
        let rows = stmt
            .query_map(params![limit], row_to_sync_entry)?
            .filter_map(|r| r.ok())
            .collect();
        Ok(rows)
    }

    /// Removes the entry from the queue after a successful API sync.
    pub fn mark_success(&self, uuid: &str) -> Result<bool> {
        let changed = self.conn.execute(q::DELETE_SYNC_ENTRY, params![uuid])?;
        Ok(changed > 0)
    }

    /// Increments `attempts` and stores the error message.
    pub fn mark_failed(&self, uuid: &str, error: &str) -> Result<()> {
        self.conn.execute(q::MARK_SYNC_FAILED, params![uuid, error])?;
        Ok(())
    }
}

fn row_to_sync_entry(row: &rusqlite::Row<'_>) -> rusqlite::Result<SyncEntry> {
    Ok(SyncEntry {
        uuid:        row.get(0)?,
        entity_type: row.get(1)?,
        entity_uuid: row.get(2)?,
        operation:   row.get(3)?,
        payload:     row.get(4)?,
        attempts:    row.get(5)?,
        last_error:  row.get(6)?,
        created_at:  row.get(7)?,
    })
}
