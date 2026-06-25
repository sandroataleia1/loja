# AUDIT_SECURITY.md
# Módulo 02 — Segurança e Controle de Acesso
# Auditoria Completa — 2026-06-23

---

## 1. RESUMO EXECUTIVO

| Item | Status |
|------|--------|
| Autenticação (Login/Logout/JWT) | ✅ COMPLETO |
| Refresh Token | ⚠️ PARCIAL (stateless, sem refresh explícito) |
| Proteção de Rotas (Backend) | ✅ COMPLETO |
| Proteção de Rotas (Frontend) | ✅ COMPLETO |
| RBAC — Roles | ✅ COMPLETO |
| RBAC — Permissões Granulares (46) | ✅ COMPLETO |
| Permission Cache (Redis) | ✅ COMPLETO |
| Estrutura de Usuários | ⚠️ PARCIAL (faltam username, phone) |
| PIN Operacional | ⚠️ PARCIAL (armazenamento OK, falta endpoint de definição) |
| created_by / updated_by | ⚠️ PARCIAL (presente em módulos, sem trait universal) |
| Sistema de Aprovações | ❌ NÃO IMPLEMENTADO (apenas enum) |
| Auditoria Operacional (API) | ⚠️ PARCIAL (model OK, falta endpoint/frontend) |
| Frontend — Gestão de Usuários | ✅ COMPLETO |
| Frontend — Gestão de Roles | ✅ COMPLETO |
| Frontend — Matrix de Permissões | ✅ COMPLETO |
| Frontend — Gestão de PIN | ❌ NÃO IMPLEMENTADO |
| Frontend — Página de Auditoria | ❌ NÃO IMPLEMENTADO |

---

## 2. O QUE JÁ EXISTE

### 2.1 Backend — Autenticação

**Arquivo:** `backend/app/Core/Auth/Http/Controllers/AuthController.php`

Endpoints implementados e operacionais:

| Endpoint | Método | Status |
|----------|--------|--------|
| `POST /auth/register` | Cria tenant + usuário admin | ✅ |
| `POST /auth/login` | Email + senha → token Sanctum | ✅ |
| `POST /auth/logout` | Revoga token atual | ✅ |
| `GET /auth/me` | Perfil completo com permissões | ✅ |
| `POST /auth/forgot-password` | Envia e-mail de reset | ✅ |
| `POST /auth/reset-password` | Redefine senha via token | ✅ |
| `GET /auth/email/verify/{id}/{hash}` | Verificação de e-mail | ✅ |
| `POST /auth/email/resend` | Reenviar verificação | ✅ |

**Rate Limiting configurado:**
- register: 3/min por IP
- login: 5/min por IP+email (proteção contra força bruta)
- forgot-password: 3/min por IP
- reset-password: 3/min por IP

**Segurança da senha:**
- Bcrypt com 12 rounds (configurado em .env)
- `password` cast como `hashed` no model User
- Tokens Sanctum com expiração de 1440 min (24h)

### 2.2 Backend — Modelos de Dados

**User** (`backend/app/Core/Auth/Models/User.php`)

| Campo | Tipo | Observação |
|-------|------|------------|
| uuid | PK | Identificador único |
| tenant_id | FK | Vincula ao tenant |
| name | string | Nome completo |
| email | string | Unique por tenant |
| password | hashed | Bcrypt 12 rounds |
| pin | string(10) nullable | SHA-256(APP_KEY + pin) |
| is_active | boolean | Controle de acesso |
| email_verified_at | timestamp | Verificação de e-mail |
| last_login_at | timestamp | Último acesso |
| created_at / updated_at | timestamps | Auto-gerenciados |
| deleted_at | timestamp | Soft delete |

**Campos AUSENTES em relação ao requisito mínimo:**
- `username` (campo "usuário" na especificação)
- `phone` (telefone)

**Role** (`backend/app/Core/Auth/Models/Role.php`)
- uuid, tenant_id (nullable — NULL = sistema), name, slug, description
- is_system, is_active, policy (JSON)
- Relacionamentos: BelongsToMany(Permission), HasMany(TenantUser)

**Permission** (`backend/app/Core/Auth/Models/Permission.php`)
- uuid, name, slug (unique), module, is_system
- 46 permissões granulares cadastradas via PermissionEnum + RbacSeeder

**TenantUser** (`backend/app/Core/Auth/Models/TenantUser.php`)
- Vincula User a Tenant com um Role específico
- Allowlist de lojas via TenantUserStore (ausência = acesso irrestrito)

### 2.3 Backend — RBAC

**Arquivo:** `backend/app/Core/Auth/Enums/PermissionEnum.php`

Permissões granulares por módulo:

| Módulo | Permissões |
|--------|-----------|
| Dashboard | dashboard.view |
| Customers | customers.view, create, update, delete |
| Products | products.view, create, update, delete |
| Inventory | inventory.view, adjust, transfer |
| Sales | sales.view, create, cancel, discount |
| Cashier | cashier.open, close, reopen |
| Financial | financial.view, create, update, delete |
| Fiscal | fiscal.view, issue, cancel, reprocess |
| Users | users.view, create, update, delete |
| Settings | settings.view, update |
| Suppliers | suppliers.view, create, update |
| Purchase Orders | purchase_orders.view, create, approve |
| Purchase Receipts | purchase_receipts.receive |

**Total: 46 permissões distintas**

**Roles de Sistema** (`backend/app/Core/Auth/Enums/RoleSlugEnum.php`):

| Role | Permissões | Descrição |
|------|-----------|-----------|
| owner | TODAS | Proprietário — sem restrições |
| manager | 33 | Gerente completo |
| salesperson | 9 | Foco em vendas e clientes |
| cashier | 7 | Foco em caixa e vendas |
| stock_operator | 7 | Foco em estoque |
| financial | 7 | Foco em financeiro e fiscal |

**Permission Cache:** Redis, TTL 5 minutos
- Chaves: `rbac.{tenantId}.{userId}.permissions` e `.stores`
- Invalidação automática ao mudar role ou permissões

### 2.4 Backend — Middlewares

| Middleware | Uso | Arquivo |
|-----------|-----|---------|
| `RequirePermission` | `middleware('permission:products.view')` | `Http/Middleware/RequirePermission.php` |
| `RequireRole` | `middleware('role:owner,manager')` | `Http/Middleware/RequireRole.php` |
| `RequireStoreAccess` | Verifica allowlist de lojas | `Http/Middleware/RequireStoreAccess.php` |
| `auth:sanctum` | Autenticação obrigatória | Laravel Sanctum |
| `throttle:*` | Rate limiting por rota | Laravel built-in |

### 2.5 Backend — PIN Operacional

**Estado atual:**

- ✅ Campo `pin` na tabela `users` (migration `2026_06_21_000006`)
- ✅ Hash SHA-256(APP_KEY + pin) no model User (cast automático)
- ✅ Migração de PINs legados plaintext (migration `2026_06_22_200001`)
- ✅ Endpoint `POST /quotes/resolve-seller-pin` — identifica vendedor pelo PIN
- ❌ **FALTA:** Endpoint para administrador definir/alterar PIN de usuário
- ❌ **FALTA:** Interface frontend para gerenciar PIN

**Utilizações previstas do PIN (ApprovalRequiredEnum):**
- Aprovação de desconto
- Aprovação de cancelamento
- Aprovação de estorno
- Aprovação de reembolso alto

### 2.6 Backend — Auditoria

**Modelo:** `backend/app/Core/Audit/Models/AuditLog.php`

Campos:
- tenant_id, store_id, user_id, device_id, correlation_id
- entity_type (AuditEntityTypeEnum), entity_uuid
- action (AuditActionEnum)
- old_values (JSON), new_values (JSON), metadata (JSON)
- ip, user_agent

**AuditActionEnum** — 30+ ações rastreadas:

| Categoria | Ações |
|-----------|-------|
| Autenticação | auth.login, auth.logout, auth.failed_login, auth.token_revoked |
| RBAC | rbac.role_assigned, role_revoked, role_created, role_updated, role_deleted, store_access_granted, store_access_revoked, permissions_synced |
| Vendas | sale.completed, sale.cancelled, sale.discount_applied, sale.returned |
| Caixa | cash_register.opened, cash_register.closed |
| Estoque | inventory.stock_adjusted, stock_transferred, stock_counted |
| Financeiro | financial.expense_paid, financial.entry_reversed |
| Fiscal | fiscal.nfce_emitted, nfce_cancelled, error, retried |
| Catálogo | catalog.price_changed |

**Serviço:** `AuditLogger::record(AuditLogDTO)` — grava no banco e no log channel 'audit'
- Nunca quebra o caller (exceção capturada internamente)

**Estado:**
- ✅ Modelo e serviço funcionais
- ❌ **FALTA:** Controller/endpoint para listar/filtrar audit logs via API
- ❌ **FALTA:** Frontend para visualizar logs de auditoria

### 2.7 Backend — created_by / updated_by

Presente nas seguintes tabelas:

| Tabela | created_by | updated_by |
|--------|-----------|-----------|
| orders | ✅ | ✅ |
| quotes | ✅ | ✅ |
| inventory_adjustments | ✅ | — |
| inventory_transfers | ✅ | — |
| cash_movements | ✅ | — |
| financial_entries | ✅ | — |
| fiscal_documents | ✅ | — |
| purchase_orders | ✅ | ✅ |
| sync_operations | ✅ | — |

**Estado:** Implementado por módulo, sem trait universal.

### 2.8 Frontend — Autenticação

| Componente | Status |
|-----------|--------|
| `middleware.ts` | ✅ Protege todas as rotas, redireciona não-autenticados |
| `AuthProvider` | ✅ Contexto com user, tenant, store, channel, permissions |
| `useAuth` | ✅ Hook wrapper do contexto |
| `useMeQuery` | ✅ React Query para /auth/me, staleTime 5min |
| `api-client.ts` | ✅ Axios + Bearer token + interceptor 401 |
| Login page | ✅ `/login` |
| Register page | ✅ `/register` |
| Forgot password | ✅ `/forgot-password` |
| Reset password | ✅ `/reset-password` |

### 2.9 Frontend — RBAC

| Componente | Status |
|-----------|--------|
| `/users` | ✅ Lista, criar, convidar, trocar role, revogar |
| `/roles` | ✅ Lista de perfis com permissões |
| `/roles/create` | ✅ Criar perfil customizado |
| `/roles/[uuid]` | ✅ Detalhe do perfil |
| `/roles/[uuid]/edit` | ✅ Editar perfil |
| `/permissions` | ✅ Matrix de permissões por módulo |
| `/store-access` | ✅ Allowlist de acesso por loja |
| `PermissionGuard` | ✅ Guard de componentes por permissão |
| `(app)/layout.tsx` | ✅ Verificação de permissão por rota |

---

## 3. O QUE ESTÁ PARCIALMENTE IMPLEMENTADO

### 3.1 Refresh Token
- **Situação:** Sanctum stateless tokens com TTL de 24h. Não há mecanismo de refresh explícito.
- **Impacto:** Após 24h (ou expiração configurada), o usuário precisa fazer login novamente.
- **Avaliação:** Aceitável para o contexto de ERP desktop/web. Não é obrigatório implementar refresh token com Sanctum, pois os tokens podem ter TTL longo ou ser renováveis via re-login.

### 3.2 Estrutura de Usuários (Fase 3)
- **Situação:** Campos `username` e `phone` ausentes no model e na tabela `users`.
- **Impacto:** Requisitos mínimos da especificação não atendidos.
- **Ação necessária:** Migration + atualização do model.

### 3.3 PIN Operacional (Fase 6)
- **Situação:** Storage e resolução implementados. Falta gerenciamento.
- **Impacto:** Administrador não consegue definir/alterar PIN de usuários via interface.
- **Ação necessária:** Endpoint `PUT /rbac/users/{tenantUser}/pin` + frontend.

### 3.4 created_by / updated_by (Fase 7)
- **Situação:** Implementado por módulo individualmente, sem trait centralizada.
- **Impacto:** Futuras entidades podem não ter o campo se não adicionado manualmente.
- **Avaliação:** Funcional, risco baixo. Documentar padrão.

---

## 4. O QUE NÃO EXISTE

### 4.1 Sistema de Aprovações (Fase 8)
- `ApprovalRequiredEnum` existe como fundação apenas.
- **Falta completamente:**
  - Tabela `approval_requests`
  - Model `ApprovalRequest`
  - Controller com endpoints
  - Workflow de aprovação por PIN
  - Frontend de aprovações pendentes

### 4.2 API de Auditoria (Fase 9)
- `AuditLog` model e `AuditLogger` service existem.
- **Falta completamente:**
  - Controller `AuditLogController`
  - Endpoint `GET /audit-logs` com filtros
  - Rota registrada em `api/v1.php`

### 4.3 Frontend — Auditoria (Fase 10)
- Não existe página `/audit-logs` ou equivalente.
- **Falta completamente:**
  - Serviço frontend de auditoria
  - Hook de query
  - Página com filtros e tabela de logs

### 4.4 Frontend — Gestão de PIN (Fase 10)
- Sem qualquer interface para definir ou alterar PIN de usuário.

---

## 5. ANÁLISE DE SEGURANÇA

### 5.1 Pontos Fortes

| Aspecto | Avaliação |
|---------|----------|
| Senhas com Bcrypt 12 rounds | Excelente |
| PIN nunca em plaintext (SHA-256 + pepper) | Excelente |
| Rate limiting em todos os endpoints sensíveis | Muito bom |
| RBAC multi-nível (roles + permissões granulares) | Muito bom |
| Permission Cache evita consultas repetidas ao banco | Bom |
| Soft delete para rastreabilidade | Bom |
| Tokens com expiração configurável | Bom |
| Multi-tenancy com isolamento por tenant_id | Bom |
| CSRF protection via Sanctum | Bom |
| Session encryption habilitada | Bom |

### 5.2 Riscos Identificados

| Risco | Gravidade | Mitigação |
|-------|-----------|-----------|
| Sem refresh token explícito | Baixo | Tokens de longa duração via config |
| Campos username e phone ausentes | Médio | Implementar migration |
| PIN sem endpoint de gerenciamento | Médio | Implementar endpoint |
| Sem interface de auditoria | Médio | Implementar frontend |
| Sistema de aprovações não implementado | Alto | Implementar workflow |
| Rate limiting pode falhar atrás de proxy reverso | Baixo | Configurar trusted proxies |

---

## 6. PLANO DE IMPLEMENTAÇÃO

### Fase 3 — Campos username e phone
1. Migration: `add_username_phone_to_users`
2. Atualizar `$fillable` no model `User`
3. Atualizar `UserResource`
4. Atualizar formulário de criação de usuário no frontend

### Fase 6 — PIN (endpoint de definição)
1. Endpoint: `PUT /rbac/users/{tenantUser}/pin` no `TenantUserController`
2. Validação: PIN de 4-8 dígitos numéricos
3. Hash automático via cast do model
4. Frontend: modal "Definir PIN" na tabela de usuários

### Fase 8 — Sistema de Aprovações
1. Migration: tabela `approval_requests`
2. Model: `ApprovalRequest`
3. Controller: `ApprovalController`
4. Endpoints: criar solicitação, aprovar por PIN, rejeitar
5. Integração com `ApprovalRequiredEnum`

### Fase 9 — API de Auditoria
1. Controller: `AuditLogController::index()` com filtros
2. Resource: `AuditLogResource`
3. Rota: `GET /audit-logs`
4. Frontend: página `/audit-logs`

---

## 7. ARQUIVOS IDENTIFICADOS

### Backend — Existentes

| Arquivo | Status |
|---------|--------|
| `backend/app/Core/Auth/Models/User.php` | ✅ Completo |
| `backend/app/Core/Auth/Models/Role.php` | ✅ Completo |
| `backend/app/Core/Auth/Models/Permission.php` | ✅ Completo |
| `backend/app/Core/Auth/Models/TenantUser.php` | ✅ Completo |
| `backend/app/Core/Auth/Models/TenantUserStore.php` | ✅ Completo |
| `backend/app/Core/Auth/Models/PlatformRole.php` | ✅ Completo |
| `backend/app/Core/Auth/Enums/PermissionEnum.php` | ✅ Completo |
| `backend/app/Core/Auth/Enums/RoleSlugEnum.php` | ✅ Completo |
| `backend/app/Core/Auth/Enums/ApprovalRequiredEnum.php` | ⚠️ Fundação apenas |
| `backend/app/Core/Auth/Http/Controllers/AuthController.php` | ✅ Completo |
| `backend/app/Core/Auth/Http/Controllers/TenantUserController.php` | ✅ Completo |
| `backend/app/Core/Auth/Http/Controllers/RoleController.php` | ✅ Completo |
| `backend/app/Core/Auth/Http/Middleware/RequirePermission.php` | ✅ Completo |
| `backend/app/Core/Auth/Http/Middleware/RequireRole.php` | ✅ Completo |
| `backend/app/Core/Auth/Http/Middleware/RequireStoreAccess.php` | ✅ Completo |
| `backend/app/Core/Auth/Services/PermissionCache.php` | ✅ Completo |
| `backend/app/Core/Auth/Traits/HasTenantPermissions.php` | ✅ Completo |
| `backend/app/Core/Audit/Models/AuditLog.php` | ✅ Completo |
| `backend/app/Core/Audit/Services/AuditLogger.php` | ✅ Completo |
| `backend/app/Core/Audit/Enums/AuditActionEnum.php` | ✅ Completo |
| `backend/app/Core/Audit/Enums/AuditEntityTypeEnum.php` | ✅ Completo |
| `backend/database/migrations/2026_05_30_000024_create_rbac_foundation.php` | ✅ Completo |
| `backend/database/migrations/2026_06_21_000006_add_pin_and_seller_to_orders.php` | ✅ Completo |
| `backend/database/migrations/2026_06_22_200001_hash_existing_plaintext_pins.php` | ✅ Completo |
| `backend/database/seeders/RbacSeeder.php` | ✅ Completo |

### Frontend — Existentes

| Arquivo | Status |
|---------|--------|
| `frontend/src/middleware.ts` | ✅ Completo |
| `frontend/src/providers/auth-provider.tsx` | ✅ Completo |
| `frontend/src/hooks/use-auth.ts` | ✅ Completo |
| `frontend/src/hooks/use-me-query.ts` | ✅ Completo |
| `frontend/src/services/auth.service.ts` | ✅ Completo |
| `frontend/src/lib/api-client.ts` | ✅ Completo |
| `frontend/src/components/guards/permission-guard.tsx` | ✅ Completo |
| `frontend/src/app/(auth)/login/page.tsx` | ✅ Completo |
| `frontend/src/app/(auth)/register/page.tsx` | ✅ Completo |
| `frontend/src/app/(auth)/forgot-password/page.tsx` | ✅ Completo |
| `frontend/src/app/(auth)/reset-password/page.tsx` | ✅ Completo |
| `frontend/src/app/(app)/users/page.tsx` | ✅ Completo |
| `frontend/src/app/(app)/roles/page.tsx` | ✅ Completo |
| `frontend/src/app/(app)/roles/create/page.tsx` | ✅ Completo |
| `frontend/src/app/(app)/roles/[uuid]/page.tsx` | ✅ Completo |
| `frontend/src/app/(app)/roles/[uuid]/edit/page.tsx` | ✅ Completo |
| `frontend/src/app/(app)/permissions/page.tsx` | ✅ Completo |
| `frontend/src/app/(app)/store-access/page.tsx` | ✅ Completo |
| `frontend/src/features/rbac/components/users-table.tsx` | ✅ Completo |
| `frontend/src/features/rbac/components/roles-list.tsx` | ✅ Completo |
| `frontend/src/features/rbac/components/role-form.tsx` | ✅ Completo |
| `frontend/src/features/rbac/components/permissions-matrix.tsx` | ✅ Completo |
| `frontend/src/features/rbac/components/store-access-list.tsx` | ✅ Completo |

### A Criar

| Arquivo | Fase |
|---------|------|
| `backend/database/migrations/*_add_username_phone_to_users.php` | Fase 3 |
| `backend/app/Core/Auth/Http/Controllers/TenantUserController.php` (atualização — endpoint PIN) | Fase 6 |
| `backend/app/Core/Auth/Models/ApprovalRequest.php` | Fase 8 |
| `backend/database/migrations/*_create_approval_requests_table.php` | Fase 8 |
| `backend/app/Core/Auth/Http/Controllers/ApprovalController.php` | Fase 8 |
| `backend/app/Core/Audit/Http/Controllers/AuditLogController.php` | Fase 9 |
| `backend/app/Core/Audit/Http/Resources/AuditLogResource.php` | Fase 9 |
| `frontend/src/app/(app)/audit-logs/page.tsx` | Fase 10 |
| `frontend/src/features/audit/components/audit-logs-table.tsx` | Fase 10 |
| `frontend/src/services/audit.service.ts` | Fase 10 |

---

*Gerado em: 2026-06-23*
*Auditor: Claude (Módulo 02 — Segurança e Controle de Acesso)*
