use rusqlite::{Connection, OptionalExtension, Result, params};
use serde::Deserialize;
use uuid::Uuid;

use crate::database::{
    models::product::{
        BarcodeResult, CatalogProduct, CatalogVariant, CreateProductInput,
        CreateVariantInput, Product, ProductCategory, ProductVariant,
        UpdateProductInput, UpdateVariantInput,
    },
    queries::products as q,
};

pub struct ProductRepository<'a> {
    conn: &'a Connection,
}

impl<'a> ProductRepository<'a> {
    pub fn new(conn: &'a Connection) -> Self {
        Self { conn }
    }

    pub fn find(&self, uuid: &str, tenant: &str) -> Result<Option<Product>> {
        self.conn
            .query_row(q::FIND, params![uuid, tenant], row_to_product)
            .optional()
    }

    pub fn list(
        &self,
        tenant:  &str,
        status:  &str,
        search:  Option<&str>,
        limit:   i64,
        offset:  i64,
    ) -> Result<Vec<Product>> {
        let pattern = search.map(|s| format!("%{}%", s.to_lowercase()));
        let mut stmt = self.conn.prepare(q::LIST)?;
        let rows = stmt
            .query_map(params![tenant, status, pattern, limit, offset], row_to_product)?
            .filter_map(|r| r.ok())
            .collect();
        Ok(rows)
    }

    pub fn create(&self, input: &CreateProductInput, tenant: &str) -> Result<Product> {
        let id     = Uuid::new_v4().to_string();
        let now    = now();
        let status = input.status.as_deref().unwrap_or("active");

        self.conn.execute(
            q::INSERT,
            params![
                id, tenant, input.category_uuid, input.name, input.sku,
                input.barcode, input.description, input.price, input.cost,
                status, now
            ],
        )?;

        self.find(&id, tenant)?
            .ok_or_else(|| rusqlite::Error::QueryReturnedNoRows)
    }

    pub fn update(
        &self,
        uuid:   &str,
        input:  &UpdateProductInput,
        tenant: &str,
    ) -> Result<Option<Product>> {
        let now = now();
        self.conn.execute(
            q::UPDATE,
            params![
                uuid, tenant,
                input.category_uuid, input.name, input.sku, input.barcode,
                input.description, input.price, input.cost,
                input.status, now
            ],
        )?;
        self.find(uuid, tenant)
    }

    /// Soft-delete: sets status='inactive'.
    pub fn soft_delete(&self, uuid: &str, tenant: &str) -> Result<bool> {
        let changed = self.conn.execute(q::SOFT_DELETE, params![uuid, tenant, now()])?;
        Ok(changed > 0)
    }

    // ---- Variants ------------------------------------------------

    pub fn find_variant(&self, uuid: &str, tenant: &str) -> Result<Option<ProductVariant>> {
        self.conn
            .query_row(q::FIND_VARIANT, params![uuid, tenant], row_to_variant)
            .optional()
    }

    pub fn list_variants(&self, product_uuid: &str, tenant: &str) -> Result<Vec<ProductVariant>> {
        let mut stmt = self.conn.prepare(q::LIST_VARIANTS)?;
        let rows = stmt
            .query_map(params![product_uuid, tenant], row_to_variant)?
            .filter_map(|r| r.ok())
            .collect();
        Ok(rows)
    }

    pub fn create_variant(
        &self,
        input:  &CreateVariantInput,
        tenant: &str,
    ) -> Result<ProductVariant> {
        let id    = Uuid::new_v4().to_string();
        let now   = now();
        let attrs = serde_json::to_string(
            &input.attributes.clone().unwrap_or(serde_json::json!({})),
        )
        .unwrap_or_else(|_| "{}".to_string());
        let stock = input.stock.unwrap_or(0);

        self.conn.execute(
            q::INSERT_VARIANT,
            params![
                id, tenant, input.product_uuid, input.sku,
                input.barcode, attrs, input.price, input.cost, stock, now
            ],
        )?;

        self.find_variant(&id, tenant)?
            .ok_or_else(|| rusqlite::Error::QueryReturnedNoRows)
    }

    pub fn update_variant(
        &self,
        uuid:   &str,
        input:  &UpdateVariantInput,
        tenant: &str,
    ) -> Result<Option<ProductVariant>> {
        let now       = now();
        let attrs_str = input.attributes.as_ref()
            .map(|v| serde_json::to_string(v).unwrap_or_else(|_| "{}".to_string()));
        let is_active = input.is_active.map(|b| if b { 1i64 } else { 0i64 });

        self.conn.execute(
            q::UPDATE_VARIANT,
            params![
                uuid, tenant,
                input.sku, input.barcode, attrs_str,
                input.price, input.cost, input.stock, is_active, now
            ],
        )?;
        self.find_variant(uuid, tenant)
    }

    pub fn adjust_variant_stock(&self, uuid: &str, delta: i64) -> Result<()> {
        self.conn.execute(q::ADJUST_VARIANT_STOCK, params![delta, uuid, now()])?;
        Ok(())
    }

    // ---- Catalog (busca em memória / POS) ----------------------------

    pub fn catalog_load_all(&self, tenant: &str) -> Result<Vec<CatalogProduct>> {
        let mut stmt = self.conn.prepare(q::CATALOG_LOAD_ALL)?;
        let rows = stmt
            .query_map(params![tenant], row_to_catalog_product)?
            .filter_map(|r| r.ok())
            .collect();
        Ok(rows)
    }

    pub fn find_by_barcode(&self, barcode: &str, tenant: &str) -> Result<Option<BarcodeResult>> {
        self.conn
            .query_row(q::CATALOG_BY_BARCODE, params![barcode, tenant], row_to_barcode_result)
            .optional()
    }

    pub fn list_categories(&self, tenant: &str) -> Result<Vec<ProductCategory>> {
        let mut stmt = self.conn.prepare(q::CATALOG_CATEGORIES)?;
        let rows = stmt
            .query_map(params![tenant], |row| {
                Ok(ProductCategory { uuid: row.get(0)?, name: row.get(1)? })
            })?
            .filter_map(|r| r.ok())
            .collect();
        Ok(rows)
    }
}

// ---- Row mappers ----------------------------------------------------

fn row_to_product(row: &rusqlite::Row<'_>) -> rusqlite::Result<Product> {
    Ok(Product {
        uuid:          row.get(0)?,
        tenant_uuid:   row.get(1)?,
        category_uuid: row.get(2)?,
        category_name: row.get(3)?,
        name:          row.get(4)?,
        sku:           row.get(5)?,
        barcode:       row.get(6)?,
        description:   row.get(7)?,
        price:         row.get(8)?,
        cost:          row.get(9)?,
        stock:         row.get(10)?,
        status:        row.get(11)?,
        created_at:    row.get(12)?,
        updated_at:    row.get(13)?,
    })
}

fn row_to_variant(row: &rusqlite::Row<'_>) -> rusqlite::Result<ProductVariant> {
    let attrs_str: String = row.get(5)?;
    let attributes = serde_json::from_str(&attrs_str).unwrap_or(serde_json::json!({}));
    let is_active: i64 = row.get(9)?;
    Ok(ProductVariant {
        uuid:         row.get(0)?,
        tenant_uuid:  row.get(1)?,
        product_uuid: row.get(2)?,
        sku:          row.get(3)?,
        barcode:      row.get(4)?,
        attributes,
        price:        row.get(6)?,
        cost:         row.get(7)?,
        stock:        row.get(8)?,
        is_active:    is_active != 0,
        created_at:   row.get(10)?,
        updated_at:   row.get(11)?,
    })
}

fn now() -> String {
    chrono::Utc::now().format("%Y-%m-%dT%H:%M:%SZ").to_string()
}

// ---- Catalog row mappers -------------------------------------------

fn parse_variants(json_str: &str) -> Vec<CatalogVariant> {
    #[derive(Deserialize)]
    struct Raw {
        uuid:       String,
        sku:        String,
        barcode:    Option<String>,
        price:      Option<i64>,
        stock:      i64,
        is_active:  serde_json::Value, // SQLite stores as 0/1 integer
        attributes: serde_json::Value,
    }
    let raw: Vec<Raw> = serde_json::from_str(json_str).unwrap_or_default();
    raw.into_iter()
        .map(|r| CatalogVariant {
            uuid:       r.uuid,
            sku:        r.sku,
            barcode:    r.barcode,
            price:      r.price,
            stock:      r.stock,
            is_active:  r.is_active.as_i64().map(|n| n != 0).unwrap_or(false),
            attributes: r.attributes,
        })
        .collect()
}

fn row_to_catalog_product(row: &rusqlite::Row<'_>) -> rusqlite::Result<CatalogProduct> {
    let variants_str: String = row.get(10)?;
    Ok(CatalogProduct {
        uuid:          row.get(0)?,
        category_uuid: row.get(1)?,
        category_name: row.get(2)?,
        name:          row.get(3)?,
        sku:           row.get(4)?,
        barcode:       row.get(5)?,
        description:   row.get(6)?,
        price:         row.get(7)?,
        stock:         row.get(8)?,
        status:        row.get(9)?,
        variants:      parse_variants(&variants_str),
    })
}

fn row_to_barcode_result(row: &rusqlite::Row<'_>) -> rusqlite::Result<BarcodeResult> {
    let variants_str: String = row.get(10)?;
    Ok(BarcodeResult {
        product: CatalogProduct {
            uuid:          row.get(0)?,
            category_uuid: row.get(1)?,
            category_name: row.get(2)?,
            name:          row.get(3)?,
            sku:           row.get(4)?,
            barcode:       row.get(5)?,
            description:   row.get(6)?,
            price:         row.get(7)?,
            stock:         row.get(8)?,
            status:        row.get(9)?,
            variants:      parse_variants(&variants_str),
        },
        matched_variant: row.get(11)?,
    })
}
