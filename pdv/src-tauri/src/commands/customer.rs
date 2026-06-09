use tauri::State;

use crate::commands::product::TENANT;
use crate::database::{
    DbState,
    models::customer::{CreateCustomerInput, Customer, UpdateCustomerInput},
    repositories::CustomerRepository,
};

#[tauri::command]
pub fn customer_list(
    state:  State<'_, DbState>,
    search: Option<String>,
    limit:  Option<i64>,
    offset: Option<i64>,
) -> Result<Vec<Customer>, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    CustomerRepository::new(&conn)
        .list(TENANT, search.as_deref(), limit.unwrap_or(50), offset.unwrap_or(0))
        .map_err(|e| e.to_string())
}

#[tauri::command]
pub fn customer_get(
    state: State<'_, DbState>,
    uuid:  String,
) -> Result<Option<Customer>, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    CustomerRepository::new(&conn)
        .find(&uuid, TENANT)
        .map_err(|e| e.to_string())
}

#[tauri::command]
pub fn customer_create(
    state: State<'_, DbState>,
    input: CreateCustomerInput,
) -> Result<Customer, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    CustomerRepository::new(&conn)
        .create(&input, TENANT)
        .map_err(|e| e.to_string())
}

#[tauri::command]
pub fn customer_update(
    state: State<'_, DbState>,
    uuid:  String,
    input: UpdateCustomerInput,
) -> Result<Option<Customer>, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    CustomerRepository::new(&conn)
        .update(&uuid, &input, TENANT)
        .map_err(|e| e.to_string())
}
