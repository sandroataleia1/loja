use serde::{Deserialize, Serialize};

// ---- Catalog (fast search / POS) ------------------------------------

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct CatalogVariant {
    pub uuid:       String,
    pub sku:        String,
    pub barcode:    Option<String>,
    pub price:      Option<i64>,
    pub stock:      i64,
    pub is_active:  bool,
    pub attributes: serde_json::Value,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct CatalogProduct {
    pub uuid:          String,
    pub category_uuid: Option<String>,
    pub category_name: Option<String>,
    pub name:          String,
    pub sku:           String,
    pub barcode:       Option<String>,
    pub description:   Option<String>,
    pub price:         i64,
    pub stock:         i64,
    pub status:        String,
    pub variants:      Vec<CatalogVariant>,
}

#[derive(Debug, Clone, Serialize)]
pub struct BarcodeResult {
    pub product:         CatalogProduct,
    pub matched_variant: Option<String>,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct ProductCategory {
    pub uuid: String,
    pub name: String,
}

// ---- Core models ----------------------------------------------------

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Product {
    pub uuid:          String,
    pub tenant_uuid:   String,
    pub category_uuid: Option<String>,
    pub category_name: Option<String>,
    pub name:          String,
    pub sku:           String,
    pub barcode:       Option<String>,
    pub description:   Option<String>,
    pub price:         i64,
    pub cost:          Option<i64>,
    pub stock:         i64,
    pub status:        String,
    pub created_at:    String,
    pub updated_at:    String,
}

/// attributes armazenado como JSON TEXT no SQLite, entregue como Value ao frontend.
#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct ProductVariant {
    pub uuid:         String,
    pub tenant_uuid:  String,
    pub product_uuid: String,
    pub sku:          String,
    pub barcode:      Option<String>,
    pub attributes:   serde_json::Value,
    /// None = herda o preço do produto pai
    pub price:        Option<i64>,
    pub cost:         Option<i64>,
    pub stock:        i64,
    pub is_active:    bool,
    pub created_at:   String,
    pub updated_at:   String,
}

#[derive(Debug, Deserialize)]
pub struct CreateProductInput {
    pub category_uuid: Option<String>,
    pub name:          String,
    pub sku:           String,
    pub barcode:       Option<String>,
    pub description:   Option<String>,
    pub price:         i64,
    pub cost:          Option<i64>,
    pub status:        Option<String>,
}

#[derive(Debug, Deserialize)]
pub struct UpdateProductInput {
    pub category_uuid: Option<String>,
    pub name:          Option<String>,
    pub sku:           Option<String>,
    pub barcode:       Option<String>,
    pub description:   Option<String>,
    pub price:         Option<i64>,
    pub cost:          Option<i64>,
    pub status:        Option<String>,
}

#[derive(Debug, Deserialize)]
pub struct CreateVariantInput {
    pub product_uuid: String,
    pub sku:          String,
    pub barcode:      Option<String>,
    pub attributes:   Option<serde_json::Value>,
    pub price:        Option<i64>,
    pub cost:         Option<i64>,
    pub stock:        Option<i64>,
}

#[derive(Debug, Deserialize)]
pub struct UpdateVariantInput {
    pub sku:        Option<String>,
    pub barcode:    Option<String>,
    pub attributes: Option<serde_json::Value>,
    pub price:      Option<i64>,
    pub cost:       Option<i64>,
    pub stock:      Option<i64>,
    pub is_active:  Option<bool>,
}
