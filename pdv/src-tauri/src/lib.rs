mod commands;
mod database;

use std::sync::Mutex;
use tauri::Manager;

pub fn run() {
    tauri::Builder::default()
        .setup(|app| {
            let data_dir = app.path().app_data_dir()?;
            std::fs::create_dir_all(&data_dir)?;
            let db_path = data_dir.join("pdv.db");
            let conn = database::init(&db_path)?;
            app.manage(database::DbState(Mutex::new(conn)));
            Ok(())
        })
        .invoke_handler(tauri::generate_handler![
            // Catalog (in-memory search)
            commands::catalog::catalog_load_all,
            commands::catalog::catalog_find_by_barcode,
            commands::catalog::catalog_categories,
            // Products
            commands::product::product_list,
            commands::product::product_get,
            commands::product::product_create,
            commands::product::product_update,
            commands::product::product_delete,
            // Variants
            commands::product::variant_list,
            commands::product::variant_get,
            commands::product::variant_create,
            commands::product::variant_update,
            // Sales
            commands::sale::sale_create,
            commands::sale::sale_list,
            commands::sale::sale_get,
            // Carts (on-hold)
            commands::cart::cart_list,
            commands::cart::cart_get,
            commands::cart::cart_save,
            commands::cart::cart_delete,
            // Customers
            commands::customer::customer_list,
            commands::customer::customer_get,
            commands::customer::customer_create,
            commands::customer::customer_update,
            // Sync
            commands::sync::sync_pending_count,
            commands::sync::sync_next_batch,
            commands::sync::sync_mark_success,
            commands::sync::sync_mark_failed,
        ])
        .run(tauri::generate_context!())
        .expect("error while running tauri application");
}
