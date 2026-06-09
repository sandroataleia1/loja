use serde::{Deserialize, Serialize};

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Cart {
    pub uuid:          String,
    pub tenant_uuid:   String,
    pub store_uuid:    String,
    pub user_uuid:     String,
    pub customer_uuid: Option<String>,
    pub status:        String,
    pub notes:         Option<String>,
    pub created_at:    String,
    pub updated_at:    String,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct CartItem {
    pub uuid:         String,
    pub cart_uuid:    String,
    pub product_uuid: String,
    pub variant_uuid: Option<String>,
    pub product_name: String,
    pub product_sku:  String,
    pub quantity:     i64,
    pub unit_price:   i64,
    pub discount:     i64,
    pub total:        i64,
    pub created_at:   String,
    pub updated_at:   String,
}

/// Cart with its items — returned by `cart_get`.
#[derive(Debug, Serialize)]
pub struct CartWithItems {
    pub cart:  Cart,
    pub items: Vec<CartItem>,
}

/// Saves the current in-memory cart to SQLite as on_hold.
#[derive(Debug, Deserialize)]
pub struct SaveCartInput {
    pub customer_uuid: Option<String>,
    pub notes:         Option<String>,
    pub items:         Vec<SaveCartItemInput>,
}

#[derive(Debug, Deserialize)]
pub struct SaveCartItemInput {
    pub product_uuid: String,
    pub variant_uuid: Option<String>,
    pub product_name: String,
    pub product_sku:  String,
    pub quantity:     i64,
    pub unit_price:   i64,
    pub discount:     Option<i64>,
}
