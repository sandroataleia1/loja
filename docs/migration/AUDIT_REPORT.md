# Relatório de Auditoria — Migração Moda → Material de Construção

> **Arquiteto responsável:** IA Assistente (Claude Sonnet 4.6)
> **Data da auditoria:** 2026-06-20
> **Status:** Concluído — somente leitura, nenhum código alterado

---

## Sumário Executivo

A auditoria varreu **toda a base de código** do projeto ERP SaaS localizado em `C:\xampp\htdocs\store`.
O sistema foi originalmente construído para **varejo de moda multi-tenant**.

**Conclusão principal:** A arquitetura central (multi-tenant, RBAC, Fiscal, Estoque, PDV, Financeiro) é **completamente agnóstica de domínio** e não precisa de reescrita. As dependências de moda estão **concentradas em um único conceito**: o campo `gender` / `ProductGenderEnum` e seus dependentes diretos.

| Métrica | Valor |
|---------|-------|
| Arquivos PHP escaneados | ~280 |
| Arquivos frontend escaneados | ~120 |
| **Arquivos afetados (total)** | **19 backend + 5 frontend + 1 config** |
| Ocorrências do termo `gender` | 47 (backend: 33, frontend: 14) |
| Enums a remover | 1 (`ProductGenderEnum`) |
| Campos DB a remover | 1 (`catalog_products.gender`) |
| Campos DB a adicionar | 2 (`unit_of_measure`, `ncm_code` no produto) |
| Campos DB a alterar | 1+ (`quantity` de INTEGER → DECIMAL) |
| Campos DB a avaliar | 2 (`season`, `launch_date` — manter ou renomear) |

---

## 1. Arquivos Afetados

### 1.1 Backend — PHP

#### CRÍTICO — Remover completamente

| Arquivo | Tipo | Problema |
|---------|------|----------|
| `backend/app/Modules/Catalog/Enums/ProductGenderEnum.php` | Enum | Enum exclusivo de moda: `Male`, `Female`, `Unisex`, `Child`, `All`. **Deletar o arquivo.** |

#### CRÍTICO — Remover campo/referências a `gender`

| Arquivo | Tipo | Linha(s) | Problema |
|---------|------|----------|---------|
| `backend/app/Modules/Catalog/Models/Product.php` | Model | `$fillable`, `$casts` | `'gender'` em `$fillable`; cast `'gender' => ProductGenderEnum::class`; import do enum |
| `backend/app/Modules/Catalog/DTOs/CreateProductDTO.php` | DTO | ~20, ~44 | Propriedade `public ProductGenderEnum $gender`; conversão `ProductGenderEnum::from(...)` |
| `backend/app/Modules/Catalog/DTOs/UpdateProductDTO.php` | DTO | ~18, ~37 | Propriedade `public ?ProductGenderEnum $gender`; conversão condicional |
| `backend/app/Modules/Catalog/Actions/CreateProductAction.php` | Action | — | Passa `gender` do DTO para criação do produto |
| `backend/app/Modules/Catalog/Actions/UpdateProductAction.php` | Action | — | Passa `gender` do DTO para atualização |
| `backend/app/Modules/Catalog/Http/Requests/StoreProductRequest.php` | Request | ~21 | `'gender' => ['sometimes', Rule::enum(ProductGenderEnum::class)]` |
| `backend/app/Modules/Catalog/Http/Requests/UpdateProductRequest.php` | Request | — | Validação de `gender` como enum de moda |
| `backend/app/Modules/Catalog/Http/Resources/ProductResource.php` | Resource | ~22–23 | Serializa `gender` e `gender_label` na resposta da API |
| `backend/app/Modules/Catalog/Http/Controllers/ProductController.php` | Controller | ~36 | Filtro `->when($request->string('gender')..., fn ($q, $v) => $q->where('gender', $v))` |
| `backend/database/factories/ProductFactory.php` | Factory | ~7, ~29 | Import do enum; `'gender' => ProductGenderEnum::All` na definição |

#### MÉDIO — Atualizar testes

| Arquivo | Tipo | Linhas | Problema |
|---------|------|--------|---------|
| `backend/tests/Feature/Catalog/CatalogTest.php` | Teste | 74, 114, 129, 136, 183 | Fixtures com `'gender' => 'unisex'` e `'gender' => 'all'`; nomes de produto `'Camiseta Básica'`, `'Camiseta Oversized Preta'`, categoria `'Camisetas'` |
| `backend/tests/Feature/Catalog/ProductContentTest.php` | Teste | 70, 73 | Usa `status = 'seasonal'` (genérico, mas verificar contexto) |

#### BAIXO — Ajustar texto/label

| Arquivo | Tipo | Linha | Problema |
|---------|------|-------|---------|
| `backend/app/Modules/Sales/Enums/ReturnReasonEnum.php` | Enum | 22 | Label `'Não serviu / tamanho errado'` (específico de vestuário) — renomear para `'Não adequado / tamanho incorreto'` ou algo neutro |
| `backend/config/scribe.php` | Config | 17 | Descrição: `'API REST da plataforma Store SaaS — varejo de moda multi-tenant.'` — atualizar para construção |

---

### 1.2 Backend — Migrations

| Arquivo | Tipo | Campo(s) afetado(s) | Ação |
|---------|------|--------------------|----|
| `backend/database/migrations/2026_05_28_200000_rebuild_catalog_tables.php` | Migration | `gender VARCHAR(20) DEFAULT 'all'` em `catalog_products`; comentários com exemplos `"Tamanho"`, `"P/M/G/GG"`, `"Cor + Tamanho"` | Criar nova migration para dropar `gender`; comentários são inofensivos |
| `backend/database/migrations/2026_05_29_000018_catalog_commerce_foundation.php` | Migration | `season VARCHAR(50)` e `launch_date DATE` adicionados a `catalog_products`; tabela `catalog_collection_products` (pivot) | Avaliar manter `season` (genérico) ou renomear; `launch_date` é genérico |
| `backend/database/migrations/2026_05_29_000004_create_sales_tables.php` | Migration | `quantity INTEGER` em `sale_items` | Criar migration para alterar para `DECIMAL(10,3)` |
| `backend/database/migrations/2026_05_29_000003_create_inventory_tables.php` | Migration | `quantity INTEGER` em múltiplas tabelas de estoque | Avaliar se precisa ser decimal também |

---

### 1.3 Frontend — TypeScript / React

| Arquivo | Tipo | Linha(s) | Problema |
|---------|------|----------|---------|
| `frontend/src/types/shared-types.ts` | Tipos TS | 129, 214–215 | `type ProductGender = 'male' \| 'female' \| 'unisex' \| 'child' \| 'all'`; propriedades `gender: ProductGender` e `gender_label: string` na interface `Product` |
| `frontend/src/types/contracts.ts` | Contratos TS | 3, 29, 117 | Re-exports de `ProductGender`; campo `gender?: ProductGender` em `CreateProductRequest` |
| `frontend/src/features/catalog/components/product-form.tsx` | Formulário | 38–41, 86, 114, 166–180 | Campo select `Gênero *` com opções Masculino/Feminino/Unissex/Infantil/Todos; validação Zod `z.enum([...])` |
| `frontend/src/services/catalog.service.ts` | Service | 35, 132 | Interface `ProductFilters` com `gender?: string`; `params.set('gender', filters.gender)` |
| `frontend/src/app/(app)/products/[uuid]/page.tsx` | Página | 148 | `<dd>{product.gender_label}</dd>` na seção de detalhes |

---

### 1.4 Arquivo de Configuração da API

| Arquivo | Linha | Problema |
|---------|-------|---------|
| `backend/config/scribe.php` | 17 | Texto `'varejo de moda multi-tenant'` na descrição da API |

---

## 2. Estruturas Afetadas

### 2.1 Enum — REMOVER

```php
// backend/app/Modules/Catalog/Enums/ProductGenderEnum.php
enum ProductGenderEnum: string {
    case Male   = 'male';    // Masculino
    case Female = 'female';  // Feminino
    case Unisex = 'unisex';  // Unissex
    case Child  = 'child';   // Infantil
    case All    = 'all';     // Todos
}
```

### 2.2 Campo de Banco de Dados — REMOVER

```sql
-- Tabela: catalog_products
-- Campo a dropar:
gender VARCHAR(20) NOT NULL DEFAULT 'all'
-- Index a dropar:
INDEX idx_tenant_gender (tenant_id, gender)
```

### 2.3 Campos a Adicionar ao Banco

```sql
-- Tabela: catalog_products
unit_of_measure VARCHAR(10) NULL  -- UN, M2, ML, KG, L, SC, RL, PC
ncm_code        VARCHAR(8)  NULL  -- NCM para NF-e (ex: 6908.90.00)

-- Tabela: catalog_variants
-- ncm já existe em $fillable do Model, verificar se está na migration

-- Tabela: sale_items
-- Alterar tipo:
quantity DECIMAL(10,3) NOT NULL  -- era INTEGER
```

### 2.4 Enums — MANTER (são genéricos)

| Enum | Motivo para manter |
|------|-------------------|
| `ProductTypeEnum` (Simple/Variant/Kit) | Funciona perfeitamente em construção |
| `AttributeTypeEnum` (Text/Color/Number/Boolean) | Agnóstico de domínio |
| `ProductStatusEnum` (Draft/Active/Inactive/Archived/Seasonal) | `Seasonal` é genérico |
| `ProductVisibilityEnum` | Sem relação com domínio |

### 2.5 Campo `season` — AVALIAR

O campo `season VARCHAR(50)` foi adicionado em `2026_05_29_000018` sobre `catalog_products`. Em moda representa estação/coleção. Em construção pode representar:
- Disponibilidade sazonal (produtos sazonais como ventiladores no verão)
- Validade de campanha promocional

**Recomendação:** Manter o campo mas renomear para `availability_season` ou simplesmente manter como `season` (o nome é suficientemente genérico).

### 2.6 Tabela `catalog_collections` — AVALIAR

A tabela de coleções existe com pivot `catalog_collection_products`. Em moda = coleções editoriais (Verão 2026). Em construção pode representar:
- Campanhas promocionais
- Kits temáticos
- Agrupamentos editoriais para e-commerce

**Recomendação:** Manter a estrutura. Renomear conceitualmente para "campanhas" na UI se necessário.

---

## 3. Dependências Encontradas

### 3.1 Cadeia de Dependência do `ProductGenderEnum`

```
ProductGenderEnum.php
    └── Product.php ($casts, $fillable)
    └── CreateProductDTO.php (propriedade tipada)
    └── UpdateProductDTO.php (propriedade tipada)
    └── CreateProductAction.php (usa DTO)
    └── UpdateProductAction.php (usa DTO)
    └── StoreProductRequest.php (Rule::enum)
    └── UpdateProductRequest.php (Rule::enum)
    └── ProductResource.php (gender, gender_label)
    └── ProductController.php (filtro where gender)
    └── ProductFactory.php (definition gender=All)
    └── CatalogTest.php (fixtures gender=unisex/all)
    ↕ (frontend consome a API)
    └── shared-types.ts (type ProductGender)
    └── contracts.ts (re-export, CreateProductRequest.gender)
    └── catalog.service.ts (filtro gender)
    └── product-form.tsx (campo Gênero, validação Zod)
    └── products/[uuid]/page.tsx (gender_label)
```

### 3.2 Dependência de Quantidade Decimal

```
sale_items.quantity (INTEGER)
    └── SaleController (cria itens)
    └── SaleItemResource (serializa qty)
    └── CartTotals cálculo (frontend)
    └── CartItemRow input (frontend)
    └── Cupom fiscal / impressão
    └── InventoryMovement (baixa estoque por qty)
```

### 3.3 Dependência de `unit_of_measure`

Atualmente não existe campo `unit_of_measure` em `catalog_products`. Sua adição impacta:
- `ProductForm.tsx` (adicionar campo)
- `ProductResource.php` (serializar)
- `ProductList.tsx` / tabela (exibir coluna)
- PDV — `CartItemRow` (exibir unidade ao lado da qty)

---

## 4. Riscos

| Risco | Probabilidade | Impacto | Mitigação |
|-------|--------------|---------|-----------|
| **R1 — Remover `gender` quebra testes existentes** | Alta | Médio | Remover fixtures com `gender=unisex` dos testes antes de rodar a suite |
| **R2 — Qty decimal quebra cálculo de total** | Média | Alto | `total_cents = unit_price_cents × qty` pode ter erro de ponto flutuante; usar `round()` ou `bcmul()` |
| **R3 — Qty decimal quebra relatórios de estoque** | Média | Alto | Testar `SaleReturnController` e `InventoryMovement` com qty fracionada |
| **R4 — Migration de qty integer→decimal em produção** | Baixa | Alto | Sistema ainda não tem dados de produção; migration simples |
| **R5 — NCM incorreto no produto gera rejeição de NF-e** | Alta (uso real) | Alto | Campo obrigatório no cadastro; validar com regex `^\d{4}\.\d{2}\.\d{2}$` |
| **R6 — VariantPicker quebra com grid de 1 atributo** | Baixa | Baixo | Testar com porcelanato (1 dimensão) antes de produção |
| **R7 — Seeder de categorias conflita com tenant_id** | Baixa | Médio | Criar categorias globais (`tenant_id = NULL`) conforme padrão atual |
| **R8 — Campo `gender` em registros legados bloqueia drop** | Muito baixa | Baixo | Sem dados de produção; migration com `nullable` + `dropColumn` é segura |

---

## 5. Impactos

### 5.1 O que NÃO precisa mudar (agnóstico de domínio)

| Módulo / Componente | Justificativa |
|--------------------|--------------|
| Multi-tenancy (`BelongsToTenant`) | Arquitetura central; zero relação com domínio |
| RBAC e permissões | Permissões genéricas |
| Módulo Fiscal (NFC-e / NF-e) | **Mais crítico em construção do que em moda** (B2B) |
| Módulo Financeiro | Genérico |
| Módulo Compras (`Purchasing`) | Mais usado em construção |
| PDV Web (funcionalidade) | Apenas ajustes de UI |
| PDV Desktop (Tauri) | Sem conceitos de moda |
| Inventário / Estoque | Apenas qty precisa virar decimal |
| Sync offline | Sem relação com domínio |
| Relatórios / Analytics | Sem relação com domínio |
| Clientes / CRM | Genérico |
| Modelo Produto → Variante → Atributo | **Perfeito para construção** |
| AttributeGroup / Grid / Attribute | Agnóstico de domínio por design |

### 5.2 Escopo real das mudanças

A migração é uma **remoção de conceitos de moda + adição de conceitos de construção**. Não é uma refatoração arquitetural. O núcleo do sistema permanece intacto.

**Estimativa de impacto:**
- Backend: 11 arquivos PHP a modificar + 1 arquivo a deletar + 2–3 migrations a criar
- Frontend: 5 arquivos a modificar
- Seeds: 4–5 seeders novos a criar
- Testes: 2 arquivos de teste a atualizar
- Config/Docs: 2 arquivos

**Total de arquivos afetados: ~26 arquivos** (dos ~400 no projeto = 6,5% da base de código)

---

## 6. Glossário de Termos Encontrados

| Termo de Moda | Ocorrências | Status | Equivalente em Construção |
|---------------|-------------|--------|--------------------------|
| `gender` / `ProductGenderEnum` | 47 | **REMOVER** | Não há equivalente |
| `season` / sazonal | 11 | Manter (genérico) | Sazonalidade de produtos |
| `collection` / coleção | 29 | Manter (renomear UI) | Campanha / agrupamento |
| `launch_date` | 3 | Manter (genérico) | Data de lançamento/disponibilidade |
| Camiseta / roupa (em testes) | 5 | Substituir nos testes | Cimento / Porcelanato / Parafuso |
| `tamanho` (em comentários) | 4 | Comentários inofensivos | Dimensão / Bitola / Diâmetro |
| `varejo de moda` (config) | 1 | Atualizar texto | `varejo de construção` |
| `Não serviu / tamanho errado` | 1 | Ajustar label | `Produto inadequado / medida errada` |

---

*Auditoria concluída em 2026-06-20. Nenhum arquivo foi modificado.*
