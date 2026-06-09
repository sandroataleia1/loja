use tauri::State;

use crate::database::{
    DbState,
    models::sync::SyncEntry,
    repositories::SyncRepository,
};

#[tauri::command]
pub fn sync_pending_count(state: State<'_, DbState>) -> Result<i64, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    SyncRepository::new(&conn)
        .pending_count()
        .map_err(|e| e.to_string())
}

#[tauri::command]
pub fn sync_next_batch(
    state: State<'_, DbState>,
    limit: Option<i64>,
) -> Result<Vec<SyncEntry>, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    SyncRepository::new(&conn)
        .next_batch(limit.unwrap_or(10))
        .map_err(|e| e.to_string())
}

#[tauri::command]
pub fn sync_mark_success(
    state: State<'_, DbState>,
    uuid:  String,
) -> Result<bool, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    SyncRepository::new(&conn)
        .mark_success(&uuid)
        .map_err(|e| e.to_string())
}

#[tauri::command]
pub fn sync_mark_failed(
    state: State<'_, DbState>,
    uuid:  String,
    error: String,
) -> Result<(), String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    SyncRepository::new(&conn)
        .mark_failed(&uuid, &error)
        .map_err(|e| e.to_string())
}
