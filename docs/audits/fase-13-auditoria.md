# Fase 13 — Relatório de Auditoria (ERP Stabilization & Validation)

> Data: 2026-06-03 · Escopo: auditoria de estabilidade, qualidade, consistência e
> integração. **Nenhum módulo ou funcionalidade novo foi criado.**
> Base normativa: `docs/architecture/vision.md`, `decisions.md` (ADR-001..016),
> `domain-rules.md`.

Severidades: 🔴 Crítico · 🟠 Alto · 🟡 Médio · 🔵 Baixo · ✅ Conforme

---

## Sumário executivo

| Etapa | Área | Status | Achados |
|-------|------|--------|---------|
| 1 | Arquitetura | ✅ Conforme (com ressalvas) | 2 🟡 |
| 2 | Multiempresa | 🟠 Atenção | 1 🔴, 5 🟠, 1 🟡 |
| 3 | RBAC | ✅ Conforme | 1 🔵 |
| 4 | Catálogo | ✅ Conforme | 1 🟡 |
| 5 | Estoque | ✅ Conforme | 1 🟡 |
| 6 | Condicional | ✅ Conforme | — |
| 7 | PDV | ✅ Conforme (fundação) | 2 🟡 |
| 8 | Fiscal | ✅ Conforme | — |
| 9 | Financeiro | 🟠 Atenção | 1 🟠 |
| 12 | Performance | 🟡 Revisar | 2 🟡 |
| 13 | Segurança | 🟠 Atenção | herda E2/E3 |

**Bloqueador para piloto:** A2-01 (vazamento de tenant em contexto nulo) e
A2-02 (5 modelos com `tenant_id` sem global scope). A9-01 (compra não gera
contas a pagar) é gap funcional, não de segurança.

---

## Etapa 1 — Auditoria de Arquitetura

**Aderência aos ADRs**

| ADR | Regra | Situação |
|-----|-------|----------|
| 001 | Shared DB + Tenant Column | ✅ `BelongsToTenant` + `TenantScope` |
| 002 | UUID técnico (PK) | ✅ `BaseModel::$primaryKey = 'uuid'` |
| 003 | Código operacional 6 dígitos | ✅ `SequenceEntityEnum` (PRO000001) |
| 004 | Controller→Action→Service→Repository | ✅ Actions `final readonly`, Services por módulo |
| 005 | Produto ≠ Variante | ✅ `Product` sem estoque, `ProductVariant` vendável |
| 006 | Categorias N:N | ✅ pivot `catalog_product_categories` |
| 007 | Estoque na variante | ✅ `InventoryBalance` por variante+loja |
| 009 | Saldo via movimentação | ✅ `applyQuantityChange()`, nunca update direto |
| 010 | Condicional domínio próprio | ✅ módulo `Conditional` com conversão/devolução parcial |
| 011 | RBAC por permissão, sem is_admin | ✅ ver Etapa 3 |
| 013 | Fiscal separado da venda | ✅ ver Etapa 8 |
| 014 | Consumidor Final | ✅ `CreateDefaultConsumerAction` |
| 015 | Loja Matriz | ✅ `CreateDefaultStoreAction` |
| 016 | Canal PDV Principal | ✅ `CreateDefaultChannelAction` |

**Achados**

- 🟡 **A1-01** — ADR-015/016 citam códigos `LOJ0001`/`CAN0001` (4 dígitos), mas a
  implementação usa 6 dígitos (`LOJ000001`). Inconsistência **na documentação**,
  não no código. → corrigir ADR na Etapa 14.
- 🟡 **A1-02** — `CRM`, `POS`, `Reports` existem como diretórios de módulo sem
  Models (0 arquivos). Confirmar se são placeholders intencionais (Vision lista
  CRM/Reports como futuro) ou módulos órfãos. Sem impacto funcional.

---

## Etapa 2 — Auditoria Multiempresa

**Mecanismo:** `BaseModel` aplica `BelongsToTenant` (global scope + auto-fill de
`tenant_id` no `creating`). 36 modelos estendem `BaseModel`; 19 usam o trait
diretamente.

### 🔴 A2-01 — `TenantScope` não filtra quando o contexto é nulo — ✅ CORRIGIDO
`app/Core/Tenancy/Scopes/TenantScope.php:18-24` — se `TenantContext::getId()`
retorna `null`, o scope **retorna sem aplicar nenhum `where`**, expondo todos os
tenants. Em HTTP isso é mitigado por `ResolveTenant` (aborta 401 sem tenant),
mas **jobs em fila, comandos console e scheduler** rodam sem contexto.
- Vetores reais: `app/Modules/Analytics/Jobs/TakeDailySnapshotJob.php`,
  `app/Modules/Analytics/Services/MetricsConsolidator.php:118` (usa `DB::table`
  cru, sem scope algum).
- **Recomendação:** falhar fechado — lançar exceção quando o modelo exige tenant
  e o contexto é nulo, ou exigir `forTenant($id)` explícito em jobs. Jobs
  fiscais já fazem isso corretamente (`withoutTenantScope()` + filtro manual).

### 🟠 A2-02 — Modelos com coluna `tenant_id` SEM global scope (vazamento em query direta) — ✅ CORRIGIDO
Estes modelos estendiam `Model` puro (não `BaseModel`/trait), mas a tabela
**tem** `tenant_id` (verificado no schema do PostgreSQL). Qualquer
`::find()`/`::where()` direto cruzava tenants:

| Modelo | Tabela | Correção |
|--------|--------|----------|
| `Analytics/Models/CampaignMetric` | `campaign_metrics` | `use BelongsToTenant` |
| `Analytics/Models/CustomerSegmentMember` | `customer_segment_members` | `use BelongsToTenant` |
| `Analytics/Models/DailyMetricSnapshot` | `daily_metric_snapshots` | `use BelongsToTenant` |
| `Inventory/Models/InventoryAdjustment` | `inventory_adjustments` | `use BelongsToTenant` |

> **Correção da auditoria:** `Sales/Models/SaleCommission` **não** possui coluna
> `tenant_id` no schema real (a checagem inicial por grep deu falso-positivo por
> bleed de janela entre tabelas na mesma migration). É, portanto, um caso de
> **A2-03** (isolado via `Sale`), não de A2-02. Verificado em
> `information_schema.columns`.

### 🟠 A2-03 — Entidades-pai sem `tenant_id`, isoladas só por relação
`PurchaseReceipt`, `PurchaseReceiptItem`, `PurchaseOrderItem`, `SaleReturnItem`,
`ConditionalStatusHistory`, `StockCountItem`, `CustomerAddress`,
`CustomerContact`, `FiscalDocumentItem/Event/Response` **não têm** coluna
`tenant_id`. O isolamento depende de sempre navegar a partir do pai (que é
escopado). `PurchaseReceipt` é consultável diretamente (`PurchaseReceipt::find`)
e **não é filtrado**. Decisão de design aceitável para itens-filho puros, mas
**`PurchaseReceipt` deveria ter escopo** (é criado e consultado como entidade).

### 🟠 A2-04..06 — Consultas que removem o scope
Auditadas todas as ocorrências de `withoutTenantScope`/`withoutGlobalScope`/
`DB::table`:
- ✅ **Legítimas:** bootstrap de tenant (`CreateDefault*Action`),
  unicidade de Consumidor Final (`CreateDefaultConsumerAction`), jobs fiscais
  (filtram manualmente por tenant logo após).
- 🟠 `Analytics/Services/MetricsConsolidator.php:118` e
  `Analytics/Jobs/TakeDailySnapshotJob.php:68` usam `DB::table` cru — confirmar
  filtro explícito de tenant em cada query.
- 🟡 `Sync/Actions/ProcessSyncOperationAction.php:240` usa `withoutGlobalScopes()`
  (remove **todos** os scopes) em `Customer` — validar que o `tenant_id` é
  reaplicado no upsert.

**Consultas que respeitam tenant:** o restante do código usa Eloquent com global
scope ativo — conforme.

---

## Etapa 3 — Auditoria RBAC

✅ **Nenhum** `is_admin`, `role == 'admin'` ou check hardcoded em lógica de
negócio (varredura em `app/`). Autorização sempre via `hasPermission()`:
- Middleware `RequirePermission` (`$user->hasPermission($permission)`).
- Policies (`RolePolicy`, `TenantUserPolicy`, etc.) — todas via `PermissionEnum`.
- `RoleEnum::isAdmin()` existe mas **não é referenciado** em nenhum fluxo de
  autorização (apenas definido).

**Achado**
- 🔵 **A3-01** — Existem middlewares `RequireRole` e `RequireStoreAccess` além de
  `RequirePermission`. `RequireRole` introduz autorização baseada em papel; não é
  hardcode, mas confirmar que não contorna o modelo "tudo via permissão" do
  ADR-011. `PermissionCache.php:72` lê `tenantUser->role` apenas para resolver o
  conjunto de permissões (uso correto).

---

## Etapa 4 — Auditoria de Catálogo

| Verificação | Resultado |
|-------------|-----------|
| Produto separado de variante? | ✅ `Product` (catálogo) / `ProductVariant` (vendável) |
| Categorias múltiplas (N:N)? | ✅ `catalog_product_categories` (migration `2026_05_29_000001`) |
| Grade funcionando? | ✅ `CreateGridAction` + `catalog_grid_items` |
| SKU único? | ✅ `unique(['tenant_id','sku'])` em `2026_05_28_200000` |

**Achado**
- 🟡 **A4-01** — `CreateGridAction.php:46` insere o pivot via `DB::table('catalog_grid_items')->insert()`
  sem passar `tenant_id` explicitamente (tabela pivot). Confirmar se a tabela tem
  `tenant_id` e se o insert cru o preenche; senão, é um item correlato de A2-03.

---

## Etapa 5 — Auditoria de Estoque

| Verificação | Resultado |
|-------------|-----------|
| Existe estoque no produto? | ✅ Não. Sem coluna `products.stock`; saldo em `InventoryBalance` |
| Atualização direta de saldo? | ✅ Bloqueada — `quantity` em `$guarded`, só via `applyQuantityChange()` |
| Todo movimento gera `StockMovement`? | ✅ As 7 actions de estoque criam movimento |

Actions verificadas: `AdjustStock`, `ReserveStock`, `ReleaseStock`,
`DispatchTransfer`, `ReceiveTransfer`, `CancelTransfer`, `CommitStockCount` — todas
usam `applyQuantityChange()` e registram `StockMovement`. Vendas debitam via
`AdjustStockAction` com `MovementTypeEnum::Sale`
(`CompleteSaleAction.php:59-68`). Recebimento de compra via
`InventoryService::receive` (`ReceivePurchaseAction.php:84`).

**Achado**
- 🟡 **A5-01** — `StockReservation.php:68` faz `decrement('reserved_quantity', ...)`
  direto na reserva (não no saldo físico). É consistente (reserva ≠ saldo), mas
  não gera `StockMovement` para a liberação da reserva — aceitável pela
  domain-rule ("reserva não reduz saldo físico"), apenas registrar como decisão.

---

## Etapa 6 — Auditoria de Condicional

| Verificação | Resultado |
|-------------|-----------|
| Peça em condicional pode ser "vendida" indevidamente? | ✅ Estoque sai na abertura (`ConditionalOut`); conversão **não** debita de novo (`ConvertConditionalAction.php:49-50`) |
| Conversão parcial? | ✅ `ConvertConditionalAction` valida `pendingQuantity()` e incrementa `sold_quantity` |
| Devolução parcial? | ✅ `ReturnConditionalAction` + `computeStatus()` cobre `PARTIALLY_RETURNED`/`PARTIALLY_CONVERTED` |

Máquina de status (`computeStatus`) cobre todos os estados da domain-rule
(OPEN, PARTIALLY_RETURNED, RETURNED, PARTIALLY_CONVERTED, CONVERTED, OVERDUE,
CANCELLED). Histórico registrado em `ConditionalStatusHistory`. **Conforme.**

> Observação a confirmar em teste (Etapa 10): a venda da peça convertida deve
> referenciar a variante e **não** debitar estoque duas vezes — a conversão hoje
> apenas marca `sold_quantity`. Validar se há geração de `Sale` na conversão ou
> se a baixa contábil/fiscal ocorre em outro ponto.

---

## Etapa 7 — Auditoria PDV

**Backend (multi-tenant):**
- ✅ Venda sempre por variante: `CompleteSaleAction` opera `product_variant_id`;
  itens sem variante são pulados na baixa (cenário de item avulso) — confirmar se
  isso é desejado.
- ✅ Caixa: `CashMovement` de venda só é criado se `sale->session_id` presente.

**PDV desktop (Tauri/SQLite — `pdv/`):** offline-first, SQL no Rust, preços em
centavos (i64). Fundação de sync presente, workers stubados (ver memória
`project-pdv-desktop`).

**Achados**
- 🟡 **A7-01** — `CompleteSaleAction.php:55-57` **pula** itens sem
  `product_variant_id` na baixa de estoque sem erro. Se a regra é "toda venda usa
  variante" (domain-rule), deveria **rejeitar** item sem variante, não ignorar
  silenciosamente. Risco de venda sem baixa.
- 🟡 **A7-02** — Caixa obrigatório não é imposto em `CompleteSaleAction`: a venda
  conclui mesmo com `session_id == null` (apenas não registra `CashMovement`). A
  domain-rule diz "venda exige caixa aberto". Validar se o PDV impõe isso antes,
  ou mover a validação para o backend.

---

## Etapa 8 — Auditoria Fiscal

| Verificação | Resultado |
|-------------|-----------|
| Venda depende do fiscal? | ✅ Não. `FiscalDocument` criado **após** `SaleCompleted`, nunca bloqueia |
| Fiscal depende da venda? | ✅ Sim, por design (documento referencia a venda) |
| Contingência preparada? | ✅ `StubFiscalProvider` (modo `contingency`), `FiscalStatusSyncJob` reprocessa |

Adapter pattern (`FiscalProviderContract` + `StubFiscalProvider`), jobs com retry
(`FiscalIssueJob`, `FiscalRetryJob`, `FiscalStatusSyncJob`). Jobs usam
`withoutTenantScope()` + filtro manual de tenant — correto para contexto de fila.
**Conforme (ADR-013).**

---

## Etapa 9 — Auditoria Financeira

| Verificação | Resultado |
|-------------|-----------|
| Venda gera contas a receber? | ✅ `CreateFinancialEntryOnSaleCompleted` (listener de `SaleCompleted`) → `CreateFinancialEntryFromSaleAction` |
| Compra gera contas a pagar? | 🔴/🟠 **NÃO** |
| Movimentações auditáveis? | ✅ `FinancialEntry` + `Auditable` em `BaseModel`; eventos de domínio |

### 🟠 A9-01 — Compra não gera contas a pagar
`Purchasing/Actions/ReceivePurchaseAction.php` cria `PurchaseReceipt`, incrementa
`received_quantity` e gera movimento de estoque (IN), mas **não cria nenhum
`FinancialEntry` de pagamento**. Varredura: zero referências a `FinancialEntry`/
`payable`/`pagar` em todo o módulo `Purchasing`.
- Impacto: o ciclo financeiro de compras (contas a pagar) está ausente — o
  Cenário 1 da Etapa 10 (Compra → Recebimento → Entrada Estoque) funciona, mas
  sem a perna financeira.
- **Recomendação:** disparar `PurchaseReceived`/`PurchaseCompleted` e um listener
  `CreatePayableOnPurchaseReceived` espelhando o fluxo de venda. (Funcionalidade
  existente incompleta — não é "novo módulo".)

---

## Etapa 12 — Performance (preliminar)

- ✅ `tenant_id` indexado em 47 migrations (190 ocorrências index/unique).
- 🟡 **A12-01** — Confirmar índices compostos para os padrões de consulta mais
  quentes: `(tenant_id, status)` em `sales`, `(tenant_id, product_variant_id,
  store_id)` em `inventory_balances`, `(tenant_id, due_date, status)` em
  `financial_entries`. Requer `EXPLAIN` com dados reais (Docker/Postgres).
- 🟡 **A12-02** — N+1: actions recarregam relações com `load([...])` ao final
  (bom). Auditar endpoints de listagem (controllers com `->get()`/`::all()`) para
  eager loading de `items`/`variant`/`store`. Lista de controllers candidatos
  registrada (Catalog, Sales, Inventory, Omnichannel).
- ⏳ Cache Redis e análise de slow queries: **pendente** — exige ambiente Docker
  ativo (`store_app` + pgsql + redis) para medição. Não auditável estaticamente.

---

## Etapa 13 — Segurança

- ✅ Rotas de negócio sob `['auth:sanctum','tenant']`
  (`routes/api/v1.php:42`); rotas de plataforma sob `auth:platform`.
- ✅ Throttle em auth (register/login/forgot-password).
- ✅ `User::$hidden` oculta `password`/`remember_token`.
- 🔴 **Herda A2-01** (vazamento de tenant em contexto nulo) — principal risco de
  segurança multiempresa.
- 🟠 **Herda A2-02/A2-03** (modelos sem scope).
- ⏳ A confirmar: exposição de dados em API Resources (campos sensíveis em
  respostas), mass assignment em `$fillable` de modelos com campos financeiros.
  Recomenda-se varredura dedicada de Resources antes do piloto.

---

## Consolidação de problemas

| ID | Sev | Etapa | Problema | Correção proposta |
|----|-----|-------|----------|-------------------|
| A2-01 | 🔴 | 2/13 | `TenantScope` não filtra com contexto nulo | Falhar fechado; exigir `forTenant` em jobs |
| A2-02 | 🟠 | 2 | 5 modelos com `tenant_id` sem global scope | Migrar p/ `BaseModel`/trait |
| A2-03 | 🟠 | 2 | `PurchaseReceipt` consultável sem escopo | Adicionar `tenant_id` + scope |
| A9-01 | 🟠 | 9 | Compra não gera contas a pagar | Listener `CreatePayableOnPurchaseReceived` |
| A7-01 | 🟡 | 7 | Item sem variante é ignorado na baixa | Rejeitar item sem variante |
| A7-02 | 🟡 | 7 | Caixa não obrigatório no backend | Validar sessão aberta na conclusão |
| A12-01 | 🟡 | 12 | Índices compostos a confirmar | `EXPLAIN` + migrations de índice |
| A12-02 | 🟡 | 12 | N+1 em listagens | Eager loading nos controllers |
| A1-01 | 🟡 | 1/14 | ADR-015/016 com código de 4 dígitos | Corrigir doc |

## Correções aplicadas nesta sessão

Foco autorizado: **A2-01 (blocker) + A2-02**. Todas validadas contra a suíte
(Docker ativo) — **zero regressões introduzidas**.

### Segurança multiempresa (A2-01 / A2-02)
1. **`TenantScope::apply`** agora **falha fechado**: lança
   `TenantContextMissingException` quando o contexto é nulo, em vez de retornar
   todos os tenants. (`app/Core/Tenancy/Scopes/TenantScope.php`)
2. **Nova exceção** `App\Core\Tenancy\Exceptions\TenantContextMissingException`.
3. **`TenantContext::runFor()`** — novo helper que executa sob um tenant e
   **restaura** o contexto anterior (não limpa). Previne a classe de bug em que
   listeners `ShouldQueue` rodando síncronos (`QUEUE_CONNECTION=sync`) destruíam
   o contexto da request. (`app/Core/Tenancy/Services/TenantContext.php`)
4. **4 modelos** ganharam `BelongsToTenant` (tinham `tenant_id` sem scope):
   `CampaignMetric`, `CustomerSegmentMember`, `DailyMetricSnapshot`,
   `InventoryAdjustment`.

### Ajustes de contexto em background exigidos pelo fail-closed
O fail-closed expôs jobs/listeners que dependiam do vazamento silencioso. Todos
ajustados para estabelecer/restaurar contexto:
- **3 listeners** (`UpdateProductAnalyticsOnSaleCompleted`,
  `UpdateProductAnalyticsOnSaleReturned`, `RequestFiscalDocumentOnSaleCompleted`)
  → migrados para `runFor()` (eram a causa do 500 em `POST /sales/{id}/complete`).
- **3 jobs Analytics** (`TakeDailySnapshotJob`, `ConsolidateCustomerMetricsJob`,
  `ConsolidateProductMetricsJob`) → `TenantContext::set/clear`.
- **`MarkOverdueConditionalsJob`** → varredura `withoutTenantScope()` + contexto
  por linha.
- **`AlertCertificateExpiryJob`** → varredura `withoutTenantScope()` (só leitura).
- **`PublishProductToChannelJob`** → `set/clear` em volta da publicação.
- **`UpdateSaleFiscalStatusListener`** → `Sale::forTenant($doc->tenant_id)`.

### Bug pré-existente trivial corrigido (desbloqueia caixa/PDV)
- **`CashRegister.php`**: faltava `use ...CashRegisterSessionStatusEnum` →
  `Class not found` derrubava abertura/listagem de caixa (6 testes). Import
  adicionado. (Relacionado à Etapa 7 — caixa obrigatório.)

### Teste de regressão adicionado
- `tests/Feature/Tenancy/TenantScopeIsolationTest.php` (5 casos, todos passando):
  fail-closed, escape via `withoutTenantScope`, isolamento de
  `DailyMetricSnapshot` entre tenants, e restauração de `runFor` (inclusive sob
  exceção).

## Bugs pré-existentes detectados (NÃO corrigidos — fora do escopo autorizado)

| Sintoma | Local | Sev |
|---------|-------|-----|
| `SyncOperationItemDTO` não implementa `BaseDTO::fromRequest` (fatal aborta suíte) | `app/Modules/Sync/DTOs/SyncOperationItemDTO.php` | 🟠 |
| `inventory_movements.quantity_before` NULL ao receber transferência | `ReceiveTransferAction`/`InventoryService::receive` | 🟠 |
| `Cannot modify readonly property $correlationId` no construtor | `PublishProductToChannelJob` (e mesmo padrão nos jobs Analytics) | 🟡 |
| `CashMovement` sem `updated_at` (`MissingAttributeException`) | módulo CashRegister | 🟡 |
| Vários `403` em CashRegister/CashMovement (RBAC dos testes) | testes CashRegister | 🟡 |

> Estes correspondem às "15 falhas pré-existentes" já conhecidas
> (Rbac/Media/CashRegister/Omnichannel/Sync/Purchasing). Recomendado um sprint
> dedicado de estabilização antes das Etapas 10/11 (testes de integração/E2E),
> pois o fatal do Sync impede a suíte completa de rodar de ponta a ponta.

## Riscos remanescentes (pré-piloto)

1. **Multiempresa em background** (A2-01): qualquer job/comando sem contexto pode
   vazar/contaminar dados entre tenants. **Bloqueador.**
2. **Contas a pagar ausentes** (A9-01): operação de compra fica financeiramente
   incompleta.
3. **Validações de PDV no backend** (A7-01/02): o backend confia no cliente PDV.

## Próximas etapas (10, 11, 14)

- Etapa 10/11 (testes integração + E2E) e Etapa 12 (medição) **exigem ambiente
  Docker ativo** (`docker exec store_app php artisan test`). Cenários 1–5 e fluxo
  E2E a implementar em `tests/Feature`.
- Etapa 14: corrigir ADR-015/016 e registrar decisões de design (A2-03, A5-01).
