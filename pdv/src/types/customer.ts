export interface Customer {
  uuid:        string
  tenant_uuid: string
  name:        string
  cpf:         string | null
  email:       string | null
  phone:       string | null
  created_at:  string
  updated_at:  string
}

export interface CreateCustomerInput {
  name:   string
  cpf?:   string
  email?: string
  phone?: string
}
