pub mod connection;
pub mod migrations;
pub mod models;
pub mod queries;
pub mod repositories;

use anyhow::Result;
use rusqlite::Connection;
use std::sync::Mutex;

/// Shared mutable SQLite state managed by Tauri.
/// All commands lock this mutex before accessing the connection.
pub struct DbState(pub Mutex<Connection>);

/// Opens the database, applies all pending migrations, and returns the connection.
pub fn init(path: &std::path::Path) -> Result<Connection> {
    let conn = connection::open(path)?;
    migrations::run(&conn)?;
    Ok(conn)
}
