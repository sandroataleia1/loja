// ---- Sync Queue -----------------------------------------------------

pub const COUNT_PENDING: &str = "
    SELECT COUNT(*) FROM sync_queue WHERE attempts < 5";

/// Returns entries with fewest retries first. ?1=limit
pub const NEXT_BATCH: &str = "
    SELECT uuid, entity_type, entity_uuid, operation, payload,
           attempts, last_error, created_at
    FROM sync_queue
    WHERE attempts < 5
    ORDER BY created_at ASC
    LIMIT ?1";

/// On success: remove from queue. ?1=uuid
pub const DELETE_SYNC_ENTRY: &str = "
    DELETE FROM sync_queue WHERE uuid = ?1";

/// On failure: increment attempts + store error. ?1=uuid ?2=error_msg
pub const MARK_SYNC_FAILED: &str = "
    UPDATE sync_queue
    SET attempts   = attempts + 1,
        last_error = ?2
    WHERE uuid = ?1";
