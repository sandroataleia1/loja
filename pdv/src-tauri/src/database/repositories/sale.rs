use rusqlite::{Connection, OptionalExtension, Result, params};
use uuid::Uuid;

use crate::database::{
    models::sale::{
        CreateSaleInput, Payment, Sale, SaleDetail, SaleItem,
    },
    queries::sales as q,
};

pub struct SaleRepository<'a> {
    conn: &'a Connection,
}

impl<'a> SaleRepository<'a> {
    pub fn new(conn: &'a Connection) -> Self {
        Self { conn }
    }

    pub fn find_detail(&self, uuid: &str, tenant: &str) -> Result<Option<SaleDetail>> {
        let sale = match self.find(uuid, tenant)? {
            Some(s) => s,
            None    => return Ok(None),
        };

        let mut item_stmt = self.conn.prepare(q::FIND_ITEMS)?;
        let items: Vec<SaleItem> = item_stmt
            .query_map(params![uuid], row_to_item)?
            .filter_map(|r| r.ok())
            .collect();

        let mut pay_stmt = self.conn.prepare(q::FIND_PAYMENTS)?;
        let payments: Vec<Payment> = pay_stmt
            .query_map(params![uuid], row_to_payment)?
            .filter_map(|r| r.ok())
            .collect();

        Ok(Some(SaleDetail { sale, items, payments }))
    }

    pub fn list(&self, tenant: &str, limit: i64, offset: i64) -> Result<Vec<Sale>> {
        let mut stmt = self.conn.prepare(q::LIST)?;
        let rows = stmt
            .query_map(params![tenant, limit, offset], row_to_sale)?
            .filter_map(|r| r.ok())
            .collect();
        Ok(rows)
    }

    /// Creates a sale atomically:
    /// INSERT sales → INSERT sale_items → decrement product stock →
    /// INSERT payments → INSERT sync_queue entry.
    pub fn create(
        &self,
        input:  &CreateSaleInput,
        tenant: &str,
        store:  &str,
    ) -> Result<Sale> {
        let sale_id  = Uuid::new_v4().to_string();
        let now      = now();
        let discount = input.discount.unwrap_or(0);

        let subtotal: i64 = input.items.iter().map(|i| {
            i.quantity * i.unit_price - i.discount.unwrap_or(0)
        }).sum();
        let total = (subtotal - discount).max(0);

        let tx = self.conn.unchecked_transaction()?;

        tx.execute(
            q::INSERT,
            params![
                sale_id, tenant, store,
                input.customer_uuid, input.user_uuid,
                subtotal, discount, total, input.notes, now
            ],
        )?;

        for item in &input.items {
            let item_id          = Uuid::new_v4().to_string();
            let item_discount    = item.discount.unwrap_or(0);
            let item_total       = item.quantity * item.unit_price - item_discount;
            let name_snap        = item.name_snapshot.as_deref().unwrap_or(&item.product_name);
            let sku_snap         = item.sku_snapshot.as_deref().unwrap_or(&item.product_sku);
            let attrs_snap       = item.attributes_snapshot.as_deref().unwrap_or("{}");

            tx.execute(
                q::INSERT_ITEM,
                params![
                    item_id, sale_id,
                    item.product_uuid, item.variant_uuid,
                    item.product_name, item.product_sku,
                    name_snap, sku_snap, attrs_snap, item.cost_snapshot,
                    item.quantity, item.unit_price,
                    item_discount, item_total, now
                ],
            )?;

            // Decrement variant stock (clamped to 0 via MAX(0, …))
            if let Some(ref variant_uuid) = item.variant_uuid {
                tx.execute(
                    crate::database::queries::products::ADJUST_VARIANT_STOCK,
                    params![-item.quantity, variant_uuid, now],
                )?;
            }
        }

        for payment in &input.payments {
            let pay_id = Uuid::new_v4().to_string();
            tx.execute(
                q::INSERT_PAYMENT,
                params![pay_id, sale_id, payment.method, payment.amount,
                        payment.reference, now],
            )?;
        }

        // Enqueue for future sync with API Laravel
        let sync_id = Uuid::new_v4().to_string();
        let payload = serde_json::json!({ "sale_uuid": sale_id });
        tx.execute(
            q::INSERT_SYNC_ENTRY,
            params![sync_id, "sale", sale_id, "create",
                    payload.to_string(), now],
        )?;

        tx.commit()?;

        self.find(&sale_id, tenant)?
            .ok_or_else(|| rusqlite::Error::QueryReturnedNoRows)
    }

    fn find(&self, uuid: &str, tenant: &str) -> Result<Option<Sale>> {
        self.conn
            .query_row(q::FIND, params![uuid, tenant], row_to_sale)
            .optional()
    }
}

// ---- Row mappers ----------------------------------------------------

fn row_to_sale(row: &rusqlite::Row<'_>) -> rusqlite::Result<Sale> {
    Ok(Sale {
        uuid:          row.get(0)?,
        tenant_uuid:   row.get(1)?,
        store_uuid:    row.get(2)?,
        customer_uuid: row.get(3)?,
        customer_name: row.get(4)?,
        user_uuid:     row.get(5)?,
        status:        row.get(6)?,
        subtotal:      row.get(7)?,
        discount:      row.get(8)?,
        total:         row.get(9)?,
        notes:         row.get(10)?,
        completed_at:  row.get(11)?,
        synced_at:     row.get(12)?,
        created_at:    row.get(13)?,
    })
}

fn row_to_item(row: &rusqlite::Row<'_>) -> rusqlite::Result<SaleItem> {
    Ok(SaleItem {
        uuid:                row.get(0)?,
        sale_uuid:           row.get(1)?,
        product_uuid:        row.get(2)?,
        variant_uuid:        row.get(3)?,
        product_name:        row.get(4)?,
        product_sku:         row.get(5)?,
        name_snapshot:       row.get(6)?,
        sku_snapshot:        row.get(7)?,
        attributes_snapshot: row.get(8)?,
        cost_snapshot:       row.get(9)?,
        quantity:            row.get(10)?,
        unit_price:          row.get(11)?,
        discount:            row.get(12)?,
        total:               row.get(13)?,
    })
}

fn row_to_payment(row: &rusqlite::Row<'_>) -> rusqlite::Result<Payment> {
    Ok(Payment {
        uuid:      row.get(0)?,
        sale_uuid: row.get(1)?,
        method:    row.get(2)?,
        amount:    row.get(3)?,
        reference: row.get(4)?,
    })
}

fn now() -> String {
    chrono::Utc::now().format("%Y-%m-%dT%H:%M:%SZ").to_string()
}
