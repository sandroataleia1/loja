# AUDITORIA COMPLETA — MÓDULO 05: PRODUTOS

**Data:** 2026-06-24
**Domínio:** ERP SaaS para Material de Construção
**Escopo:** Catálogo de Produtos, Variantes, Atributos, Categorias, Marcas, Unidades, Estoque

---

## 1. RESUMO EXECUTIVO

O módulo de Produtos encontra-se **bem estruturado** para o domínio de material de construção. O campo `gender` (moda) foi removido via migration `2026_06_20_000001`. As categorias, atributos e grades estão completamente alinhados ao domínio de construção. O estoque está segregado em `inventory_balances` (variante × loja), sem quantidade diretamente no produto — arquitetura correta para ERP multi-loja.

**Lacunas principais identificadas:**
- Conversão de unidades não implementada (crítico para construção)
- Seeds de produtos reais não criados (Cimento Votoran, Tubo PVC, etc.)
- Detalhe do produto não exibe campos fiscais (NCM, CEST, CFOP)
- Listagem não tem filtro por marca/categoria

---

## 2. O QUE JÁ EXISTE E ESTÁ CORRETO

### 2.1 Backend — Modelos

| Modelo | Tabela | Status |
|--------|--------|--------|
| Product | catalog_products | ✅ Completo |
| Variant | catalog_variants | ✅ Completo |
| Category | catalog_categories | ✅ Completo |
| Brand | catalog_brands | ✅ Completo (+ fabricante integrado) |
| AttributeGroup | catalog_attribute_groups | ✅ Completo |
| Attribute | catalog_attributes | ✅ Completo |
| Grid | catalog_grids | ✅ Completo |
| ProductCollection | catalog_collections | ✅ (campanhas comerciais, não moda) |
| ProductImage | catalog_images | ✅ Legado (coexiste com ProductMedia) |
| ProductPriceHistory | product_price_history | ✅ Completo |

### 2.2 Campos do Produto (`catalog_products`)

| Campo | Tipo | Status |
|-------|------|--------|
| uuid | UUID PK | ✅ |
| tenant_id | UUID FK | ✅ |
| code | string (sequencial) | ✅ |
| name | string(200) | ✅ |
| slug | string(220) UNIQUE/tenant | ✅ |
| description / short_description | text | ✅ |
| type | enum (simple\|variable\|kit) | ✅ |
| unit_of_measure | enum (UN\|M\|M2\|M3\|KG\|LT\|CX\|SC) | ✅ |
| ncm | string(10) | ✅ |
| cest | string(9) | ✅ |
| cfop_default | string(5) | ✅ |
| origin_code | tinyint (0–8) | ✅ |
| status | enum (draft\|active\|inactive\|archived\|seasonal) | ✅ |
| visibility | enum (PRIVATE\|PUBLIC\|UNLISTED) | ✅ |
| base_price | decimal(15,2) | ✅ |
| cost_price | decimal(15,2) | ✅ |
| brand_id | UUID FK nullable | ✅ |
| is_featured / is_digital / is_publishable | boolean | ✅ |
| season | string(50) | ⚠️ Ver seção 4 |
| seo | jsonb | ✅ |
| metadata | jsonb | ✅ |
| soft delete | deleted_at | ✅ |

### 2.3 Campos da Variante (`catalog_variants`)

| Campo | Tipo | Status |
|-------|------|--------|
| sku | string(100) UNIQUE/tenant | ✅ |
| barcode | string(50) | ✅ |
| gtin | string(14) EAN/UPC | ✅ |
| price_cents | integer | ✅ |
| cost_cents | integer | ✅ |
| compare_at_cents | integer | ✅ |
| weight_g | integer | ✅ |
| dimensions | jsonb | ✅ |
| grid_combination | jsonb | ✅ |
| is_default / is_active | boolean | ✅ |
| ncm / cest / cfop_default / origin_code | fiscal | ✅ |
| tax_profile_id | UUID FK | ✅ |

### 2.4 Enums Implementados

| Enum | Valores | Status |
|------|---------|--------|
| UnitOfMeasureEnum | UN, M, M2, M3, KG, LT, CX, SC | ✅ |
| ProductTypeEnum | simple, variable, kit | ✅ |
| ProductStatusEnum | draft, active, inactive, archived, seasonal | ✅ |
| ProductVisibilityEnum | PRIVATE, PUBLIC, UNLISTED | ✅ |
| ProductOriginEnum | 0–8 (SEFAZ) | ✅ |
| AttributeTypeEnum | text, color, number, boolean | ✅ |

### 2.5 Actions Implementadas

| Action | Propósito | Status |
|--------|-----------|--------|
| CreateProductAction | Criar produto, código sequencial, categorias | ✅ |
| UpdateProductAction | Atualizar produto, sync categorias | ✅ |
| CreateVariantAction | Criar variante, snapshot grid_combination | ✅ |
| UpdateVariantAction | Atualizar variante | ✅ |
| GenerateVariantsAction | Produto cartesiano (Grid → Variantes) | ✅ |
| PublishProductAction | Publicar produto | ✅ |
| ArchiveProductAction | Arquivar produto | ✅ |
| DuplicateProductAction | Duplicar produto | ✅ |
| CreateBrandAction | Criar marca + código | ✅ |
| CreateCategoryAction | Criar categoria hierárquica | ✅ |

### 2.6 API Routes (`/api/v1/catalog/...`)

```
GET    /products                         Listagem com filtros
GET    /products/{uuid}                  Detalhe
POST   /products                         Criar
PUT    /products/{uuid}                  Atualizar
DELETE /products/{uuid}                  Exclusão lógica
POST   /products/{uuid}/publish          Publicar
POST   /products/{uuid}/archive          Arquivar
POST   /products/{uuid}/duplicate        Duplicar
GET    /products/{uuid}/variants         Variantes do produto
POST   /variants                         Criar variante
PUT    /variants/{uuid}                  Atualizar variante
DELETE /products/{uuid}/variants/{uuid}  Remover variante
GET    /brands                           Listagem marcas
POST   /brands                           Criar marca
GET    /categories                       Listagem categorias
POST   /categories                       Criar categoria
GET    /attribute-groups                 Listagem grupos de atributos
GET    /grids                            Listagem grades
```

### 2.7 Seeds de Construção

| Seeder | Dados | Status |
|--------|-------|--------|
| ConstructionCategorySeeder | 10 categorias + 40 subcategorias | ✅ |
| ConstructionAttributeSeeder | 10 grupos + 60+ atributos | ✅ |
| ConstructionGridSeeder | Grades por grupo de atributo | ✅ |

### 2.8 Frontend

| Página | Rota | Status |
|--------|------|--------|
| Listagem | /products | ✅ Completo |
| Criar | /products/create | ✅ Completo |
| Editar | /products/{uuid}/edit | ✅ Completo |
| Detalhe | /products/{uuid} | ✅ Parcial (fiscal não exibido) |
| Marcas | /catalog/brands | ✅ Completo |
| Categorias | /catalog/categories | ✅ Completo |
| Atributos | /catalog/attributes | ✅ Completo |
| Grades | /catalog/grids | ✅ Completo |

---

## 3. O QUE ESTÁ PARCIALMENTE IMPLEMENTADO

### 3.1 Detalhe do Produto — Fiscal Ausente
- **Arquivo:** `frontend/src/app/(app)/products/[uuid]/page.tsx`
- **Problema:** Aba "Geral" exibe unidade, visibilidade, preços, flags — mas **não exibe NCM, CEST, CFOP, origem**
- **Impacto:** Usuário não consegue confirmar dados fiscais sem editar
- **Ação:** Adicionar seção "Dados Fiscais" na aba Geral

### 3.2 Listagem — Sem Filtro por Marca/Categoria
- **Arquivo:** `frontend/src/app/(app)/products/page.tsx`
- **Problema:** Filtros limitados a busca textual e status
- **Faltam:** filtro por marca, por categoria, por tipo, por unidade
- **Ação:** Adicionar seletores de filtro

### 3.3 Conversão de Unidades — Estrutura Ausente
- Enum `UnitOfMeasureEnum` com `isDecimalAllowed()` ✅
- **Falta:** tabela `unit_conversions`, `UnitConversionService`
- Sem isso: impossível converter 1 CX = 12 UN, 1 SC = 25 KG
- **Ação:** Fase 9 — implementar migration + service

### 3.4 Analytics — Campos sem Listeners Confirmados
- Campos `sales_velocity`, `days_without_sale`, `stock_age`, `last_sale_at` existem
- Listeners `UpdateProductAnalyticsOnSaleCompleted`, `UpdateProductAnalyticsOnSaleReturned` declarados
- Não foi possível confirmar se listeners preenchem os campos corretamente

---

## 4. O QUE ESTÁ INCORRETO OU PERTENCE AO DOMÍNIO MODA

### 4.1 Campo `season` — Risco Moderado
- **Arquivo:** `backend/app/Modules/Catalog/Models/Product.php`
- **Campo:** `season string(50)` em `catalog_products`
- **DOMAIN_RULES proíbe:** "estação" e "coleção"
- **Avaliação:** O campo está sendo usado como rótulo de campanha (ex: "Promoção Julho 2026") — uso legítimo para construção
- **Recomendação:** Renomear para `campaign_label` para clareza; não é urgente
- **Risco:** Baixo — campo de texto livre, sem enum de moda

### 4.2 `ProductCollection` — Risco Baixo
- **Modelo:** `catalog_collections`
- **DOMAIN_RULES proíbe:** "coleção"
- **Avaliação:** Usado para campanhas comerciais com datas (Promoção, Liquidação) — uso legítimo
- **Comentários no código:** "coleção editorial primária", "campanhas sazonais" — apenas nomenclatura interna
- **Recomendação:** Documentar que é "campanha", não "coleção de moda"; sem remoção urgente

### 4.3 Campo `gender` — REMOVIDO ✅
- **Migration:** `2026_06_20_000001_remove_gender_from_catalog_products.php`
- **Status:** Completamente removido da tabela e do modelo

---

## 5. O QUE DEVE SER REMOVIDO

Nada crítico identificado. O campo `gender` já foi removido. Os demais itens são reutilizáveis com ajustes de nomenclatura.

---

## 6. O QUE DEVE SER REAPROVEITADO

| Componente | Motivo |
|------------|--------|
| Grid + GenerateVariantsAction | Variantes técnicas (Bitola × Diâmetro) |
| UnitOfMeasureEnum | Já implementado corretamente |
| ProductOriginEnum | Fiscal SEFAZ completo |
| ConstructionCategorySeeder | 50 categorias de construção |
| ConstructionAttributeSeeder | 10 grupos + 60 atributos técnicos |
| ProductPriceHistory | Auditoria de preços |
| inventory_balances | Estoque por variante × loja |
| StockMovement (imutável) | Rastreabilidade de estoque |

---

## 7. O QUE AINDA FALTA

| Item | Fase | Prioridade |
|------|------|-----------|
| Conversão de unidades | 9 | Alta |
| Seeds de produtos reais | 17 | Alta |
| Exibir fiscal no detalhe | 15 | Média |
| Filtros brand/categoria na listagem | 15 | Média |
| Documentação arquitetura | 2 | Alta |
| Documentação estoque | 3 | Alta |

---

## 8. PROBLEMAS ARQUITETURAIS ENCONTRADOS

### P1 — Ambiguidade de Preço (Produto vs. Variante)
- `catalog_products`: `base_price`, `cost_price` (decimal)
- `catalog_variants`: `price_cents`, `cost_cents` (centavos inteiros)
- Sem política documentada de qual usar
- **Risco:** PDV pode ler campo errado

### P2 — Duplicação de Campos Fiscais
- NCM, CEST, CFOP e origin_code em `catalog_products` E em `catalog_variants`
- `FiscalResolverService` existe para `origin_code` mas não para os demais
- **Risco:** Divergência entre produto e variante sem regra clara

### P3 — Dual Image System
- `catalog_images` (polymorphic, legado)
- `product_media` N:N com `media_assets`
- Ambos coexistem — pode causar confusão
- **Recomendação:** Migrar para `product_media` em Módulo seguinte

### P4 — Sem Conversão de Unidades
- Crítico para construção: 1 CX = 12 UN, 1 SC = 25 KG, 1 M² = N CX
- Afeta compras, estoque e vendas
- **Ação:** Implementar na Fase 9

---

## 9. SITUAÇÃO DA MODELAGEM DE ESTOQUE

**Arquitetura atual: CORRETA para ERP**

```
Produto
  └── Variante (SKU)
        └── inventory_balances (variante × loja)
              ├── quantity DECIMAL(15,3)
              └── reserved_quantity DECIMAL(15,3)
```

- Não há `quantity` ou `stock` em `catalog_products` ou `catalog_variants`
- Estoque por loja implementado em `inventory_balances`
- Movimentações imutáveis em `inventory_movements`
- Suporte a quantidades fracionadas (DECIMAL 15,3)
- Detalhes completos no documento `STOCK_ARCHITECTURE_REVIEW.md`

---

## 10. RECOMENDAÇÕES PARA MÓDULO 06

1. Implementar módulo de estoque completo (entrada, saída, ajuste, inventário)
2. Implementar tabela de preços com múltiplas listas
3. Migrar imagens de `catalog_images` para `product_media` + `media_assets`
4. Renomear `season` → `campaign_label` para clareza
5. Decidir política de preço: variante sobrescreve produto ou produto é default
6. Centralizar resolução de campos fiscais (NCM, CEST, CFOP)
