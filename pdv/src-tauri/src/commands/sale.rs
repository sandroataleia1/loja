use tauri::State;

use crate::commands::product::TENANT;
use crate::database::{
    DbState,
    models::sale::{CreateSaleInput, Sale, SaleDetail},
    repositories::SaleRepository,
};

pub const STORE: &str = "00000000-0000-4000-8000-000000000002";

#[tauri::command]
pub fn sale_create(
    state: State<'_, DbState>,
    input: CreateSaleInput,
) -> Result<Sale, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    SaleRepository::new(&conn)
        .create(&input, TENANT, STORE)
        .map_err(|e| e.to_string())
}

#[tauri::command]
pub fn sale_list(
    state:  State<'_, DbState>,
    limit:  Option<i64>,
    offset: Option<i64>,
) -> Result<Vec<Sale>, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    SaleRepository::new(&conn)
        .list(TENANT, limit.unwrap_or(50), offset.unwrap_or(0))
        .map_err(|e| e.to_string())
}

#[tauri::command]
pub fn sale_get(
    state: State<'_, DbState>,
    uuid:  String,
) -> Result<Option<SaleDetail>, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    SaleRepository::new(&conn)
        .find_detail(&uuid, TENANT)
        .map_err(|e| e.to_string())
}
