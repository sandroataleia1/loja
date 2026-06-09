use serde::{Deserialize, Serialize};

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct SyncEntry {
    pub uuid:        String,
    pub entity_type: String,
    pub entity_uuid: String,
    pub operation:   String,
    pub payload:     String,
    pub attempts:    i64,
    pub last_error:  Option<String>,
    pub created_at:  String,
}
