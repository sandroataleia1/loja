# ARCHITECTURE.md
# Módulo 03 — Configurações do Sistema
# Arquitetura — 2026-06-23

---

## 1. OBJETIVO

Centralizar todas as configurações operacionais do ERP em uma única estrutura coesa.

Estas configurações são do **tenant** (empresa), não do sistema global.

Cada tenant tem suas próprias configurações independentes.

---

## 2. PRINCÍPIOS DE DESIGN

1. **Um registro por tenant** — padrão já estabelecido por `TenantFiscalSettings`
2. **JSONB por domínio** — cada seção é um objeto JSON tipado
3. **Defaults semânticos** — valores padrão explícitos no backend
4. **API por seção** — cada seção é atualizada de forma independente
5. **Reutilizar feature flags existentes** — TenantFeature + TenantFeatureEnum
6. **Permissões existentes** — `settings.view` e `settings.update`

---

## 3. MODELAGEM

### 3.1 Tabela `tenant_settings`

```sql
CREATE TABLE tenant_settings (
    uuid        UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id   UUID NOT NULL REFERENCES tenants(uuid) ON DELETE CASCADE,
    commercial  JSONB DEFAULT '{}',
    financial   JSONB DEFAULT '{}',
    credit      JSONB DEFAULT '{}',
    inventory   JSONB DEFAULT '{}',
    billing     JSONB DEFAULT '{}',
    commission  JSONB DEFAULT '{}',
    logistics   JSONB DEFAULT '{}',
    created_at  TIMESTAMP,
    updated_at  TIMESTAMP,
    UNIQUE (tenant_id)
);
```

### 3.2 Estrutura das Seções

#### commercial
```json
{
  "require_salesperson": false,
  "require_quote_before_order": false,
  "allow_order_without_stock": false,
  "allow_free_discount": true,
  "default_discount_limit": 10,
  "require_discount_approval": false,
  "require_price_approval": false,
  "use_price_table": false,
  "quote_validity_days": 30
}
```

#### financial
```json
{
  "default_interest_rate": 1.0,
  "default_fine_rate": 2.0,
  "default_discount_rate": 0.0,
  "tolerance_days": 0,
  "rounding_mode": "half_up",
  "auto_generate_bills": true,
  "auto_apply_customer_credit": false
}
```

#### credit
```json
{
  "default_credit_limit": 0,
  "block_sale_without_credit": false,
  "allow_exceed_limit": true,
  "require_approval_to_exceed": false
}
```

#### inventory
```json
{
  "allow_negative_stock": false,
  "auto_reserve": true,
  "auto_deduct": true,
  "require_picking": false,
  "require_shipping": false,
  "require_counting": false,
  "auto_update_cost": true
}
```

#### billing
```json
{
  "billing_mode": "by_order"
}
```

Valores permitidos de `billing_mode`: `by_order`, `by_invoice`, `future`.

#### commission
```json
{
  "commission_on_sale": true,
  "commission_on_payment": false,
  "proportional_commission": false,
  "commission_by_margin": false
}
```

#### logistics
```json
{
  "require_picking": false,
  "require_shipping": false,
  "require_packing_list": false,
  "require_delivery": false,
  "require_delivery_receipt": false
}
```

### 3.3 Feature Flags — TenantFeatureEnum (expandido)

Casos existentes (não alterar):
- `fiscal_enabled`
- `financial_enabled`
- `multi_store_enabled`
- `offline_first_enabled`
- `conditional_order_enabled`
- `social_commerce_enabled`
- `ai_campaigns_enabled`
- `omnichannel_enabled`

Novos casos a adicionar:
- `purchasing_enabled` — Módulo de Compras
- `logistics_enabled` — Módulo de Logística
- `commissions_enabled` — Módulo de Comissões
- `crm_enabled` — Módulo CRM
- `bi_enabled` — Módulo BI / Analytics
- `nfe_enabled` — NF-e (Nota Fiscal Eletrônica)
- `custom_price_table_enabled` — Tabela de Preços personalizada

---

## 4. ENTIDADES

### TenantSettings (model)
- Namespace: `App\Core\Tenancy\Models\TenantSettings`
- Tabela: `tenant_settings`
- Primary key: `uuid`
- Unique: `tenant_id`
- Casts: cada seção como `array`
- Defaults: definidos via `$attributes` com valores semânticos

### TenantFeatureEnum (expandido)
- Namespace: `App\Core\Tenancy\Enums\TenantFeatureEnum`
- +7 casos (purchasing, logistics, commissions, crm, bi, nfe, custom_price_table)

---

## 5. APIs

### 5.1 Rotas

Grupo: `GET/PUT /system-settings`
Middleware: `auth:sanctum` + `tenant` + `permission:settings.view`

| Endpoint | Método | Permissão | Descrição |
|----------|--------|-----------|-----------|
| `/system-settings` | GET | settings.view | Retorna todas as configurações |
| `/system-settings/commercial` | PUT | settings.update | Atualiza configurações comerciais |
| `/system-settings/financial` | PUT | settings.update | Atualiza configurações financeiras |
| `/system-settings/credit` | PUT | settings.update | Atualiza configurações de crédito |
| `/system-settings/inventory` | PUT | settings.update | Atualiza configurações de estoque |
| `/system-settings/billing` | PUT | settings.update | Atualiza configurações de faturamento |
| `/system-settings/commission` | PUT | settings.update | Atualiza configurações de comissão |
| `/system-settings/logistics` | PUT | settings.update | Atualiza configurações de logística |
| `/system-settings/features` | PUT | settings.update | Ativa/desativa feature flags |
| `/system-settings/features` | GET | settings.view | Lista feature flags |

### 5.2 Payload GET /system-settings

```json
{
  "data": {
    "commercial":  { ... },
    "financial":   { ... },
    "credit":      { ... },
    "inventory":   { ... },
    "billing":     { ... },
    "commission":  { ... },
    "logistics":   { ... }
  }
}
```

### 5.3 Payload PUT /system-settings/{section}

```json
{
  "require_salesperson": true,
  "default_discount_limit": 15
}
```

Atualização parcial (merge) — campos não enviados mantêm seu valor atual.

### 5.4 Payload GET /system-settings/features

```json
{
  "data": [
    { "feature": "fiscal_enabled",      "label": "Módulo fiscal",    "is_enabled": true },
    { "feature": "financial_enabled",   "label": "Módulo financeiro", "is_enabled": true },
    { "feature": "purchasing_enabled",  "label": "Módulo de Compras", "is_enabled": false },
    ...
  ]
}
```

### 5.5 Payload PUT /system-settings/features

```json
{
  "features": {
    "purchasing_enabled": true,
    "logistics_enabled":  false
  }
}
```

---

## 6. CONTROLLER

```
App\Core\Tenancy\Http\Controllers\TenantSettingsController
  - show()                     → GET  /system-settings
  - update(section, request)   → PUT  /system-settings/{section}
  - showFeatures()             → GET  /system-settings/features
  - updateFeatures(request)    → PUT  /system-settings/features
```

Validação:
- Seções válidas: `commercial`, `financial`, `credit`, `inventory`, `billing`, `commission`, `logistics`
- Validação dos campos por seção usando rules definidas no controller
- 403 se `section` for inválido

---

## 7. PERMISSÕES

| Operação | Permissão |
|----------|-----------|
| Ler configurações | `settings.view` |
| Alterar configurações | `settings.update` |

Estas permissões já existem em `PermissionEnum` e são atribuídas ao role `owner` no bootstrap do tenant.

---

## 8. TELAS

### 8.1 Localização

Nova página: `frontend/src/app/(app)/settings/system/page.tsx`

Rota: `/settings/system`

### 8.2 Estrutura de Abas

| Aba | Seção | Descrição |
|-----|-------|-----------|
| Geral | — | Dados da empresa (existente, somente leitura) |
| Comercial | commercial | Vendedor, orçamento, desconto, aprovações |
| Financeiro | financial | Juros, multas, arredondamento, títulos |
| Crédito | credit | Limite, bloqueio, aprovação |
| Estoque | inventory | Negativo, reserva, baixa, conferência |
| Faturamento | billing | Modo de faturamento |
| Comissão | commission | Tipo de comissão |
| Logística | logistics | Separação, expedição, entrega |
| Recursos | features | Feature flags — ativar/desativar módulos |

### 8.3 Integração no Header

Adicionar ícone `Settings` no `header.tsx` com:
- Link para `/settings/system`
- Visível apenas para usuários com `settings.view`
- Tooltip: "Configurações do Sistema"

### 8.4 Sidebar

No grupo "Configurações", adicionar:
```
{ label: 'Sistema', href: '/settings/system', icon: Cog, permission: 'settings.view' }
```

---

## 9. BOOTSTRAP DO TENANT

Ao criar um novo tenant (`BootstrapTenantAction`), criar automaticamente:
1. `TenantSettings` com defaults — método `TenantSettings::createDefaults($tenantId)`
2. Todos os `TenantFeature` com `is_enabled = false` como ponto de partida

---

## 10. FLUXO DE MIGRAÇÃO

### CommercialSettings (localStorage → API)

1. API retorna `commercial.quote_validity_days` (novo campo no backend)
2. Frontend usa `useSystemSettings()` em vez de `loadCommercialSettings()`
3. O `commercial-settings-modal.tsx` lê da API e salva na API
4. Remoção da dependência de localStorage

---

*Gerado em: 2026-06-23*
*Módulo 03 — Configurações do Sistema — ARQUITETURA*
