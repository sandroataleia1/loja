use serde::{Deserialize, Serialize};

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Sale {
    pub uuid:          String,
    pub tenant_uuid:   String,
    pub store_uuid:    String,
    pub customer_uuid: Option<String>,
    pub customer_name: Option<String>,
    pub user_uuid:     String,
    pub status:        String,
    pub subtotal:      i64,
    pub discount:      i64,
    pub total:         i64,
    pub notes:         Option<String>,
    pub completed_at:  Option<String>,
    pub synced_at:     Option<String>,
    pub created_at:    String,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct SaleItem {
    pub uuid:                 String,
    pub sale_uuid:            String,
    pub product_uuid:         String,
    pub variant_uuid:         Option<String>,
    pub product_name:         String,
    pub product_sku:          String,
    pub name_snapshot:        String,
    pub sku_snapshot:         String,
    pub attributes_snapshot:  String,
    pub cost_snapshot:        Option<i64>,
    pub quantity:             i64,
    pub unit_price:           i64,
    pub discount:             i64,
    pub total:                i64,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Payment {
    pub uuid:      String,
    pub sale_uuid: String,
    pub method:    String,
    pub amount:    i64,
    pub reference: Option<String>,
}

#[derive(Debug, Serialize)]
pub struct SaleDetail {
    pub sale:     Sale,
    pub items:    Vec<SaleItem>,
    pub payments: Vec<Payment>,
}

#[derive(Debug, Deserialize)]
pub struct CreateSaleInput {
    pub customer_uuid: Option<String>,
    pub user_uuid:     String,
    pub discount:      Option<i64>,
    pub notes:         Option<String>,
    pub items:         Vec<CreateSaleItemInput>,
    pub payments:      Vec<CreatePaymentInput>,
}

#[derive(Debug, Deserialize)]
pub struct CreateSaleItemInput {
    pub product_uuid:         String,
    pub variant_uuid:         Option<String>,
    pub product_name:         String,
    pub product_sku:          String,
    pub name_snapshot:        Option<String>,
    pub sku_snapshot:         Option<String>,
    pub attributes_snapshot:  Option<String>,
    pub cost_snapshot:        Option<i64>,
    pub quantity:             i64,
    pub unit_price:           i64,
    pub discount:             Option<i64>,
}

#[derive(Debug, Deserialize)]
pub struct CreatePaymentInput {
    pub method:    String,
    pub amount:    i64,
    pub reference: Option<String>,
}
