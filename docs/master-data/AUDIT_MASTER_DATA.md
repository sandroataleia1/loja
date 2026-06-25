# Módulo 04 — Cadastros Mestres — Auditoria Completa

**Data:** 2026-06-23  
**Auditor:** Fase 1 — pré-implementação

---

## 1. Resumo Executivo

| Entidade               | Backend         | Frontend           | Status         |
|---|---|---|---|
| Clientes               | ✅ Completo     | ✅ Completo         | Enriquecer campos |
| Fornecedores           | ⚠️ Básico       | ⚠️ Modal inline     | Enriquecer + endereços |
| Transportadoras        | ❌ Inexistente  | ❌ Inexistente      | Criar do zero  |
| Vendedores             | ⚠️ User + PIN   | ❌ Sem tela         | Criar seller profile |
| Profissionais Parceiros| ❌ Inexistente  | ❌ Inexistente      | Criar do zero  |
| Fabricantes            | ❌ Inexistente  | ❌ Inexistente      | Criar do zero  |
| Marcas                 | ✅ Completo     | ✅ Completo         | OK (Catálogo)  |
| Centros de Custo       | ❌ Inexistente  | ❌ Inexistente      | Criar do zero  |

---

## 2. CLIENTES

### 2.1 O que existe

**Backend — `app/Modules/Customers/`**

| Arquivo | Estado |
|---|---|
| `Models/Customer.php` | ✅ Completo |
| `Models/CustomerAddress.php` | ✅ Completo |
| `Models/CustomerContact.php` | ✅ Completo |
| `Models/CustomerTag.php` | ✅ Completo |
| `Http/Controllers/CustomerController.php` | ✅ Completo (CRUD + sub-resources) |
| `Http/Requests/StoreCustomerRequest.php` | ✅ Existe |
| `Http/Requests/UpdateCustomerRequest.php` | ✅ Existe |
| `Http/Resources/CustomerResource.php` | ✅ Completo |
| `Actions/CreateCustomerAction.php` | ✅ Existe |
| `Actions/UpdateCustomerAction.php` | ✅ Existe |
| `DTOs/CreateCustomerDTO.php` | ✅ Existe |
| `DTOs/UpdateCustomerDTO.php` | ✅ Existe |
| `Enums/PersonTypeEnum.php` | ✅ INDIVIDUAL / COMPANY |
| `Enums/ContactTypeEnum.php` | ✅ PHONE, WHATSAPP, EMAIL, INSTAGRAM, OTHER |
| `Events/Customer*.php` | ✅ Created, Updated, Deleted, Activated, Deactivated |
| `Http/Policies/CustomerPolicy.php` | ✅ Existe |

**Migrations:**
- `2026_05_29_000006_create_customers_table.php` — tabela base
- `2026_06_01_000003_evolve_customers_table.php` — person_type, document, birth_date, etc.
- `2026_06_01_000004_create_customer_addresses_table.php` — endereços
- `2026_06_01_000005_create_customer_contacts_table.php` — contatos
- `2026_06_01_000006_create_customer_tags_tables.php` — tags

**Frontend:**
- `/customers` — lista com busca e paginação
- `/customers/create` — formulário de criação
- `/customers/[uuid]` — detalhe com abas (endereços, contatos, tags, observações)
- `/customers/[uuid]/edit` — formulário de edição

### 2.2 Problemas encontrados

| Problema | Gravidade | Descrição |
|---|---|---|
| Campos `cpf` e `cnpj` legados no model fillable | Média | A migration original criou `cpf` e `cnpj` separados; a evolução adicionou `document` unificado mas não removeu as colunas antigas. O model fillable lista ambos. |
| `credit_limit` ausente | Alta | O roadmap exige campo de Limite de Crédito no cadastro de cliente. Não existe na tabela nem no model. |
| `rg` ausente | Baixa | Para PF, o RG não está mapeado. |
| `ie` / `im` ausentes | Média | Inscrição Estadual e Municipal (PJ) não estão no modelo. |
| `situation` ausente | Média | Campo Situação (ativo/inadimplente/bloqueado) além do simples `is_active`. |

### 2.3 O que precisa ser criado

- Migration `add_credit_fields_to_customers` — `credit_limit`, `rg`, `ie`, `im`, `situation`
- Atualizar model, resource e requests

---

## 3. FORNECEDORES

### 3.1 O que existe

**Backend — `app/Modules/Purchasing/`**

| Arquivo | Estado |
|---|---|
| `Models/Supplier.php` | ⚠️ Básico (sem IE, sem endereços) |
| `Http/Controllers/SupplierController.php` | ⚠️ CRUD básico (sem endereços sub-resource) |
| `Actions/CreateSupplierAction.php` | ✅ Existe |
| `Http/Resources/SupplierResource.php` | ⚠️ Básico |

**Tabela `suppliers`:**
```
uuid, tenant_id, code, person_type, name, trade_name, document, email, phone, is_active, notes
```

**Frontend:**
- `/purchasing/suppliers` — lista inline com dialog de criação/edição (sem páginas dedicadas)

### 3.2 Problemas encontrados

| Problema | Gravidade | Descrição |
|---|---|---|
| Sem endereços sub-resource | Alta | Diferente de clientes, fornecedores não têm tabela de endereços |
| Sem contatos sub-resource | Média | Só `email` e `phone` inline — sem histórico de contatos |
| `ie` / `im` ausentes | Média | Campos fiscais importantes para compras/NF-e |
| Sem página dedicada | Média | Supplier está embutido em /purchasing, não tem tela própria |
| Sem política de autorização | Média | Sem `SupplierPolicy.php` |
| `SuppliersDelete` ausente | Média | Permissão de exclusão não existe no `PermissionEnum` |

### 3.3 O que precisa ser criado

- Migration `add_fiscal_fields_to_suppliers` — `ie`, `im`
- `supplier_addresses` table + model + sub-resource no controller
- `supplier_contacts` table + model + sub-resource (ou reutilizar modelo genérico)
- Frontend: `/suppliers` como rota independente (não dentro de /purchasing)
- Adicionar `SuppliersDelete` no PermissionEnum

---

## 4. TRANSPORTADORAS

### 4.1 O que existe

**Nada.** Não há modelo, migration, controller, rota, nem página frontend.

### 4.2 O que precisa ser criado

- `carrier_profiles` table (ou `carriers`)
- Model `Carrier`
- Controller `CarrierController`
- Rota API `/carriers`
- Permissões: `carriers.view`, `carriers.create`, `carriers.update`, `carriers.delete`
- Frontend: `/carriers` (lista + modal ou páginas dedicadas)

**Campos mínimos:**
```
uuid, tenant_id, code, name, trade_name, cnpj, ie, email, phone, notes, is_active
```

**Preparar para:** Entregas, NF-e (transportadora na NF), Logística

---

## 5. VENDEDORES

### 5.1 O que existe

A relação **Usuário → Vendedor** existe via:
- `users.pin` — PIN numérico para identificação na venda
- `quotes.seller_id → users.uuid` — vendedor no orçamento
- `orders.seller_id → users.uuid` — vendedor no pedido

Não existe um `sellers` ou `seller_profiles` separado.

### 5.2 Problemas encontrados

| Problema | Gravidade | Descrição |
|---|---|---|
| Sem cadastro de vendedor | Alta | Não há como gerenciar vendedores separadamente de usuários |
| Sem código de vendedor | Média | Users não têm código de vendedor |
| Sem flag `is_seller` | Média | Não há como filtrar usuários que são vendedores |
| Sem meta/comissão | Baixa | Campos futuros não preparados |

### 5.3 Decisão arquitetural

**Abordagem recomendada:** criar `seller_profiles` como extensão 1:1 de `users`.  
Evita duplicar usuário, permite campos específicos de vendedor (código, comissão, meta).

```
seller_profiles: uuid, tenant_id, user_id FK users.uuid, code, is_active, commission_rate, notes
```

---

## 6. PROFISSIONAIS PARCEIROS

### 6.1 O que existe

**Nada.** Específico do domínio de material de construção.

### 6.2 O que precisa ser criado

- `partner_professionals` table
- Model `PartnerProfessional`
- Controller `PartnerProfessionalController`
- Rota API `/partner-professionals`
- Permissões: `partners.view`, `partners.create`, `partners.update`, `partners.delete`
- Frontend: `/partners`

**Campos mínimos:**
```
uuid, tenant_id, code, type (MASON|FOREMAN|ARCHITECT|ENGINEER|DESIGNER|OTHER),
name, document, email, phone, notes, is_active
```

**Preparar para:** comissão por indicação, CRM, relatórios

---

## 7. FABRICANTES

### 7.1 O que existe

**Nada** como entidade separada.  
O modelo `Brand` (`catalog_brands`) cobre parte dos fabricantes no domínio de construção.

### 7.2 Análise

Em material de construção:
- **Marca** = identidade comercial (Suvinil, Tigre, Votoran)
- **Fabricante** = empresa que produz (pode ser mesma da marca ou OEM)

Para o escopo atual, **Fabricante pode ser implementado como extensão da Marca** com campos adicionais (CNPJ do fabricante, contato técnico). Evita duplicação de entidade.

### 7.3 O que precisa ser criado

- Migration `add_manufacturer_fields_to_catalog_brands` — `cnpj`, `contact_name`, `contact_email`, `contact_phone`
- Atualizar model `Brand`, resource e controller

---

## 8. MARCAS

### 8.1 O que existe

**Backend — `app/Modules/Catalog/Models/Brand.php`**
```
uuid, tenant_id, code, name, slug, description, logo_url, website_url, is_active, metadata
```

Completo para o domínio de catálogo. Controller, resource, rotas, frontend — todos existem.

### 8.2 Problemas encontrados

Nenhum crítico. Apenas incorporar campos de fabricante (ver §7.3).

---

## 9. CENTROS DE CUSTO

### 9.1 O que existe

**Nada.**

### 9.2 O que precisa ser criado

- `cost_centers` table com suporte a hierarquia (parent_id)
- Model `CostCenter`
- Controller `CostCenterController`
- Rota API `/cost-centers`
- Permissões: `cost_centers.view`, `cost_centers.create`, `cost_centers.update`, `cost_centers.delete`
- Frontend: `/cost-centers` com tree view

**Campos mínimos:**
```
uuid, tenant_id, parent_id (nullable FK self), code, name, type (ADMIN|COMMERCIAL|LOGISTICS|PURCHASING|FINANCIAL), is_active
```

**Preparar para:** Financeiro, BI, Relatórios

---

## 10. ENDEREÇOS E CONTATOS — MODELO REUTILIZÁVEL

### 10.1 Situação atual

- `customer_addresses` — exclusivo de clientes (zipcode, street, number, complement, district, city, state, country)
- `customer_contacts` — exclusivo de clientes (type, value, label, is_primary)
- Fornecedores — sem tabelas dedicadas (apenas `email` e `phone` inline)
- Transportadoras, Parceiros — sem tabelas

### 10.2 Recomendação

Criar **tabelas polimórficas** reutilizáveis:

**Opção A — Tabelas específicas por entidade (atual pattern):**
Criar `supplier_addresses`, `carrier_addresses`, etc. seguindo o mesmo schema de `customer_addresses`.

**Opção B — Tabela polimórfica:**
```
entity_addresses: addressable_type, addressable_id + campos de endereço
entity_contacts:  contactable_type, contactable_id + campos de contato
```

**Decisão: Opção A** — mais simples, sem polimorfismo complexo, FK tipada, mais fácil de auditar.

---

## 11. CONSULTA DE CEP / CNPJ (Fase 12)

### 11.1 Situação atual

Não há integração com ViaCEP ou Receita Federal.

### 11.2 Arquitetura preparatória

Criar serviço PHP `CepLookupService` + `CnpjLookupService` com interface, sem implementação real ainda. Endpoints:
- `GET /utilities/cep/{cep}` — retorna dados de endereço
- `GET /utilities/cnpj/{cnpj}` — retorna dados da empresa

---

## 12. PERMISSÕES FALTANTES

As seguintes permissões precisam ser adicionadas ao `PermissionEnum`:

```
carriers.view, carriers.create, carriers.update, carriers.delete
sellers.view, sellers.create, sellers.update, sellers.delete  
partners.view, partners.create, partners.update, partners.delete
cost_centers.view, cost_centers.create, cost_centers.update, cost_centers.delete
suppliers.delete  (faltando)
```

---

## 13. ROTAS FRONTEND FALTANTES

As seguintes rotas precisam ser adicionadas ao `routes.ts`:

```typescript
SUPPLIERS:                '/suppliers',          // mover de /purchasing/suppliers
CARRIERS:                 '/carriers',
SELLERS:                  '/sellers',
PARTNERS:                 '/partners',
COST_CENTERS:             '/cost-centers',
```

---

## 14. ITENS DO ROADMAP — MAPEAMENTO

| Fase | Entidade | Estado atual | Ação necessária |
|---|---|---|---|
| 3 | Clientes | ✅ Existe | Adicionar credit_limit, rg, ie, im, situation |
| 4 | Fornecedores | ⚠️ Básico | Adicionar ie/im, endereços, contatos, tela própria |
| 5 | Transportadoras | ❌ | Criar do zero |
| 6 | Vendedores | ⚠️ Parcial | Criar seller_profiles como extensão de User |
| 7 | Parceiros | ❌ | Criar do zero |
| 8 | Fabricantes | ❌ | Estender Brand com campos de fabricante |
| 9 | Marcas | ✅ | OK — validar apenas |
| 10 | Centros de Custo | ❌ | Criar do zero |
| 11 | Endereços/Contatos | ⚠️ | Criar supplier_addresses + carrier_addresses |
| 12 | CEP/CNPJ | ❌ | Preparar arquitetura (sem integrações externas) |
| 13 | Frontend | ⚠️ | Implementar telas faltantes |
| 14 | Auditoria | — | Pós-implementação |
| 15 | Qualidade | — | Pós-implementação |
| 16 | Auditoria Final | — | Pós-implementação |

---

## 15. ORDEM DE IMPLEMENTAÇÃO RECOMENDADA

1. **Permissões** — adicionar ao PermissionEnum (fundação para RBAC)
2. **Migrations** — customers (enriquecimento), suppliers (ie/im + endereços), carriers, seller_profiles, partners, cost_centers, brand (fabricante)
3. **Models + Resources** — para cada nova entidade
4. **Controllers + Routes** — CRUD completo com autorização
5. **BootstrapTenantAction** — seeder de centros de custo padrão
6. **Frontend** — pages + services + hooks + sidebar
7. **Qualidade** — typecheck + build

---
*Documento gerado na Fase 1 do Módulo 04 — Cadastros Mestres.*
