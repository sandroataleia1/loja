# AUDIT_SYSTEM_SETTINGS.md
# Módulo 03 — Configurações do Sistema
# Auditoria Completa — 2026-06-23

---

## 1. RESUMO EXECUTIVO

| Item | Status |
|------|--------|
| Tenant.settings (JSON genérico) | ⚠️ PARCIAL — coluna existe, mas nunca utilizada com estrutura definida |
| TenantFeature / TenantFeatureEnum | ✅ EXISTE — feature flags por módulo (8 casos) |
| TenantFiscalSettings | ✅ EXISTE — configurações fiscais completas em tabela dedicada |
| Configurações comerciais | ❌ INCORRETO — armazenadas em localStorage, não no banco |
| Configurações financeiras | ❌ NÃO IMPLEMENTADO |
| Configurações de crédito | ❌ NÃO IMPLEMENTADO |
| Configurações de estoque | ❌ NÃO IMPLEMENTADO |
| Configurações de faturamento | ❌ NÃO IMPLEMENTADO |
| Configurações de comissão | ❌ NÃO IMPLEMENTADO |
| Configurações de logística | ❌ NÃO IMPLEMENTADO |
| API unificada de configurações | ❌ NÃO IMPLEMENTADO |
| Tela de configurações do sistema | ⚠️ PARCIAL — existe /settings mas apenas exibe dados da empresa |
| Feature flags expandidos | ⚠️ PARCIAL — 8 casos, faltam purchasing, logistics, commissions, crm, bi |
| Ícone de engrenagem no header | ❌ NÃO IMPLEMENTADO |

---

## 2. O QUE JÁ EXISTE

### 2.1 Backend — Estrutura de Dados

#### Tenant (tabela `tenants`)
- **Arquivo:** `backend/app/Core/Tenancy/Models/Tenant.php`
- Campo `settings JSONB` — presente na tabela e no model
- Campo `settings` é genérico, sem estrutura definida
- Nunca populado com configurações operacionais

#### TenantFeature (tabela `tenant_features`)
- **Arquivo:** `backend/app/Core/Tenancy/Models/TenantFeature.php`
- **Migration:** `backend/database/migrations/2026_05_30_000025_create_tenant_features_table.php`
- Modelo completo com `isEnabled()`, `enable()`, `disable()` estáticos
- Campos: `tenant_id`, `feature`, `is_enabled`, `metadata`
- Índice único em `(tenant_id, feature)`

#### TenantFeatureEnum
- **Arquivo:** `backend/app/Core/Tenancy/Enums/TenantFeatureEnum.php`
- 8 casos existentes:
  - `fiscal_enabled` — Módulo fiscal
  - `financial_enabled` — Módulo financeiro
  - `multi_store_enabled` — Multi-loja
  - `offline_first_enabled` — Modo offline (PDV)
  - `conditional_order_enabled` — Pedidos condicionais
  - `social_commerce_enabled` — Social commerce
  - `ai_campaigns_enabled` — Campanhas com IA
  - `omnichannel_enabled` — Omnichannel

#### TenantFiscalSettings (tabela `tenant_fiscal_settings`)
- **Arquivo:** `backend/app/Modules/Fiscal/Models/TenantFiscalSettings.php`
- Configurações fiscais completas: ambiente, séries, certificado, NFC-e, política de emissão
- API completa: `GET /fiscal/settings`, `PUT /fiscal/settings`
- Não duplicar. Este módulo é separado e correto.

### 2.2 Backend — APIs Existentes

| Endpoint | Módulo | Status |
|----------|--------|--------|
| `GET /fiscal/settings` | Fiscal | ✅ Completo |
| `PUT /fiscal/settings` | Fiscal | ✅ Completo |
| `GET /settings/payment-conditions` | Financeiro | ✅ Completo |
| `GET /settings/payment-methods` | Financeiro | ✅ Completo |

**Não existe** endpoint `GET /settings` ou `PUT /settings/{section}` para configurações operacionais.

### 2.3 Backend — Permissões Existentes

- `settings.view` — já existe em `PermissionEnum`
- `settings.update` — já existe em `PermissionEnum`

### 2.4 Frontend — Páginas Existentes

| Rota | Arquivo | Status |
|------|---------|--------|
| `/settings` | `frontend/src/app/(app)/settings/page.tsx` | ⚠️ Somente leitura — exibe dados da empresa |
| `/settings/gateways` | `frontend/src/app/(app)/settings/gateways/page.tsx` | ✅ Config PIX/Asaas |
| `/settings/payment-conditions` | `frontend/src/app/(app)/settings/payment-conditions/page.tsx` | ✅ |
| `/settings/payment-methods` | `frontend/src/app/(app)/settings/payment-methods/page.tsx` | ✅ |
| `/fiscal/settings` | `frontend/src/app/(app)/fiscal/settings/page.tsx` | ✅ Config fiscal completa |

### 2.5 Frontend — Problemas Encontrados

#### CommercialSettings em localStorage (CRÍTICO)
- **Arquivo:** `frontend/src/features/orders/components/commercial-settings-modal.tsx`
- A configuração `default_validity_days` (validade padrão do orçamento) é salva em `localStorage`
- Isso significa que cada navegador/dispositivo tem configurações diferentes
- Não é multi-usuário nem persistente no servidor
- **DEVE SER MIGRADO** para API

#### Header sem ícone de Settings
- O `header.tsx` não tem link para configurações do sistema
- Apenas ThemeToggle, UserMenu, Download, TenantSwitcher, StoreSwitcher

### 2.6 Frontend — Sidebar

- Grupo "Configurações" existe com: Configurações, Cond. Pagamento, Formas Pagamento, Gateways, Downloads
- `/settings` aponta para a página de leitura da empresa
- A nova página de configurações do sistema precisará ser integrada

---

## 3. O QUE ESTÁ DUPLICADO

| Duplicação | Problema | Resolução |
|------------|---------|-----------|
| `Tenant.settings` vs `TenantFiscalSettings` | Fiscal já tem tabela própria, mas settings genérico não está sendo usado | Criar `TenantSettings` dedicado — não usar `Tenant.settings` |
| `CommercialSettings` em localStorage | Não é o lugar correto para configurações do sistema | Migrar para API |

---

## 4. O QUE DEVE SER REAPROVEITADO

| Item | Reutilização |
|------|-------------|
| `TenantFeature` model | Reutilizar integralmente — apenas adicionar novos casos ao enum |
| `TenantFeatureEnum` | Adicionar: `purchasing`, `logistics`, `commissions`, `crm`, `bi`, `nfe`, `custom_price_table` |
| Permissões `settings.view` / `settings.update` | Reutilizar em todos os endpoints de configurações |
| Sidebar grupo "Configurações" | Adicionar link para `/settings/system` |
| Header (`header.tsx`) | Adicionar ícone de engrenagem com link para `/settings/system` |
| Pattern de `TenantFiscalSettings` | Usar mesmo padrão: tabela dedicada, um registro por tenant, JSONB por seção |

---

## 5. O QUE DEVE SER CRIADO

### Backend

| Item | Descrição |
|------|-----------|
| Migration `create_tenant_settings_table` | Tabela `tenant_settings` com colunas JSONB por domínio |
| `TenantSettings` model | Model com casts e defaults por seção |
| `TenantSettingsController` | GET show + PUT update por seção |
| `TenantSettingsResource` | Resource com todas as seções |
| `routes/api/v1/system-settings.php` | Rotas de configurações |
| Bootstrap de tenant | Criar registro padrão ao criar tenant |
| Expandir `TenantFeatureEnum` | +6 novos módulos |

### Frontend

| Item | Descrição |
|------|-----------|
| `settings.service.ts` | Serviço de configurações do sistema |
| `features/system-settings/hooks/index.ts` | Hooks TanStack Query |
| `features/system-settings/components/` | Componentes de formulário por seção |
| `app/(app)/settings/system/page.tsx` | Página principal com abas |
| Ícone engrenagem no `header.tsx` | Link para `/settings/system` com permissão `settings.view` |
| Rota `ROUTES.SYSTEM_SETTINGS` | Adicionar em routes.ts |
| Proteção em `layout.tsx` | `/settings/system` → `settings.view` |
| Sidebar — link para `/settings/system` | No grupo "Configurações" |

---

## 6. IMPACTOS IDENTIFICADOS

| Impacto | Gravidade | Arquivo |
|---------|-----------|---------|
| `CommercialSettings` em localStorage não é persistente | Alto | `commercial-settings-modal.tsx` |
| Validação de desconto no módulo de vendas ainda não usa `TenantSettings` | Médio | Futuro |
| Feature flags não controlam renderização de menus no frontend | Médio | `sidebar.tsx` |
| `BootstrapTenantAction` não cria `TenantSettings` ao criar tenant | Médio | `BootstrapTenantAction.php` |

---

## 7. RISCOS

| Risco | Gravidade | Mitigação |
|-------|-----------|-----------|
| Migrar commercial-settings quebra o modal existente | Médio | Manter compatibilidade — ler da API, fallback para localStorage durante transição |
| Padrões JSONB podem ser difíceis de validar no backend | Baixo | Usar FormRequest dedicado por seção |
| Feature flags no frontend são renderização condicional — ainda não implementada | Baixo | Preparar estrutura, implementar condicionais no futuro |

---

## 8. PLANO DE IMPLEMENTAÇÃO

### Fase 3 — Menu de Configurações (Engrenagem no header)
- Adicionar `Settings` icon ao `header.tsx` com link para `/settings/system`

### Fases 4–10 — Tabela `tenant_settings`
- Migration: 7 colunas JSONB + `tenant_id` + timestamps
- Model `TenantSettings` com defaults
- Controller `TenantSettingsController`
- Routes `GET /system-settings` e `PUT /system-settings/{section}`

### Fase 11 — Feature Flags
- Expandir `TenantFeatureEnum` com: `purchasing_enabled`, `logistics_enabled`, `commissions_enabled`, `crm_enabled`, `bi_enabled`, `nfe_enabled`, `custom_price_table_enabled`
- Adicionar seção "Recursos" na tela de configurações

### Fases 12–13 — API e Frontend
- API completa com permissões
- Tela com 9 abas profissionais

---

*Gerado em: 2026-06-23*
*Módulo 03 — Configurações do Sistema — AUDITORIA INICIAL*
