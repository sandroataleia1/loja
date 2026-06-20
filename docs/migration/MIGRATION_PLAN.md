# Plano de Migração — Moda → Material de Construção

> **Versão:** 1.0
> **Data:** 2026-06-20
> **Base:** Auditoria `docs/migration/AUDIT_REPORT.md`
> **Referência anterior:** `docs/MIGRACAO_MODA_PARA_CONSTRUCAO.md` (plano inicial)

---

## Visão Geral

Este documento detalha a **ordem de execução** e as **dependências entre etapas** para converter o ERP SaaS de moda em ERP de material de construção.

### Princípio-guia

> Remover antes de adicionar. Banco antes de backend. Backend antes de frontend. Código antes de seeds.

### Estimativa total

| Fase | Escopo | Esforço |
|------|--------|---------|
| Fase 1 — Limpeza de moda | Remover `gender` e dependentes | 1 dia |
| Fase 2 — Banco de dados | Migrations e tipos | 0,5 dia |
| Fase 3 — Taxonomia de construção | Categorias, atributos, grids | 2 dias |
| Fase 4 — Frontend — catálogo | Formulários e listagens | 2 dias |
| Fase 5 — Frontend — PDV | Qty decimal e unidade | 1 dia |
| Fase 6 — Seeds de demonstração | Produtos, clientes, fornecedores | 1 dia |
| Fase 7 — Finalização | Labels, config, docs | 0,5 dia |
| **Total** | | **~8 dias** |

---

## Diagrama de Dependências

```
Fase 1 (Remover gender)
    │
    ├──→ Fase 2a (Migration: dropar gender, qty→decimal)
    │        │
    │        └──→ Fase 2b (Adicionar unit_of_measure, ncm_code no produto)
    │
Fase 3 (Categorias + Atributos)  ←── pode iniciar em paralelo com Fase 2
    │
    ├──→ Fase 3b (Grids de variação técnica)  ←── depende de Fase 3
    │
Fase 4 (Frontend Catálogo)  ←── depende de Fases 1, 2, 3
    │
    ├──→ Fase 5 (PDV Web)  ←── pode iniciar em paralelo com Fase 4
    │
    └──→ Fase 6 (Seeds)    ←── depende de Fases 2, 3
    │
Fase 7 (Finalização)  ←── depende de todas as fases
```

---

## Fase 1 — Limpeza de Moda (Enums e Dependentes)

**Objetivo:** Remover completamente o conceito de `gender`/`gênero` da base de código.

**Pré-requisitos:** Nenhum — pode ser a primeira ação.

**Ordem dentro da fase** (respeitar dependências de importação PHP):

### 1.1 — Remover referências de uso antes de remover o enum

**Motivo:** PHP resolve imports em tempo de análise. Remover o enum primeiro causaria erros fatais em todos os arquivos que importam.

Arquivos a modificar (nesta ordem):

1. `backend/database/factories/ProductFactory.php`
   - Remover `use App\Modules\Catalog\Enums\ProductGenderEnum;`
   - Remover linha `'gender' => ProductGenderEnum::All,` do `definition()`

2. `backend/app/Modules/Catalog/Http/Requests/StoreProductRequest.php`
   - Remover import do enum
   - Remover regra `'gender' => ['sometimes', Rule::enum(ProductGenderEnum::class)]`

3. `backend/app/Modules/Catalog/Http/Requests/UpdateProductRequest.php`
   - Mesmas remoções do passo 2

4. `backend/app/Modules/Catalog/DTOs/CreateProductDTO.php`
   - Remover import do enum
   - Remover propriedade `public ProductGenderEnum $gender`
   - Remover linha de conversão `gender: ProductGenderEnum::from(...)`

5. `backend/app/Modules/Catalog/DTOs/UpdateProductDTO.php`
   - Remover import do enum
   - Remover propriedade `public ?ProductGenderEnum $gender`
   - Remover linha de conversão condicional

6. `backend/app/Modules/Catalog/Actions/CreateProductAction.php`
   - Remover passagem de `gender` do DTO para `Product::create()`

7. `backend/app/Modules/Catalog/Actions/UpdateProductAction.php`
   - Remover passagem de `gender` do DTO para `$product->update()`

8. `backend/app/Modules/Catalog/Http/Resources/ProductResource.php`
   - Remover `'gender' => $this->gender->value`
   - Remover `'gender_label' => $this->gender->label()`

9. `backend/app/Modules/Catalog/Http/Controllers/ProductController.php`
   - Remover `->when($request->string('gender')->value(), fn ($q, $v) => $q->where('gender', $v))`

10. `backend/app/Modules/Catalog/Models/Product.php`
    - Remover import do enum
    - Remover `'gender'` do array `$fillable`
    - Remover `'gender' => ProductGenderEnum::class` do array `$casts`

### 1.2 — Deletar o enum

11. **Deletar** `backend/app/Modules/Catalog/Enums/ProductGenderEnum.php`

### 1.3 — Atualizar testes

12. `backend/tests/Feature/Catalog/CatalogTest.php`
    - Remover `'gender' => 'unisex'` e `'gender' => 'all'` de todos os payloads
    - Renomear produtos `'Camiseta Básica'` → `'Cimento CP II-E 50kg'` (ou qualquer produto de construção)
    - Renomear categoria `'Camisetas'` → `'Alvenaria'` (ou similar)

### 1.4 — Frontend

13. `frontend/src/types/shared-types.ts`
    - Remover `export type ProductGender = 'male' | 'female' | 'unisex' | 'child' | 'all'`
    - Remover `gender: ProductGender` e `gender_label: string` da interface `Product`

14. `frontend/src/types/contracts.ts`
    - Remover todos os `ProductGender` re-exports
    - Remover `gender?: ProductGender` de `CreateProductRequest`

15. `frontend/src/services/catalog.service.ts`
    - Remover `gender?: string` de `ProductFilters`
    - Remover `if (filters?.gender) params.set('gender', filters.gender)`

16. `frontend/src/features/catalog/components/product-form.tsx`
    - Remover `gender: z.enum([...])` do schema Zod
    - Remover `gender: defaultValues?.gender ?? 'unisex'` dos defaults do form
    - Remover `gender: values.gender` do payload de submissão
    - Remover todo o bloco JSX do campo `{/* gender */}` (select com opções de gênero)

17. `frontend/src/app/(app)/products/[uuid]/page.tsx`
    - Remover `<dd>{product.gender_label}</dd>` e o `<dt>` correspondente

### 1.5 — Validar Fase 1

```bash
# Verificar que não sobrou nenhuma referência
grep -rn "gender\|ProductGender" backend/app backend/tests --include="*.php"
grep -rn "gender\|ProductGender" frontend/src --include="*.ts" --include="*.tsx"

# Rodar testes
php artisan test
```

**Critério de saída:** Zero ocorrências de `gender` no código. Todos os testes passando.

---

## Fase 2 — Banco de Dados

**Pré-requisito:** Fase 1 concluída (para garantir que `gender` não é mais referenciado no código antes de dropar a coluna).

### 2a — Migration: Remover gender e alterar quantity

Criar arquivo: `backend/database/migrations/2026_06_20_000001_remove_gender_and_fix_quantity_types.php`

```php
// Ações desta migration:
// 1. DROP INDEX idx_tenant_gender em catalog_products
// 2. DROP COLUMN gender de catalog_products
// 3. ALTER COLUMN quantity de INTEGER para DECIMAL(10,3) em sale_items
// 4. ALTER COLUMN quantity para DECIMAL(10,3) em conditional_items (se aplicável)
```

**Atenção sobre quantity em inventory:** As tabelas de inventário (`inventory_balances`, `inventory_movements`) também usam `INTEGER` para quantity. Avaliar se também devem virar decimal ou se o estoque permanece inteiro (compra-se em unidades inteiras, mas vende-se em frações).

**Decisão arquitetural recomendada:**
- `sale_items.quantity` → `DECIMAL(10,3)` — venda pode ser fracionada (3,75 m²)
- `inventory_balances.quantity` → `DECIMAL(10,3)` — saldo deve refletir vendas decimais
- `inventory_movements.quantity` → `DECIMAL(10,3)` — movimentos devem ser consistentes
- `purchase_order_items.quantity` → Manter `INTEGER` inicialmente (compra por saco/caixa inteira)

### 2b — Migration: Adicionar campos de construção no produto

Criar arquivo: `backend/database/migrations/2026_06_20_000002_add_construction_fields_to_catalog_products.php`

```php
// Ações desta migration:
// 1. ADD COLUMN unit_of_measure VARCHAR(10) NULLABLE DEFAULT 'UN' AFTER sku
//    Valores: UN | M2 | ML | KG | L | SC | RL | PC | M3 | T
// 2. ADD COLUMN ncm_code VARCHAR(8) NULLABLE AFTER unit_of_measure
//    Formato: XXXX.XX.XX (código NCM/SH)
// 3. ADD INDEX idx_ncm (tenant_id, ncm_code)
```

**Verificar antes:** O Model `Variant.php` já tem `'ncm'` em `$fillable`. Verificar se a coluna `ncm` já existe na tabela `catalog_variants` (via `2026_05_28_200000_rebuild_catalog_tables.php`). Se sim, a migration 2b apenas adiciona no produto (não na variante).

### 2c — Avaliar campos de moda que podem permanecer

| Campo | Tabela | Decisão recomendada | Justificativa |
|-------|--------|--------------------|-|
| `season` | `catalog_products` | **Manter** | Construção tem produtos sazonais |
| `launch_date` | `catalog_products` | **Manter** | Data de disponibilidade genérica |
| `catalog_collections` | Tabela | **Manter** | Renomear UI para "Campanhas" |
| `catalog_collection_products` | Tabela | **Manter** | Pivot genérico |

---

## Fase 3 — Taxonomia de Construção

**Pré-requisito:** Fase 2 concluída.

### 3a — Seeder de Categorias

Criar: `backend/database/seeders/ConstructionCategorySeeder.php`

Hierarquia (10 categorias pai → 30 subcategorias):
```
Alvenaria → Tijolos e Blocos / Argamassa e Cimento / Areia e Brita
Revestimentos → Porcelanato e Cerâmica / Pedra Natural / Pastilhas
Pintura → Tinta Acrílica / Tinta Esmalte / Impermeabilizante / Massa Corrida
Hidráulica → Tubos e Conexões PVC / Metais e Louças / Caixas d'Água
Elétrica → Cabos e Fios / Disjuntores e Quadros / Tomadas e Interruptores
Ferragens e Fixação → Parafusos e Buchas / Pregos e Arames / Dobradiças
Madeira e Compensados → Tábuas e Vigas / MDF e Compensado / Portas e Janelas
Cobertura → Telhas / Calhas e Rufos / Impermeabilizante para Telhado
Ferramentas → Ferramentas Manuais / Ferramentas Elétricas / EPIs
Jardim e Paisagismo → Terra e Substrato / Produtos para Jardim
```

### 3b — Seeder de Atributos

Criar: `backend/database/seeders/ConstructionAttributeSeeder.php`

Atributos globais reutilizáveis:

| Atributo | Tipo | Uso |
|----------|------|-----|
| Dimensão | Text | Porcelanato (60x60cm), Madeira |
| Volume | Text | Tinta (3,6L, 18L) |
| Bitola | Text | Cabo elétrico (mm²) |
| Diâmetro | Text | Tubo PVC (mm) |
| Peso | Number | Sacos de cimento (kg) |
| Comprimento | Number | Tubos, madeira (m) |
| Cor | Color | Tintas, cabos |
| Voltagem | Text | Ferramentas elétricas |
| Acabamento | Text | Porcelanato (Polido/Acetinado/Rústico) |
| Espessura | Number | Madeira, compensado (mm) |
| Resistência | Number | Blocos (MPa) |
| PEI | Text | Cerâmicas (PEI 0–5) |

### 3c — Seeder de Grids

Criar: `backend/database/seeders/ConstructionGridSeeder.php`

| Grid | Atributo(s) | Valores típicos |
|------|------------|----------------|
| Grid Porcelanato | Dimensão | 30x30 / 45x45 / 60x60 / 80x80 / 90x90 / 120x60 |
| Grid Tinta | Volume | 900ml / 3,6L / 18L |
| Grid Cabo | Bitola (mm²) | 1,5 / 2,5 / 4,0 / 6,0 / 10,0 |
| Grid Tubo PVC | Diâmetro (mm) | 20 / 25 / 32 / 40 / 50 / 75 / 100 |
| Grid Madeira | Espessura (mm) | 15 / 18 / 20 / 25 |
| Grid Saco | Peso (kg) | 5 / 20 / 25 / 40 / 50 |
| Grid Cor Tinta | Cor | Branco / Marfim / Bege / Cinza |

### 3d — Registrar seeders

Em `backend/database/seeders/DatabaseSeeder.php`:
- Adicionar chamada a `ConstructionCategorySeeder`
- Adicionar chamada a `ConstructionAttributeSeeder`
- Adicionar chamada a `ConstructionGridSeeder`
- Proteger com ambiente: executar apenas em `local`/`testing` (não em produção)

---

## Fase 4 — Frontend: Catálogo

**Pré-requisito:** Fase 1 concluída (para não ter conflitos com campo gender).

### 4a — ProductForm.tsx

1. Remover campo `Gênero` (já feito na Fase 1)
2. Adicionar campo `Unidade de Medida` (select):
   ```
   UN | M2 | ML | KG | L | SC | RL | PC
   ```
3. Adicionar campo `NCM` (input com máscara `####.##.##`)

### 4b — ProductList.tsx / product-table.tsx

1. Remover coluna `Gênero` se existir
2. Adicionar coluna `Unidade` com destaque visual
3. Manter coluna `Status` (com `Sazonal` ainda válido)

### 4c — VariantPicker.tsx

1. Remover render especial de "cor como bolinha colorida" (botão circular com `background-color`)
2. Garantir que o picker use `attribute.name` como rótulo dinâmico (ex: "Dimensão", "Bitola")
3. Testar com grid de 1 atributo (construção) e 2 atributos (tinta: cor + volume)

### 4d — variant-table.tsx (se existir)

1. Substituir cabeçalhos hardcoded "Tamanho / Cor" por nome dinâmico do atributo

### 4e — category-selector.tsx

1. Testar com nova hierarquia de 10 categorias de construção (sem alteração de código esperada)

---

## Fase 5 — Frontend: PDV Web

**Pré-requisito:** Fases 1 e 2 concluídas (quantity decimal no banco).

### 5a — Suporte a quantidade decimal

1. `CartItemRow.tsx` ou equivalente:
   - Alterar `input type="number"` para aceitar `step="0.01"`
   - Alterar lógica de incremento/decremento para suportar decimais

2. `CartTotals.tsx` ou equivalente:
   - Verificar se `qty × price` arredonda corretamente com decimais
   - Usar `parseFloat` ou `Number()` consistentemente

### 5b — Exibir unidade de medida no carrinho

1. Exibir unidade ao lado da quantidade: `"3,75 M2"` em vez de `"3.75"`
2. Fonte: campo `unit_of_measure` do produto (adicionado na Fase 2b)

### 5c — Cupom de venda

1. Verificar formato de impressão da quantidade decimal no cupom
2. Garantir que `3.75` apareça como `3,75` (localização pt-BR)

---

## Fase 6 — Seeds de Demonstração

**Pré-requisito:** Fases 2 e 3 concluídas (campos e categorias existem no banco).

### 6a — ConstructionProductSeeder.php

10 produtos demo:

| Produto | Tipo | Categoria |
|---------|------|-----------|
| Cimento CP II-E 50kg (Votorantim) | Simple | Alvenaria > Argamassa e Cimento |
| Porcelanato Polido Cinza 60x60cm | Variant (Dimensão) | Revestimentos > Porcelanato |
| Tinta Acrílica Branco Neve (Suvinil) | Variant (Volume) | Pintura > Tinta Acrílica |
| Cabo Flexível 2,5mm² 100m (Tramontina) | Variant (Bitola) | Elétrica > Cabos e Fios |
| Tubo PVC Soldável 50mm × 6m | Variant (Diâmetro) | Hidráulica > Tubos |
| Parafuso Cabeça Philips 4,2×38mm (c/100) | Simple | Ferragens > Parafusos e Buchas |
| Argamassa ACIII 20kg (Weber) | Simple | Alvenaria > Argamassa e Cimento |
| Tijolo Cerâmico 9×19×19cm (caixa 100) | Simple | Alvenaria > Tijolos e Blocos |
| Telha Ondulada 2,13m (Eternit) | Simple | Cobertura > Telhas |
| Kit Registro + Válvula + Ligação | Kit | Hidráulica > Metais e Louças |

### 6b — ConstructionSupplierSeeder.php

4 fornecedores demo: Votorantim, Suvinil, Tigre, Tramontina

### 6c — ConstructionCustomerSeeder.php

2 clientes adicionais:
- Construtora Exemplo Ltda. (CNPJ)
- João da Silva — Pedreiro Autônomo (CPF)

---

## Fase 7 — Finalização

**Pré-requisito:** Todas as fases anteriores.

### 7a — Labels e textos

| Arquivo | Campo | De | Para |
|---------|-------|----|------|
| `backend/config/scribe.php` | `description` | `varejo de moda multi-tenant` | `varejo de material de construção multi-tenant` |
| `backend/app/Modules/Sales/Enums/ReturnReasonEnum.php` | label `DoesNotFit` | `Não serviu / tamanho errado` | `Produto inadequado / medida incorreta` |

### 7b — Verificar campo NCM nas variantes

Confirmar se `catalog_variants` já tem coluna `ncm` no banco (o Model já tem em `$fillable`).
Se não existir: criar migration `add_ncm_to_catalog_variants`.

### 7c — Atualizar documentação

- `README.md` na raiz: atualizar descrição da plataforma
- `memory/project_store_saas.md`: atualizar contexto de domínio
- `docs/MIGRACAO_MODA_PARA_CONSTRUCAO.md`: marcar como "Substituído por docs/migration/"

### 7d — Validação final

```bash
# 1. Zero referências de moda no código
grep -rn "gender\|ProductGender\|Camiseta\|varejo de moda" backend/app backend/tests --include="*.php"
grep -rn "gender\|ProductGender" frontend/src --include="*.ts" --include="*.tsx"

# 2. Todos os testes passando
php artisan test

# 3. Build TypeScript sem erros
pnpm --filter frontend build

# 4. Teste de fluxo completo
# Cadastrar produto Porcelanato → variante 60x60cm
# Vender 3,75 M2 no PDV Web
# Verificar cupom com quantidade decimal
```

---

## Ordem de Execução Visual

```
SEMANA 1
  Dia 1:  Fase 1 (Limpeza de moda — backend + frontend)
  Dia 2:  Fase 2 (Migrations — dropar gender, qty→decimal, adicionar campos)
  Dia 3:  Fase 3a + 3b (Seeders de categorias e atributos)
  Dia 4:  Fase 3c + 3d (Grids + registro no DatabaseSeeder)
  Dia 5:  Fase 4a + 4b (ProductForm + ProductList)

SEMANA 2
  Dia 1:  Fase 4c + 4d (VariantPicker + variant-table)
  Dia 2:  Fase 5 (PDV Web — qty decimal + unidade)
  Dia 3:  Fase 6 (Seeds de demonstração)
  Dia 4:  Fase 7 (Labels, config, docs)
  Dia 5:  Validação final + testes de regressão
```

---

## Critérios de Conclusão

A migração está completa quando **todos** os itens abaixo forem verdadeiros:

1. `grep -rn "gender\|ProductGenderEnum" backend/app` → zero resultados
2. `grep -rn "ProductGender" frontend/src` → zero resultados
3. `php artisan test` → 100% passando
4. `pnpm build` em `frontend/` → zero erros TypeScript
5. É possível cadastrar Porcelanato 60x60cm com variante de Dimensão
6. É possível vender `3,75 M2` no PDV Web com quantidade decimal
7. Cupom exibe `3,75 M2 × R$ 45,00 = R$ 168,75`
8. Campo NCM preenchível no cadastro de produto

---

*Plano gerado em 2026-06-20. Atualizar status à medida que as fases forem concluídas.*
