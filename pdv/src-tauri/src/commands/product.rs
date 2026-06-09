use tauri::State;

use crate::database::{
    DbState,
    models::product::{
        CreateProductInput, CreateVariantInput, Product, ProductVariant,
        UpdateProductInput, UpdateVariantInput,
    },
    repositories::ProductRepository,
};

pub const TENANT: &str = "00000000-0000-4000-8000-000000000001";

#[tauri::command]
pub fn product_list(
    state:  State<'_, DbState>,
    search: Option<String>,
    status: Option<String>,
    limit:  Option<i64>,
    offset: Option<i64>,
) -> Result<Vec<Product>, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    ProductRepository::new(&conn)
        .list(
            TENANT,
            status.as_deref().unwrap_or("active"),
            search.as_deref(),
            limit.unwrap_or(100),
            offset.unwrap_or(0),
        )
        .map_err(|e| e.to_string())
}

#[tauri::command]
pub fn product_get(
    state: State<'_, DbState>,
    uuid:  String,
) -> Result<Option<Product>, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    ProductRepository::new(&conn)
        .find(&uuid, TENANT)
        .map_err(|e| e.to_string())
}

#[tauri::command]
pub fn product_create(
    state: State<'_, DbState>,
    input: CreateProductInput,
) -> Result<Product, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    ProductRepository::new(&conn)
        .create(&input, TENANT)
        .map_err(|e| e.to_string())
}

#[tauri::command]
pub fn product_update(
    state: State<'_, DbState>,
    uuid:  String,
    input: UpdateProductInput,
) -> Result<Option<Product>, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    ProductRepository::new(&conn)
        .update(&uuid, &input, TENANT)
        .map_err(|e| e.to_string())
}

#[tauri::command]
pub fn product_delete(
    state: State<'_, DbState>,
    uuid:  String,
) -> Result<bool, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    ProductRepository::new(&conn)
        .soft_delete(&uuid, TENANT)
        .map_err(|e| e.to_string())
}

// ---- Variant commands -----------------------------------------------

#[tauri::command]
pub fn variant_list(
    state:        State<'_, DbState>,
    product_uuid: String,
) -> Result<Vec<ProductVariant>, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    ProductRepository::new(&conn)
        .list_variants(&product_uuid, TENANT)
        .map_err(|e| e.to_string())
}

#[tauri::command]
pub fn variant_get(
    state: State<'_, DbState>,
    uuid:  String,
) -> Result<Option<ProductVariant>, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    ProductRepository::new(&conn)
        .find_variant(&uuid, TENANT)
        .map_err(|e| e.to_string())
}

#[tauri::command]
pub fn variant_create(
    state: State<'_, DbState>,
    input: CreateVariantInput,
) -> Result<ProductVariant, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    ProductRepository::new(&conn)
        .create_variant(&input, TENANT)
        .map_err(|e| e.to_string())
}

#[tauri::command]
pub fn variant_update(
    state: State<'_, DbState>,
    uuid:  String,
    input: UpdateVariantInput,
) -> Result<Option<ProductVariant>, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    ProductRepository::new(&conn)
        .update_variant(&uuid, &input, TENANT)
        .map_err(|e| e.to_string())
}
