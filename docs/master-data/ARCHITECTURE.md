# Módulo 04 — Cadastros Mestres — Arquitetura

**Data:** 2026-06-23

---

## 1. Princípios

1. **Sem duplicação de entidade**: Fabricante estende Marca; Vendedor estende User.
2. **Endereços por tabela dedicada**: `supplier_addresses`, `carrier_addresses` (mesmo schema de `customer_addresses`).
3. **Contatos por tabela dedicada**: `supplier_contacts`, `carrier_contacts`, `partner_contacts`.
4. **Hierarquia somente em Centros de Custo**: `parent_id` auto-referenciado.
5. **Soft delete em todas as entidades**: `deleted_at` para exclusão lógica.
6. **Multi-tenant**: `tenant_id` obrigatório em todas as tabelas raiz.
7. **UUID como PK**: consistente com o restante do projeto.

---

## 2. Entidades e Relacionamentos

```
users ──────────────────────────── seller_profiles
  │ uuid                                │ user_id FK
  │                                     │ tenant_id, code, is_active, commission_rate
  │
customers ──────────────────────── customer_addresses
  │ uuid, tenant_id                     │ customer_id FK
  │ credit_limit, rg, ie, im, situation │
  │                           ┌────────── customer_contacts
  │                           │
  └── orders (seller_id FK)   │
  └── quotes (seller_id FK)   │

suppliers ──────────────────────── supplier_addresses
  │ uuid, tenant_id, ie, im            │ supplier_id FK
  │                           ┌────────── supplier_contacts
  │                           │
  └── purchase_orders          │

carriers ───────────────────────── carrier_addresses
  │ uuid, tenant_id, cnpj, ie         │ carrier_id FK
  │                           ┌────────── carrier_contacts
  └── (future: deliveries)     │

partner_professionals ─────────── partner_contacts
  │ uuid, tenant_id, type              │ partner_id FK
  └── (future: commissions/crm)

catalog_brands  [existente]
  │ + cnpj, contact_name, contact_email, contact_phone (campos fabricante)
  └── products

cost_centers
  │ uuid, tenant_id, parent_id (self FK), code, name, type, is_active
  └── (future: financial_entries)
```

---

## 3. Tabelas Novas

### 3.1 `seller_profiles`

```sql
uuid           PK
tenant_id      FK tenants.uuid CASCADE
user_id        FK users.uuid CASCADE UNIQUE (1:1)
code           VARCHAR(20) NULLABLE
is_active      BOOLEAN DEFAULT TRUE
commission_rate DECIMAL(5,2) DEFAULT 0.00
notes          TEXT NULLABLE
created_at, updated_at, deleted_at
```

Índices:
- `UNIQUE (tenant_id, code)` parcial onde `code IS NOT NULL`
- `UNIQUE (tenant_id, user_id)`

### 3.2 `supplier_addresses`

```sql
uuid          PK
supplier_id   FK suppliers.uuid CASCADE
zipcode       VARCHAR(10)
street        VARCHAR(200)
number        VARCHAR(20)
complement    VARCHAR(100) NULLABLE
district      VARCHAR(100)
city          VARCHAR(100)
state         CHAR(2)
country       CHAR(2) DEFAULT 'BR'
is_default    BOOLEAN DEFAULT FALSE
created_at, updated_at
```

### 3.3 `supplier_contacts`

```sql
uuid          PK
supplier_id   FK suppliers.uuid CASCADE
type          VARCHAR(20)  -- PHONE|WHATSAPP|EMAIL|OTHER
value         VARCHAR(200)
label         VARCHAR(100) NULLABLE
is_primary    BOOLEAN DEFAULT FALSE
created_at, updated_at
```

### 3.4 `carriers`

```sql
uuid          PK
tenant_id     FK tenants.uuid CASCADE
code          VARCHAR(20) NULLABLE
name          VARCHAR(200)
trade_name    VARCHAR(200) NULLABLE
cnpj          VARCHAR(20) NULLABLE
ie            VARCHAR(30) NULLABLE
email         VARCHAR(254) NULLABLE
phone         VARCHAR(30) NULLABLE
notes         TEXT NULLABLE
is_active     BOOLEAN DEFAULT TRUE
created_at, updated_at, deleted_at
```

Índice: `UNIQUE (tenant_id, code)` where `code IS NOT NULL`

### 3.5 `carrier_addresses`

Schema idêntico a `supplier_addresses` com FK `carrier_id`.

### 3.6 `carrier_contacts`

Schema idêntico a `supplier_contacts` com FK `carrier_id`.

### 3.7 `partner_professionals`

```sql
uuid          PK
tenant_id     FK tenants.uuid CASCADE
code          VARCHAR(20) NULLABLE
type          VARCHAR(30)  -- MASON|FOREMAN|ARCHITECT|ENGINEER|DESIGNER|OTHER
name          VARCHAR(200)
document      VARCHAR(20) NULLABLE   -- CPF
email         VARCHAR(254) NULLABLE
phone         VARCHAR(30) NULLABLE
notes         TEXT NULLABLE
is_active     BOOLEAN DEFAULT TRUE
created_at, updated_at, deleted_at
```

### 3.8 `partner_contacts`

Schema idêntico a `supplier_contacts` com FK `partner_id`.

### 3.9 `cost_centers`

```sql
uuid          PK
tenant_id     FK tenants.uuid CASCADE
parent_id     FK cost_centers.uuid NULLABLE (self-ref)
code          VARCHAR(20)
name          VARCHAR(100)
type          VARCHAR(30)  -- ADMINISTRATIVE|COMMERCIAL|LOGISTICS|PURCHASING|FINANCIAL
is_active     BOOLEAN DEFAULT TRUE
created_at, updated_at, deleted_at
```

Índice: `UNIQUE (tenant_id, code)`

---

## 4. Modificações em Tabelas Existentes

### 4.1 `customers` — adicionar campos

```
credit_limit  DECIMAL(12,2) DEFAULT 0
situation     VARCHAR(20) DEFAULT 'active'  -- active|blocked|overdue
rg            VARCHAR(20) NULLABLE
ie            VARCHAR(30) NULLABLE
im            VARCHAR(20) NULLABLE
```

### 4.2 `suppliers` — adicionar campos

```
ie   VARCHAR(30) NULLABLE
im   VARCHAR(20) NULLABLE
```

### 4.3 `catalog_brands` — campos de fabricante

```
manufacturer_cnpj          VARCHAR(20) NULLABLE
manufacturer_contact_name  VARCHAR(150) NULLABLE
manufacturer_contact_email VARCHAR(254) NULLABLE
manufacturer_contact_phone VARCHAR(30) NULLABLE
```

---

## 5. Módulos Backend — Estrutura

```
app/Modules/
  Customers/          [existe — enriquecer]
  Purchasing/
    Models/Supplier.php [enriquecer: ie, im]
    Models/SupplierAddress.php [novo]
    Models/SupplierContact.php [novo]
  Carriers/           [novo módulo]
    Models/Carrier.php
    Models/CarrierAddress.php
    Models/CarrierContact.php
    Http/Controllers/CarrierController.php
    Http/Resources/CarrierResource.php
  Sellers/            [novo módulo]
    Models/SellerProfile.php
    Http/Controllers/SellerController.php
    Http/Resources/SellerResource.php
  Partners/           [novo módulo]
    Models/PartnerProfessional.php
    Models/PartnerContact.php
    Http/Controllers/PartnerController.php
    Http/Resources/PartnerResource.php
  Finance/
    Models/CostCenter.php  [novo]
    Http/Controllers/CostCenterController.php [novo]
    Http/Resources/CostCenterResource.php [novo]
  Catalog/
    Models/Brand.php [atualizar com campos fabricante]
```

---

## 6. Rotas API

```
GET|POST   /carriers
GET|PUT|DELETE /carriers/{carrier}
GET|POST   /carriers/{carrier}/addresses
PUT|DELETE /carriers/{carrier}/addresses/{address}
GET|POST   /carriers/{carrier}/contacts
PUT|DELETE /carriers/{carrier}/contacts/{contact}

GET|POST   /suppliers/{supplier}/addresses
PUT|DELETE /suppliers/{supplier}/addresses/{address}
GET|POST   /suppliers/{supplier}/contacts
PUT|DELETE /suppliers/{supplier}/contacts/{contact}

GET|POST   /sellers
GET|PUT    /sellers/{seller}

GET|POST   /partner-professionals
GET|PUT|DELETE /partner-professionals/{partner}

GET|POST   /cost-centers
GET|PUT|DELETE /cost-centers/{costCenter}
GET        /cost-centers/tree   (hierarquia completa)
```

---

## 7. Frontend — Páginas Novas

```
/suppliers              → lista + CRUD completo (mover de /purchasing/suppliers)
/carriers               → lista + CRUD completo
/sellers                → lista + ativar/inativar
/partners               → lista + CRUD completo
/cost-centers           → lista hierárquica + CRUD
```

---

## 8. Permissões Novas

```php
// carriers
case CarriersView   = 'carriers.view';
case CarriersCreate = 'carriers.create';
case CarriersUpdate = 'carriers.update';
case CarriersDelete = 'carriers.delete';

// sellers
case SellersView   = 'sellers.view';
case SellersUpdate = 'sellers.update';

// partners
case PartnersView   = 'partners.view';
case PartnersCreate = 'partners.create';
case PartnersUpdate = 'partners.update';
case PartnersDelete = 'partners.delete';

// cost_centers
case CostCentersView   = 'cost_centers.view';
case CostCentersCreate = 'cost_centers.create';
case CostCentersUpdate = 'cost_centers.update';
case CostCentersDelete = 'cost_centers.delete';

// suppliers (faltando)
case SuppliersDelete = 'suppliers.delete';
```

---

## 9. Integrações Futuras

| Entidade | Usada por |
|---|---|
| Clientes | CRM, Pedidos, Financeiro, Entregas |
| Fornecedores | Compras, Recebimento, Financeiro, NF-e |
| Transportadoras | Entregas, NF-e, Logística |
| Vendedores | Orçamentos, Pedidos, Comissões (futuro), CRM |
| Parceiros | Comissão por indicação, CRM |
| Marcas/Fabricantes | Catálogo, Produtos |
| Centros de Custo | Financeiro, BI, Relatórios |

---

## 10. Ordem de Implementação

```
Fase 3  → Enricher customers (migration + model + resource + frontend)
Fase 4  → Enrich suppliers (ie/im + addresses/contacts + tela própria)
Fase 5  → Carriers (tudo do zero)
Fase 6  → Sellers (seller_profiles)
Fase 7  → Partners (tudo do zero)
Fase 8  → Manufacturers (estender Brand)
Fase 9  → Brands (validar — OK)
Fase 10 → Cost Centers (tudo do zero)
Fase 11 → Endereços/Contatos — validar duplicação
Fase 12 → CEP/CNPJ arquitetura
Fase 13 → Frontend (completar telas)
```

---
*Documento gerado na Fase 2 do Módulo 04 — Cadastros Mestres.*
