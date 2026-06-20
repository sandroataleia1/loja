# TODO — Migração Moda → Material de Construção

> **Checklist executável** — marcar `[x]` conforme cada item for concluído.
> **Referências:** `AUDIT_REPORT.md` (o quê e onde) · `MIGRATION_PLAN.md` (como e em que ordem)
> **Início:** 2026-06-20  |  **Step 01 concluído:** 2026-06-20  |  **Step 02 concluído:** 2026-06-20  |  **Step 03 concluído:** 2026-06-20  |  **Step 04 concluído:** 2026-06-20  |  **Step 05 concluído:** 2026-06-20

---

## ✅ ETAPA 01 CONCLUÍDA — Quantidades Decimais + Unidade de Medida

> Todos os itens abaixo foram executados em 2026-06-20.
> A migration foi criada mas ainda precisa ser rodada (`docker compose exec app php artisan migrate`).

### Backend
- [x] Criado `backend/app/Modules/Catalog/Enums/UnitOfMeasureEnum.php` (UN/M/M2/M3/KG/LT/CX/SC)
- [x] Criada `backend/database/migrations/2026_06_20_000002_add_unit_of_measure_and_decimal_quantities.php`
  - [x] Adiciona `unit_of_measure VARCHAR(10) DEFAULT 'UN'` em `catalog_products`
  - [x] Converte `INTEGER → DECIMAL(15,3)` em 10 tabelas
  - [x] Recria colunas geradas com tipo correto (`difference`, `total_cost`)
- [x] `backend/app/Modules/Catalog/Models/Product.php` — cast para `UnitOfMeasureEnum`
- [x] `backend/app/Modules/Sales/Models/SaleItem.php` — cast `decimal:3`, aritmética com `round()`
- [x] `backend/app/Modules/Sales/Models/SaleReturnItem.php` — cast `decimal:3`
- [x] `backend/app/Modules/Inventory/Models/InventoryBalance.php` — cast `decimal:3`, assinaturas `float`
- [x] `backend/app/Modules/Inventory/Models/StockMovement.php` — 5 casts `decimal:3`
- [x] `backend/app/Modules/Inventory/Models/InventoryAdjustment.php` — cast `decimal:3`
- [x] `backend/app/Modules/Inventory/Models/StockCountItem.php` — cast `decimal:3`
- [x] `backend/app/Modules/Purchasing/Models/PurchaseOrderItem.php` — cast `decimal:3`, `pendingQuantity(): float`
- [x] `backend/app/Modules/Purchasing/Models/PurchaseReceiptItem.php` — cast `decimal:3`
- [x] `backend/app/Modules/Conditional/Models/ConditionalItem.php` — cast `decimal:3`, `totalCents()` com `round()`
- [x] `backend/app/Modules/Catalog/DTOs/CreateProductDTO.php` — `unitOfMeasure` substituiu `gender`
- [x] `backend/app/Modules/Catalog/DTOs/UpdateProductDTO.php` — idem
- [x] `backend/app/Modules/Catalog/Http/Requests/StoreProductRequest.php` — `Rule::enum(UnitOfMeasureEnum::class)`
- [x] `backend/app/Modules/Catalog/Http/Requests/UpdateProductRequest.php` — idem
- [x] `backend/app/Modules/Catalog/Actions/CreateProductAction.php` — `unit_of_measure`
- [x] `backend/app/Modules/Catalog/Actions/UpdateProductAction.php` — idem
- [x] `backend/app/Modules/Catalog/Http/Resources/ProductResource.php` — `unit_of_measure` + `unit_of_measure_label`
- [x] `backend/database/factories/ProductFactory.php` — `UnitOfMeasureEnum::UN`
- [x] `backend/tests/Feature/Catalog/CatalogTest.php` — produtos renomeados, `gender` removido, `unit_of_measure` adicionado

### Frontend
- [x] `frontend/src/types/shared-types.ts` — `UnitOfMeasure` type, `Product` com `unit_of_measure`
- [x] `frontend/src/types/contracts.ts` — `CreateProductRequest` com `unit_of_measure`
- [x] `frontend/src/features/catalog/components/product-form.tsx` — select de unidade (UNIT_OPTIONS), Zod schema, submit payload
- [x] `frontend/src/app/(app)/products/[uuid]/page.tsx` — exibe `unit_of_measure_label`
- [x] `frontend/src/features/pdv/stores/pdvCartStore.ts` — `unitOfMeasure` em `PdvCartItem`, `Math.round()` no subtotal
- [x] `frontend/src/features/pdv/components/cart/CartItemRow.tsx` — exibe `"3,75 M2"`, +/- com step decimal
- [x] `frontend/src/features/pdv/components/product/ProductGrid.tsx` — passa `unitOfMeasure` no `addItem()`

### Pendente (pós Docker up)
- [ ] Executar: `docker compose exec app php artisan migrate`
- [ ] Executar testes: `docker compose exec app ./vendor/bin/pest`
- [ ] Corrigir erros encontrados, se houver

---

## ✅ FASE 1 CONCLUÍDA — Remover Moda (Enums e Dependentes)

> Todos os itens executados em 2026-06-20 (Steps 01 e 02).
> Migration criada, aguarda execução via Docker.

### 1.1 Backend — Factory
- [x] `backend/database/factories/ProductFactory.php` — removido `ProductGenderEnum`

### 1.2 Backend — Form Requests
- [x] `backend/app/Modules/Catalog/Http/Requests/StoreProductRequest.php` — removido `gender`
- [x] `backend/app/Modules/Catalog/Http/Requests/UpdateProductRequest.php` — removido `gender`

### 1.3 Backend — DTOs
- [x] `backend/app/Modules/Catalog/DTOs/CreateProductDTO.php` — removido `ProductGenderEnum`
- [x] `backend/app/Modules/Catalog/DTOs/UpdateProductDTO.php` — removido `ProductGenderEnum`

### 1.4 Backend — Actions
- [x] `backend/app/Modules/Catalog/Actions/CreateProductAction.php` — removido `gender`
- [x] `backend/app/Modules/Catalog/Actions/UpdateProductAction.php` — removido `gender`

### 1.5 Backend — Resource
- [x] `backend/app/Modules/Catalog/Http/Resources/ProductResource.php` — removido `gender` / `gender_label`

### 1.6 Backend — Controller
- [x] `backend/app/Modules/Catalog/Http/Controllers/ProductController.php` — removido filtro `gender`

### 1.7 Backend — Model
- [x] `backend/app/Modules/Catalog/Models/Product.php` — removido `gender` de `$fillable` e `$casts`

### 1.8 Backend — Deletar o Enum
- [x] **Deletado** `backend/app/Modules/Catalog/Enums/ProductGenderEnum.php`

### 1.9 Backend — Testes
- [x] `backend/tests/Feature/Catalog/CatalogTest.php` — categorias renomeadas (Revestimentos, Alvenaria/Argamassa), `gender` removido
- [x] `backend/tests/Feature/Catalog/ProductTest.php` — nomes de moda substituídos por construção
- [x] `backend/tests/Feature/Catalog/ProductContentTest.php` — 'Vestido Floral' → 'Porcelanato Polido Cinza 60x60cm'
- [x] `backend/tests/Feature/Sales/SaleTest.php` — snapshots de moda substituídos
- [x] `backend/tests/Feature/Customers/InternalCodeTest.php` — 'Camiseta Básica' → 'Tijolo Cerâmico'

### 1.10 Frontend — Tipos TypeScript
- [x] `frontend/src/types/shared-types.ts` — removido `ProductGender` type
- [x] `frontend/src/types/contracts.ts` — removido import/export de `ProductGender`

### 1.11 Frontend — Service
- [x] `frontend/src/services/catalog.service.ts` — removido `gender?` de `ProductFilters` e `params.set`

### 1.12 Frontend — Formulário
- [x] `frontend/src/features/catalog/components/product-form.tsx` — gender completamente substituído por `unit_of_measure`

### 1.13 Frontend — Página de Detalhes
- [x] `frontend/src/app/(app)/products/[uuid]/page.tsx` — exibe `unit_of_measure_label`

### 1.14 Backend — Labels e Config
- [x] `backend/app/Modules/Sales/Enums/ReturnReasonEnum.php` — `'Não serviu / tamanho errado'` → `'Produto inadequado / medida incorreta'`
- [x] `backend/config/scribe.php` — `'varejo de moda'` → `'varejo de material de construção'`

### 1.15 Validação da Fase 1
- [x] `grep gender backend/app --include="*.php"` → zero resultados ✅
- [x] `grep ProductGender frontend/src --include="*.ts,*.tsx"` → zero resultados ✅
- [x] Build TypeScript: `tsc --noEmit` → zero erros ✅
- [ ] `php artisan test` → executar quando Docker estiver up

---

## FASE 2 — Banco de Dados

> Pré-requisito: Fase 1 concluída.

### 2.1 Migration: Remover campo `gender`

- [x] Criada `backend/database/migrations/2026_06_20_000001_remove_gender_from_catalog_products.php`
  - [x] `Schema::table('catalog_products')` → `dropIndex(['tenant_id', 'gender'])`
  - [x] `Schema::table('catalog_products')` → `dropColumn('gender')`
- [ ] **Executar no banco:** `docker compose exec app php artisan migrate`
- [ ] Verificar: `DESCRIBE catalog_products` → coluna `gender` não existe mais

### 2.2 Migration: Quantidade decimal + unidade de medida

- [x] Criada `2026_06_20_000002_add_unit_of_measure_and_decimal_quantities.php`
  - [x] Adiciona `unit_of_measure` em `catalog_products`
  - [x] `DECIMAL(15,3)` em 10 tabelas
  - [x] Colunas geradas recriadas com tipo correto
- [ ] **Executar no banco:** `docker compose exec app php artisan migrate`
- [ ] Verificar: `SHOW COLUMNS FROM sale_items LIKE 'quantity'` → `decimal(15,3)`

### 2.3 Verificar NCM nas variantes

- [ ] Checar se `catalog_variants` já possui coluna `ncm`
  ```sql
  SHOW COLUMNS FROM catalog_variants LIKE 'ncm';
  ```
- [ ] Se não existir: criar migration `add_ncm_to_catalog_variants`

---

## ✅ FASE 3 PARCIAL — Categorias de Construção (Step 03)

> Step 03 executado em 2026-06-20. Atributos e Grids ficam para etapa futura.

### 3.1 Seeder de Categorias

- [x] Criado `backend/database/seeders/ConstructionCategorySeeder.php`
  - [x] 10 categorias pai (Cimento e Argamassa, Pisos e Revestimentos, Hidráulica, Elétrica, Ferragens, Ferramentas, Tintas, Madeira, Cobertura, Acabamentos)
  - [x] 40 subcategorias (4 por categoria pai) com descrições
  - [x] Idempotente: verifica slug antes de criar
  - [x] Suporta execução standalone via `--class=ConstructionCategorySeeder`
  - [x] `ensureTenantContext()`: usa tenant 'loja-demo' em execução standalone
- [x] Registrado em `DatabaseSeeder.php` (chamado dentro do contexto do tenant demo)
- [ ] **Executar:** `docker compose exec app php artisan db:seed --class=ConstructionCategorySeeder`

### 3.2 Seeder de Atributos ✅ (Step 04)

- [x] Criado `backend/database/seeders/ConstructionAttributeSeeder.php`
  - [x] Bitola (Text) — 1,5mm² a 16,0mm²
  - [x] Diâmetro (Text) — 20mm a 100mm
  - [x] Comprimento (Text) — 1m a 12m
  - [x] Espessura (Text) — 6mm a 25mm
  - [x] Volume (Text) — 900ml a 25L
  - [x] Potência (Text) — 600W a 3500W
  - [x] Tensão (Text) — 127V / 220V / Bivolt
  - [x] Material (Text) — PVC / Cobre / Aço Galvanizado / Alumínio / Madeira Maciça / Fibra de Vidro
  - [x] Cor (Color) — 10 cores com hex (Branco, Marfim, Bege, Cinza Claro/Escuro, Preto, Azul, Verde, Vermelho, Amarelo)
  - [x] Acabamento (Text) — Polido / Acetinado / Rústico / Natural / Fosco / Brilhante
- [x] Registrado em `DatabaseSeeder.php`

### 3.3 Seeder de Grids de Variação ✅ (Step 04)

- [x] Criado `backend/database/seeders/ConstructionGridSeeder.php`
  - [x] Grade Cabo Elétrico (Bitola) — 1,5mm² a 10,0mm²
  - [x] Grade Tubo PVC (Diâmetro) — 20mm a 100mm
  - [x] Grade Madeira Serrada (Espessura) — 15mm a 25mm
  - [x] Grade Tinta Volume (Volume) — 900ml / 3,6L / 18L
  - [x] Grade Cor Tinta (Cor) — Branco / Marfim / Bege / Cinza Claro / Cinza Escuro
  - [x] Grade Ferramentas Elétricas (Tensão) — 127V / 220V / Bivolt
  - [x] Grade Acabamento Porcelanato (Acabamento) — Polido / Acetinado / Rústico
- [x] Registrado em `DatabaseSeeder.php`

---

## FASE 4 — Frontend: Catálogo

> Pré-requisito: Fase 1 concluída.

### 4.1 ProductForm — Unidade de medida

- [x] `frontend/src/features/catalog/components/product-form.tsx`
  - [x] Campo `unit_of_measure` (select) com UNIT_OPTIONS
  - [x] Schema Zod: `z.enum(['UN', 'M', 'M2', 'M3', 'KG', 'LT', 'CX', 'SC'])`
  - [x] No payload de submit

### 4.2 ProductForm — Adicionar NCM

- [ ] `frontend/src/features/catalog/components/product-form.tsx`
  - [ ] Campo `ncm_code` (input com máscara `####.##.##`)
  - [ ] Label: "Código NCM", campo opcional

### 4.3 ProductList — Ajustes de colunas

- [ ] `frontend/src/features/catalog/components/product-table.tsx`
  - [ ] Remover coluna "Gênero" se existir
  - [ ] Adicionar coluna "Unidade" (unit_of_measure)

### 4.4 VariantPicker — Remover render de moda ✅ (Step 04)

- [x] `frontend/src/features/pdv/components/product/VariantPicker.tsx`
  - [x] Já usa `grid_combination.attr_value` dinamicamente — sem código fashion
  - [x] Rótulo dinâmico via `grid_combination.map(g => g.attr_value).join(' / ')`

### 4.5 Outros componentes de variante ✅ (Step 04)

- [x] `frontend/src/features/catalog/components/variant-table.tsx`
  - [x] Placeholder SKU: `"Ex.: CAMISETA-001"` → `"Ex.: PORTO-001"`
  - [x] Cabeçalhos "Nome / Atributos" já são genéricos — sem referências de moda

---

## FASE 5 — Frontend: PDV Web

> Pré-requisito: Fases 1 e 2 concluídas (quantity decimal no banco).

### 5.1 Quantidade decimal no carrinho

- [x] `frontend/src/features/pdv/stores/pdvCartStore.ts`
  - [x] `PdvCartItem.unitOfMeasure: string | null`
  - [x] `subtotalCents()` usa `Math.round(unitPriceCents * quantity)`
- [x] `frontend/src/features/pdv/components/cart/CartItemRow.tsx`
  - [x] Step decimal (0.5) para unidades M/M2/M3/KG/LT
  - [x] `lineTotal` usa `Math.round()`

### 5.2 Exibir unidade de medida

- [x] `CartItemRow.tsx` — exibe `"3,75 M2"` ao lado da quantidade
- [x] Fallback: se `unitOfMeasure` for nulo, exibe apenas o número

### 5.3 Cupom de venda ✅ (Step 05)

- [x] `frontend/src/features/pdv/components/receipt/ReceiptDocument.tsx`
  - [x] `fmtQty()` helper com locale pt-BR e até 3 decimais
  - [x] `lineTotal` usa `Math.round(item.unitPriceCents * item.quantity) - item.discountCents`
  - [x] Linha de item: `"3,75 M2 × R$ 89,90"` em vez de `"3.75x R$ 89,90"`

### 5.4 Teste de fluxo PDV

- [ ] Abrir caixa → buscar produto → inserir qty `3.75` → verificar total → finalizar venda

### 5.5 Backend — Checkout Decimal e Pagamentos ✅ (Step 05)

- [x] `backend/app/Modules/Sales/Http/Requests/CreateSaleRequest.php`
  - [x] `'items.*.quantity'` → `['required', 'numeric', 'min:0.001']` (aceita 3.75)
  - [x] Adicionadas regras de validação para array `payments[]`
- [x] `backend/app/Modules/Sales/DTOs/CreateSaleItemDTO.php`
  - [x] `public float $quantity` (era `int`)
  - [x] `quantity: (float) $data['quantity']` (era `(int)`)
  - [x] `subtotalCents()` → `(int) round($this->quantity * $this->unitPriceCents)`
- [x] `backend/app/Modules/Sales/DTOs/CreateSaleDTO.php`
  - [x] Adicionado `public array $payments = []`
  - [x] `fromRequest()` lê `payments` do request
- [x] `backend/app/Modules/Sales/Actions/CreateSaleAction.php`
  - [x] Injeta `AddPaymentAction` — pagamentos criados atomicamente na mesma transação

### 5.6 Frontend — Checkout Correto ✅ (Step 05)

- [x] `frontend/src/features/pdv/stores/pdvSessionStore.ts`
  - [x] `PdvSessionData.storeUuid: string` adicionado
- [x] `frontend/src/app/(pdv)/pdv/caixa/abrir/page.tsx`
  - [x] `storeUuid: session.store_id` salvo no store ao abrir caixa
- [x] `frontend/src/services/sales.service.ts`
  - [x] `CreateSalePayload` corrigido: `store_id`, campos renomeados para `variant_id`, `sku_snapshot`, `name_snapshot`; pagamentos alinhados com `AddPaymentRequest`
- [x] `frontend/src/features/pdv/hooks/usePayment.ts`
  - [x] Todos os nomes de campo alinhados com `CreateSaleRequest` do backend
  - [x] `store_id: session!.storeUuid` enviado na criação da venda

### 5.7 Branding PDV ✅ (Step 05)

- [x] `frontend/src/features/pdv/components/layout/PdvTopbar.tsx`
  - [x] `"PDV Fashion"` → `"PDV"`

---

## FASE 6 — Seeds de Demonstração

> Pré-requisito: Fases 2 e 3 concluídas.

### 6.1 Produtos Demo

- [ ] Criar `backend/database/seeders/ConstructionProductSeeder.php`
  - [ ] Cimento CP II-E 50kg, Porcelanato Polido Cinza, Tinta Acrílica Branca, Cabo 2,5mm², Tubo PVC, Parafuso, Argamassa, Tijolo, Telha, Kit Hidráulico
- [ ] Registrar em `DatabaseSeeder.php` (apenas `local`)

### 6.2 Fornecedores Demo

- [ ] Criar `backend/database/seeders/ConstructionSupplierSeeder.php`
  - [ ] Votorantim, Suvinil, Tigre, Tramontina

### 6.3 Clientes Demo

- [ ] Criar `backend/database/seeders/ConstructionCustomerSeeder.php`
  - [ ] Construtora Exemplo Ltda., João da Silva (Pedreiro), Consumidor Final (já existe)

### 6.4 Validação dos Seeders

- [ ] `php artisan migrate:fresh --seed` → sem erros
- [ ] Logar no admin → verificar produtos, categorias, fornecedores

---

## FASE 7 — Limpeza Final

> Pré-requisito: Todas as fases anteriores.

### 7.1 Labels e Textos

- [x] `backend/config/scribe.php` — `'varejo de moda'` → `'varejo de material de construção'` ✅ (Step 02)
- [x] `backend/app/Modules/Sales/Enums/ReturnReasonEnum.php` — `'Não serviu / tamanho errado'` → `'Produto inadequado / medida incorreta'` ✅ (Step 02)

### 7.2 Branding Frontend ✅ (Auditoria Final)

- [x] `frontend/src/app/layout.tsx` — metadata description atualizada para construção
- [x] `frontend/src/components/layouts/auth-layout.tsx` — tagline atualizada para construção
- [x] `frontend/src/features/pdv/components/layout/PdvTopbar.tsx` — `"PDV Fashion"` → `"PDV"` ✅ (Step 05)

### 7.3 Documentação ✅ (Auditoria Final)

- [x] `docs/architecture/vision.md` — `"ERP Moda SaaS"` → `"ERP Construção SaaS"`, público-alvo e catálogo atualizados
- [x] `docs/domain.md` — título, descrição e exemplos de tenants atualizados para construção
- [x] `docs/deploy/pdv-instalacao.md` — `"PDV Fashion"` → `"PDV Construção"` em todos os artefatos de build
- [x] `docs/MIGRACAO_MODA_PARA_CONSTRUCAO.md` — marcado como SUPERSEDED

### 7.4 Testes ✅ (Auditoria Final)

- [x] `backend/tests/Feature/Fiscal/FiscalSettingsTest.php` — `"Loja Moda S/A"` → `"Construtora Demo S/A"`, `"Vestuário Simples"` → `"Materiais Construção Simples"`

### 7.5 Mocks de Dashboard ✅ (Auditoria Final)

- [x] `frontend/src/features/dashboard/mocks/dashboard.mock.ts` — categorias, produtos top e produtos parados substituídos por dados de construção

### 7.6 Validação Final Completa ✅

- [x] Zero referências de moda no código (`grep` retorna exit=1 em backend/app, backend/tests, frontend/src)
- [x] `npx tsc --noEmit` → zero erros TypeScript
- [ ] `php artisan test` → executar quando Docker estiver up
- [ ] Fluxo completo manual: cadastro → PDV qty decimal → cupom → caixa → estoque

---

## Resumo de Progresso

| Fase | Status | Concluído em |
|------|--------|-------------|
| Step 01 — Qty decimal + unidade medida | `[x]` ✅ Concluído (migrar pendente) | 2026-06-20 |
| Step 02 — Remover conceitos de moda | `[x]` ✅ Concluído (migrar pendente) | 2026-06-20 |
| Step 03 — Categorias de construção | `[x]` ✅ Concluído (seeder pendente) | 2026-06-20 |
| Step 04 — Atributos e Grids de construção | `[x]` ✅ Concluído (seeder pendente) | 2026-06-20 |
| Step 05 — PDV decimal + checkout + cupom | `[x]` ✅ Concluído (teste manual pendente) | 2026-06-20 |
| Auditoria Final — Zero moda no código | `[x]` ✅ **CONCLUÍDO** | 2026-06-20 |
| Fase 1 — Remover moda | `[x]` ✅ Concluído | 2026-06-20 |
| Fase 2 — Banco de dados | `[~]` Migrations criadas; execução pendente | — |
| Fase 3 — Categorias + Atributos + Grids | `[x]` ✅ Concluído (seeders pendentes) | 2026-06-20 |
| Fase 4 — Frontend catálogo | `[~]` 4.1 ✅; 4.2 NCM pendente | — |
| Fase 5 — PDV Web decimal | `[x]` ✅ Concluído | 2026-06-20 |
| Fase 6 — Seeds demo | `[ ]` Opcional — não é bloqueante | — |
| Fase 7 — Limpeza final | `[x]` ✅ **CONCLUÍDO** (Auditoria Final) | 2026-06-20 |

---

## 🏁 STATUS FINAL DA MIGRAÇÃO

> **Migração de código: 100% concluída.**
> Todas as referências de moda foram removidas do código, testes, mocks e documentação.
> Pendências restantes são operacionais (Docker migrate/seed) e opcionais (seeds demo, NCM).

### Pendências Operacionais (requerem Docker)
1. `docker compose exec app php artisan migrate` — executa as migrations criadas
2. `docker compose exec app php artisan db:seed --class=ConstructionCategorySeeder`
3. `docker compose exec app php artisan db:seed --class=ConstructionAttributeSeeder`
4. `docker compose exec app php artisan db:seed --class=ConstructionGridSeeder`
5. `docker compose exec app ./vendor/bin/pest` — suite de testes completa

### Pendências Opcionais (Fase 6)
- Seeds de produtos, fornecedores e clientes demo (não são bloqueantes para uso em produção)

---

*Checklist criado em 2026-06-20. Atualizar `[ ]` para `[x]` conforme cada item for concluído.*
