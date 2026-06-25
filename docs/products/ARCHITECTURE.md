# ARQUITETURA DO MÓDULO DE PRODUTOS

**Data:** 2026-06-24
**Domínio:** ERP SaaS para Material de Construção

---

## 1. VISÃO GERAL

```
Produto (Product)
├── Variante (Variant) ← SKU, preço, estoque
│   ├── Atributos (Attribute) ← Cor, Bitola, Diâmetro
│   └── InventoryBalance ← quantidade × loja
├── Categoria (Category) ← hierárquica
├── Marca/Fabricante (Brand)
└── Grid ← define quais atributos geram variantes
```

---

## 2. ENTIDADES PRINCIPAIS

### 2.1 Product (`catalog_products`)

**Propósito:** Definição do produto. Sem quantidade, sem estoque.

```
Campos obrigatórios:
├── code          → Código interno sequencial (P-0001, P-0002...)
├── name          → Nome do produto
├── type          → simple | variable | kit
├── unit_of_measure → UN | M | M2 | M3 | KG | LT | CX | SC
├── status        → draft | active | inactive | archived | seasonal
└── tenant_id     → Multi-tenancy

Campos comerciais:
├── base_price    → Preço base (decimal)
├── cost_price    → Custo de aquisição
└── visibility    → PRIVATE | PUBLIC | UNLISTED

Campos fiscais (SEFAZ):
├── ncm           → Nomenclatura Comum Mercosul (8 dígitos)
├── cest          → Código Substituição Tributária (7 dígitos)
├── cfop_default  → CFOP de saída padrão (4 dígitos)
└── origin_code   → Origem 0–8

Relacionamentos:
├── brand_id      → Marca/Fabricante
├── categories[]  → Categorias (N:N com sort_order)
├── collection_id → Campanha editorial principal
└── grid_id       → Grade de variantes
```

### 2.2 Variant (`catalog_variants`)

**Propósito:** SKU individual. Herda produto, especializa atributos.

```
Campos de identidade:
├── sku           → Identificador único por tenant
├── barcode       → EAN / código de barras
├── gtin          → EAN-13 / UPC-A (14 dígitos)
└── code          → Código sequencial interno

Campos comerciais:
├── price_cents       → Preço em centavos (sobrescreve produto)
├── cost_cents        → Custo em centavos
└── compare_at_cents  → Preço "de" (risca e desconto)

Composição de atributos:
└── grid_combination jsonb → snapshot [{group, attr, value}]

Fiscal (override do produto):
├── ncm / cest / cfop_default / origin_code
└── tax_profile_id → Perfil de impostos
```

### 2.3 Category (`catalog_categories`)

**Propósito:** Classificação hierárquica (pai → filho).

```
├── parent_id → self-referencing (null = categoria raiz)
├── code      → identificador interno
└── sort_order → ordenação manual

Hierarquia suportada:
Ferragens
  └── Parafusos e Buchas
  └── Dobradiças e Fechaduras
```

### 2.4 Brand (`catalog_brands`)

**Propósito:** Marca comercial e informações do fabricante (unificados).

```
Campos da marca:
├── name / slug / code
├── description / logo_url / website_url
└── is_active

Campos do fabricante (integrados):
├── manufacturer_cnpj
├── manufacturer_contact_name
├── manufacturer_contact_email
└── manufacturer_contact_phone
```

**Decisão de design:** Fabricante e marca foram unificados no mesmo modelo pois em material de construção frequentemente coincidem (Suvinil fabrica a Suvinil). Caso haja necessidade de separação futura, expandir `Brand` com flag `is_manufacturer`.

### 2.5 AttributeGroup + Attribute

**Propósito:** Definição dinâmica de atributos técnicos — evita criar colunas para cada tipo.

```
AttributeGroup
├── name  → "Bitola", "Diâmetro", "Tensão"
├── type  → text | color | number | boolean
└── slug

Attribute (valores por grupo):
├── value → "2.5mm²", "50mm", "220V"
├── label → valor exibido ao usuário
└── color_hex → apenas para type=color
```

**Grupos implementados:**
| Grupo | Exemplos | Uso |
|-------|----------|-----|
| Bitola | 1.5mm², 2.5mm², 4.0mm² | Cabos elétricos |
| Diâmetro | 20mm, 25mm, 50mm | Tubos PVC |
| Comprimento | 1m, 2m, 6m | Barras, tubos |
| Espessura | 6mm, 10mm, 19mm | Chapas, vidros |
| Volume | 900ml, 3.6L, 18L | Tintas |
| Potência | 600W, 1500W | Ferramentas |
| Tensão | 127V, 220V, bivolt | Elétrico |
| Material | PVC, Cobre, Aço | Genérico |
| Cor | Branco, Cinza, Azul | Visual |
| Acabamento | Polido, Fosco, Rústico | Superfícies |

### 2.6 Grid (`catalog_grids`)

**Propósito:** Define conjunto de atributos que geram variantes automaticamente.

```
Grid "Volume de Tinta"
└── atributos: [900ml, 3.6L, 18L]

→ GenerateVariantsAction cria:
   Tinta Suvinil / 900ml  (SKU: SKU-001-1)
   Tinta Suvinil / 3.6L   (SKU: SKU-001-2)
   Tinta Suvinil / 18L    (SKU: SKU-001-3)
```

---

## 3. ARQUITETURA DE VARIANTES PARA MATERIAL DE CONSTRUÇÃO

### Exemplo 1 — Tinta Suvinil

```
Produto: Tinta Suvinil Acrílica Premium
├── tipo: variable
├── unidade: LT
└── grid: "Volume"
    ├── Variante: 900ml  → SKU: SUV-ACR-900
    ├── Variante: 3.6L   → SKU: SUV-ACR-3600
    └── Variante: 18L    → SKU: SUV-ACR-18000
```

### Exemplo 2 — Cimento Portland

```
Produto: Cimento Portland CP-II
├── tipo: simple
├── unidade: SC (saco)
└── variante: única
    └── SKU: CIM-CP2-50KG
        ├── atributo: Peso = 50kg
        └── estoque: 150 SC na Loja 1
```

### Exemplo 3 — Cabo Elétrico (multi-dimensional)

```
Produto: Cabo Flexível Cobre
├── tipo: variable
├── unidade: M
└── grid: "Bitola"
    ├── Variante: 1.5mm² → SKU: CAB-FLEX-1.5
    ├── Variante: 2.5mm² → SKU: CAB-FLEX-2.5
    ├── Variante: 4.0mm² → SKU: CAB-FLEX-4.0
    └── Variante: 6.0mm² → SKU: CAB-FLEX-6.0
```

---

## 4. VERIFICAÇÃO DE COMPATIBILIDADE COM MATERIAL DE CONSTRUÇÃO

### 4.1 Cimento, Argamassa, Gesso — ✅ COMPATÍVEL
- Unidade: SC (saco), KG
- Atributo: Tipo (CP-II, CP-III), Peso (25kg, 50kg)
- Variante: por peso ou tipo

### 4.2 Pisos e Revestimentos — ✅ COMPATÍVEL
- Unidade: M2 (metro quadrado), UN (caixa de unidade)
- Atributo: Dimensão (45×45, 60×60), Acabamento (polido, natural)
- Variante: por dimensão ou acabamento

### 4.3 Hidráulica (Tubos PVC) — ✅ COMPATÍVEL
- Unidade: M (metro), UN
- Atributo: Diâmetro (20mm, 50mm, 100mm), Comprimento (6m)
- Variante: por diâmetro

### 4.4 Elétrica (Cabos) — ✅ COMPATÍVEL
- Unidade: M (metro), CX (caixa com 100m)
- Atributo: Bitola (1.5mm², 2.5mm²), Tensão (127V, 220V)
- Variante: por bitola

### 4.5 Ferragens — ✅ COMPATÍVEL
- Unidade: UN, CX (caixa c/ 100un)
- Atributo: Bitola (4×30mm, 6×50mm), Material (Aço, Inox)
- Variante: por bitola e comprimento

### 4.6 Ferramentas — ✅ COMPATÍVEL
- Unidade: UN
- Atributo: Potência (600W, 800W), Tensão (127V, 220V, bivolt)
- Variante: por tensão

### 4.7 Tintas — ✅ COMPATÍVEL
- Unidade: LT, UN
- Atributo: Volume (900ml, 3.6L, 18L), Cor (cores com hex)
- Variante: por volume ou cor (combinável)

---

## 5. LIMITAÇÕES IDENTIFICADAS

### 5.1 Sem Conversão de Unidades — CRÍTICO
- **Problema:** Não há mecanismo para converter 1 CX = 12 UN, 1 SC = 25 KG
- **Impacto:** Impossível definir quanto estoque em UN equivale a N CX
- **Solução:** Implementar `unit_conversions` + `UnitConversionService` (Fase 9)

### 5.2 Ambiguidade de Preço — MÉDIO
- `Product.base_price` (decimal) vs. `Variant.price_cents` (centavos)
- Sem política documentada para produto simples (sem variantes)
- **Solução:** Documentar regra: variante sempre sobrescreve; produto usa `base_price` apenas como referência

### 5.3 Campos Fiscais Duplicados — BAIXO
- NCM, CEST, CFOP em produto E na variante
- Variante deve sobrescrever produto quando preenchida
- **Solução:** `FiscalResolverService` já implementado para `origin_code`; estender para NCM/CEST/CFOP

### 5.4 Dual Image System — BAIXO
- `catalog_images` (polymorphic, legado)
- `product_media` N:N com `media_assets` (moderno)
- **Solução:** Migrar para novo sistema no Módulo 06+

---

## 6. FLUXO DE CRIAÇÃO DE PRODUTO

```
1. POST /catalog/products
   → StoreProductRequest (validação)
   → CreateProductAction
      ├── Gerar código interno (GenerateInternalCodeAction)
      ├── Criar Product
      ├── Attach categories (N:N)
      └── Disparar ProductCreated event

2. POST /catalog/variants (para produto variável)
   → CreateVariantAction
      ├── Validar SKU único por tenant
      ├── Criar Variant
      ├── Sync attributes → grid_combination jsonb
      └── Disparar VariantCreated event

3. POST /catalog/products/{uuid}/variants/generate (opcional)
   → GenerateVariantsAction
      ├── Carregar Grid + atributos
      ├── Produto cartesiano
      └── Criar variante para cada combinação
```

---

## 7. ESTRUTURA DE DIRETÓRIOS

```
backend/app/Modules/Catalog/
├── Actions/         CreateProductAction, CreateVariantAction, GenerateVariantsAction...
├── DTOs/            CreateProductDTO, CreateVariantDTO...
├── Enums/           ProductTypeEnum, UnitOfMeasureEnum, ProductStatusEnum...
├── Events/          ProductCreated, ProductUpdated, ProductSold...
├── Http/
│   ├── Controllers/ ProductController, BrandController, CategoryController...
│   ├── Requests/    StoreProductRequest, StoreVariantRequest...
│   └── Resources/   ProductResource, VariantResource, BrandResource...
├── Listeners/       UpdateProductAnalyticsOnSaleCompleted...
├── Models/          Product, Variant, Brand, Category, Attribute...
└── Policies/        ProductPolicy

frontend/src/
├── features/catalog/
│   ├── components/  product-form, product-table, variant-table...
│   └── hooks/       useBrands, useProducts, useVariants...
├── app/(app)/
│   ├── products/    page, create, [uuid]/page, [uuid]/edit
│   └── catalog/     brands, categories, attributes, grids
└── services/        catalog.service.ts
```
