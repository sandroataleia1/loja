# Módulo 04 — Cadastros Mestres — Final Audit

**Data:** 2026-06-23  
**Build:** `next build` — 0 erros, 0 warnings TypeScript

---

## Status por Fase

| Fase | Descrição | Status |
|------|-----------|--------|
| 01 | Auditoria completa do projeto | ✅ |
| 02 | Arquitetura e plano técnico | ✅ |
| 03 | Clientes: credit_limit, rg, ie, im, situation | ✅ |
| 04 | Fornecedores: ie/im, endereços, contatos, tela própria `/suppliers` | ✅ |
| 05 | Transportadoras: migration, model, controller, rotas | ✅ |
| 06 | Vendedores: seller_profiles (1:1 com users) | ✅ |
| 07 | Profissionais Parceiros: do zero | ✅ |
| 08 | Fabricantes: extensão de catalog_brands | ✅ |
| 09 | Centros de Custo: hierarquia self-referencing | ✅ |
| 10 | Endereços/Contatos: sub-resources por entidade | ✅ |
| 11 | Permissions: 13 novas cases em PermissionEnum | ✅ |
| 12 | Fase preparatória CEP/CNPJ (sem integração externa) | ✅ |
| 13 | Frontend: services, hooks, páginas | ✅ |
| 14 | Auditoria de segurança | ✅ |
| 15 | Qualidade: tsc + build | ✅ |
| 16 | Auditoria final | ✅ |

---

## Migrations (11 novas)

| Arquivo | Tabela | Idempotente |
|---------|--------|-------------|
| `2026_06_23_200001_add_credit_fields_to_customers` | customers | ✅ hasColumn |
| `2026_06_23_200002_add_fiscal_fields_to_suppliers` | suppliers | ✅ hasColumn |
| `2026_06_23_200003_create_supplier_addresses_table` | supplier_addresses | ✅ hasTable |
| `2026_06_23_200004_create_supplier_contacts_table` | supplier_contacts | ✅ hasTable |
| `2026_06_23_200005_create_carriers_table` | carriers | ✅ hasTable |
| `2026_06_23_200006_create_carrier_addresses_table` | carrier_addresses | ✅ hasTable |
| `2026_06_23_200007_create_carrier_contacts_table` | carrier_contacts | ✅ hasTable |
| `2026_06_23_200008_create_seller_profiles_table` | seller_profiles | ✅ hasTable |
| `2026_06_23_200009_create_partner_professionals_table` | partner_professionals, partner_contacts | ✅ hasTable |
| `2026_06_23_200010_create_cost_centers_table` | cost_centers | ✅ hasTable |
| `2026_06_23_200011_add_manufacturer_fields_to_catalog_brands` | catalog_brands | ✅ hasColumn |

---

## Backend — Novos Módulos

| Módulo | Models | Controller | Resource | Rotas |
|--------|--------|------------|----------|-------|
| Carriers | Carrier, CarrierAddress, CarrierContact | ✅ | ✅ | `/api/v1/carriers` |
| Sellers | SellerProfile | ✅ | ✅ | `/api/v1/sellers` |
| Partners | PartnerProfessional, PartnerContact | ✅ | ✅ | `/api/v1/partner-professionals` |
| Finance/CostCenters | CostCenter | ✅ | ✅ | `/api/v1/cost-centers` |

## Backend — Módulos Atualizados

| Módulo | Alteração |
|--------|-----------|
| Customers | +credit_limit, rg, ie, im, situation no model/DTO/request |
| Purchasing/Supplier | +ie, im, addresses, contacts no model/resource/controller |
| Catalog/Brand | +manufacturer_cnpj/contact_name/email/phone |
| PermissionEnum | +13 novos casos |
| BootstrapTenantAction | Seed 5 centros de custo padrão |

---

## Frontend — Novas Páginas

| Rota | Tamanho | Funcionalidade |
|------|---------|----------------|
| `/suppliers` | 7.74 kB | Lista + CRUD completo (substitui `/purchasing/suppliers`) |
| `/carriers` | — | Lista + CRUD completo |
| `/sellers` | 6.76 kB | Lista + habilitar/editar vendedores |
| `/partners` | — | Lista + CRUD completo |
| `/cost-centers` | — | Árvore hierárquica interativa |

## Frontend — Novos Services/Hooks

- `carriers.service.ts` + `features/carriers/hooks.ts`
- `sellers.service.ts` + `features/sellers/hooks.ts`
- `partners.service.ts` + `features/partners/hooks.ts`
- `cost-centers.service.ts` + `features/cost-centers/hooks.ts`
- `purchasing.service.ts` atualizado (ie, im, addresses, contacts)
- `shared-types.ts` — Supplier atualizado (ie, im)

## Frontend — Novos Componentes UI

- `components/ui/switch.tsx` — Toggle customizado
- `components/ui/select.tsx` — Select dropdown customizado

---

## Segurança

- ✅ Todos os novos controllers usam `TenantContext::getIdOrFail()`
- ✅ Todas as rotas de escrita têm `permission:*` middleware
- ✅ Todos os modelos têm `tenant_id` no `$fillable`
- ✅ Seller: validação `user belongs to same tenant`
- ✅ CostCenter: impede exclusão se tiver filhos
- ✅ Route protection via `ROUTE_PERMISSIONS` no `layout.tsx`
- ✅ Sidebar respeita `permission:*` por item

---

## Decisões de Arquitetura

| Entidade | Decisão |
|----------|---------|
| Vendedores | `seller_profiles` 1:1 com `users` — sem duplicar entidade |
| Fabricantes | Campos adicionais em `catalog_brands` — sem nova tabela |
| Endereços/Contatos | Tabelas específicas por entidade (não polimórfico) |
| Centros de Custo | Self-referencing `parent_id` para hierarquia N níveis |
| `/purchasing/suppliers` | Redirect → `/suppliers` (tela própria) |

---

## Build Output

```
tsc --noEmit: 0 errors
next build:   0 errors, 0 warnings
```

**Módulo 04 — Cadastros Mestres: COMPLETO**
