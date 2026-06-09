use tauri::State;

use crate::commands::product::TENANT;
use crate::commands::sale::STORE;
use crate::database::{
    DbState,
    models::cart::{Cart, CartWithItems, SaveCartInput},
    repositories::CartRepository,
};

/// Default user UUID (single-operator seed).
pub const USER: &str = "00000000-0000-4000-8000-000000000003";

#[tauri::command]
pub fn cart_list(state: State<'_, DbState>) -> Result<Vec<Cart>, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    CartRepository::new(&conn)
        .list_on_hold(TENANT)
        .map_err(|e| e.to_string())
}

#[tauri::command]
pub fn cart_get(
    state: State<'_, DbState>,
    uuid:  String,
) -> Result<Option<CartWithItems>, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    CartRepository::new(&conn)
        .find_with_items(&uuid, TENANT)
        .map_err(|e| e.to_string())
}

/// Persists the current in-memory cart (from Zustand) to SQLite as on_hold.
#[tauri::command]
pub fn cart_save(
    state: State<'_, DbState>,
    input: SaveCartInput,
) -> Result<CartWithItems, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    CartRepository::new(&conn)
        .save(&input, TENANT, STORE, USER)
        .map_err(|e| e.to_string())
}

#[tauri::command]
pub fn cart_delete(
    state: State<'_, DbState>,
    uuid:  String,
) -> Result<bool, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    CartRepository::new(&conn)
        .delete(&uuid, TENANT)
        .map_err(|e| e.to_string())
}
