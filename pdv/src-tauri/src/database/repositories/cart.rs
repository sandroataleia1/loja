use rusqlite::{Connection, OptionalExtension, Result, params};
use uuid::Uuid;

use crate::database::{
    models::cart::{Cart, CartItem, CartWithItems, SaveCartInput},
    queries::carts as q,
};

pub struct CartRepository<'a> {
    conn: &'a Connection,
}

impl<'a> CartRepository<'a> {
    pub fn new(conn: &'a Connection) -> Self {
        Self { conn }
    }

    pub fn list_on_hold(&self, tenant: &str) -> Result<Vec<Cart>> {
        let mut stmt = self.conn.prepare(q::LIST_ON_HOLD)?;
        let rows = stmt
            .query_map(params![tenant], row_to_cart)?
            .filter_map(|r| r.ok())
            .collect();
        Ok(rows)
    }

    pub fn find_with_items(&self, uuid: &str, tenant: &str) -> Result<Option<CartWithItems>> {
        let cart = match self.find(uuid, tenant)? {
            Some(c) => c,
            None    => return Ok(None),
        };

        let mut stmt = self.conn.prepare(q::FIND_ITEMS)?;
        let items: Vec<CartItem> = stmt
            .query_map(params![uuid], row_to_cart_item)?
            .filter_map(|r| r.ok())
            .collect();

        Ok(Some(CartWithItems { cart, items }))
    }

    /// Saves an in-memory cart as on_hold (atomically).
    pub fn save(
        &self,
        input:  &SaveCartInput,
        tenant: &str,
        store:  &str,
        user:   &str,
    ) -> Result<CartWithItems> {
        let cart_id = Uuid::new_v4().to_string();
        let now     = now();

        let tx = self.conn.unchecked_transaction()?;

        tx.execute(
            q::INSERT,
            params![cart_id, tenant, store, user, input.customer_uuid, input.notes, now],
        )?;

        for item in &input.items {
            let item_id      = Uuid::new_v4().to_string();
            let item_discount = item.discount.unwrap_or(0);
            let item_total   = item.quantity * item.unit_price - item_discount;

            tx.execute(
                q::INSERT_ITEM,
                params![
                    item_id, cart_id,
                    item.product_uuid, item.variant_uuid,
                    item.product_name, item.product_sku,
                    item.quantity, item.unit_price, item_discount, item_total,
                    now
                ],
            )?;
        }

        tx.commit()?;

        self.find_with_items(&cart_id, tenant)?
            .ok_or_else(|| rusqlite::Error::QueryReturnedNoRows)
    }

    /// Marks a cart as 'cancelled' and removes it.
    pub fn delete(&self, uuid: &str, tenant: &str) -> Result<bool> {
        let changed = self.conn.execute(q::DELETE, params![uuid, tenant])?;
        Ok(changed > 0)
    }

    fn find(&self, uuid: &str, tenant: &str) -> Result<Option<Cart>> {
        self.conn
            .query_row(q::FIND, params![uuid, tenant], row_to_cart)
            .optional()
    }
}

// ---- Row mappers ----------------------------------------------------

fn row_to_cart(row: &rusqlite::Row<'_>) -> rusqlite::Result<Cart> {
    Ok(Cart {
        uuid:          row.get(0)?,
        tenant_uuid:   row.get(1)?,
        store_uuid:    row.get(2)?,
        user_uuid:     row.get(3)?,
        customer_uuid: row.get(4)?,
        status:        row.get(5)?,
        notes:         row.get(6)?,
        created_at:    row.get(7)?,
        updated_at:    row.get(8)?,
    })
}

fn row_to_cart_item(row: &rusqlite::Row<'_>) -> rusqlite::Result<CartItem> {
    Ok(CartItem {
        uuid:         row.get(0)?,
        cart_uuid:    row.get(1)?,
        product_uuid: row.get(2)?,
        variant_uuid: row.get(3)?,
        product_name: row.get(4)?,
        product_sku:  row.get(5)?,
        quantity:     row.get(6)?,
        unit_price:   row.get(7)?,
        discount:     row.get(8)?,
        total:        row.get(9)?,
        created_at:   row.get(10)?,
        updated_at:   row.get(11)?,
    })
}

fn now() -> String {
    chrono::Utc::now().format("%Y-%m-%dT%H:%M:%SZ").to_string()
}
