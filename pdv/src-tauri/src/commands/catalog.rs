use tauri::State;

use crate::database::{
    DbState,
    models::product::{BarcodeResult, CatalogProduct, ProductCategory},
    repositories::ProductRepository,
};

use super::product::TENANT;

#[tauri::command]
pub fn catalog_load_all(state: State<'_, DbState>) -> Result<Vec<CatalogProduct>, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    ProductRepository::new(&conn)
        .catalog_load_all(TENANT)
        .map_err(|e| e.to_string())
}

#[tauri::command]
pub fn catalog_find_by_barcode(
    state:   State<'_, DbState>,
    barcode: String,
) -> Result<Option<BarcodeResult>, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    ProductRepository::new(&conn)
        .find_by_barcode(&barcode, TENANT)
        .map_err(|e| e.to_string())
}

#[tauri::command]
pub fn catalog_categories(state: State<'_, DbState>) -> Result<Vec<ProductCategory>, String> {
    let conn = state.0.lock().map_err(|e| e.to_string())?;
    ProductRepository::new(&conn)
        .list_categories(TENANT)
        .map_err(|e| e.to_string())
}
