use rusqlite::{Connection, OptionalExtension, Result, params};
use uuid::Uuid;

use crate::database::{
    models::customer::{CreateCustomerInput, Customer, UpdateCustomerInput},
    queries::customers as q,
};

pub struct CustomerRepository<'a> {
    conn: &'a Connection,
}

impl<'a> CustomerRepository<'a> {
    pub fn new(conn: &'a Connection) -> Self {
        Self { conn }
    }

    pub fn find(&self, uuid: &str, tenant: &str) -> Result<Option<Customer>> {
        self.conn
            .query_row(q::FIND, params![uuid, tenant], row_to_customer)
            .optional()
    }

    pub fn list(
        &self,
        tenant: &str,
        search: Option<&str>,
        limit:  i64,
        offset: i64,
    ) -> Result<Vec<Customer>> {
        let pattern = search.map(|s| format!("%{}%", s.to_lowercase()));
        let mut stmt = self.conn.prepare(q::LIST)?;
        let rows = stmt
            .query_map(params![tenant, pattern, limit, offset], row_to_customer)?
            .filter_map(|r| r.ok())
            .collect();
        Ok(rows)
    }

    pub fn create(&self, input: &CreateCustomerInput, tenant: &str) -> Result<Customer> {
        let id  = Uuid::new_v4().to_string();
        let now = now();

        self.conn.execute(
            q::INSERT,
            params![id, tenant, input.name, input.cpf, input.email, input.phone, now],
        )?;

        self.find(&id, tenant)?
            .ok_or_else(|| rusqlite::Error::QueryReturnedNoRows)
    }

    pub fn update(
        &self,
        uuid:   &str,
        input:  &UpdateCustomerInput,
        tenant: &str,
    ) -> Result<Option<Customer>> {
        let now = now();
        self.conn.execute(
            q::UPDATE,
            params![uuid, tenant, input.name, input.cpf, input.email, input.phone, now],
        )?;
        self.find(uuid, tenant)
    }
}

// ---- Row mappers ----------------------------------------------------

fn row_to_customer(row: &rusqlite::Row<'_>) -> rusqlite::Result<Customer> {
    Ok(Customer {
        uuid:        row.get(0)?,
        tenant_uuid: row.get(1)?,
        name:        row.get(2)?,
        cpf:         row.get(3)?,
        email:       row.get(4)?,
        phone:       row.get(5)?,
        created_at:  row.get(6)?,
        updated_at:  row.get(7)?,
    })
}

fn now() -> String {
    chrono::Utc::now().format("%Y-%m-%dT%H:%M:%SZ").to_string()
}
