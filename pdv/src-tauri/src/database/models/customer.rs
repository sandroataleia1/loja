use serde::{Deserialize, Serialize};

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Customer {
    pub uuid:        String,
    pub tenant_uuid: String,
    pub name:        String,
    pub cpf:         Option<String>,
    pub email:       Option<String>,
    pub phone:       Option<String>,
    pub created_at:  String,
    pub updated_at:  String,
}

#[derive(Debug, Deserialize)]
pub struct CreateCustomerInput {
    pub name:  String,
    pub cpf:   Option<String>,
    pub email: Option<String>,
    pub phone: Option<String>,
}

#[derive(Debug, Deserialize)]
pub struct UpdateCustomerInput {
    pub name:  Option<String>,
    pub cpf:   Option<String>,
    pub email: Option<String>,
    pub phone: Option<String>,
}
