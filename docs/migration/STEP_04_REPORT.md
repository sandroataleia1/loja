# STEP 04 REPORT — Atributos Técnicos e Grids de Variação

**Data:** 2026-06-20
**Escopo:** Substituição de conceitos de moda por atributos técnicos de construção. Criação de 10 grupos de atributos, 50+ valores e 7 grades de variação prontas para uso.

---

## Objetivo

Substituir a semântica de moda (Tamanho P/M/G, Cor bolinha, Gênero) por atributos técnicos do domínio de material de construção, preservando a estrutura genérica `Produto → Variante → Atributo` já existente no sistema.

---

## Decisão Arquitetural: Nenhuma Migration Necessária

A estrutura `catalog_attribute_groups → catalog_attributes → catalog_grids → catalog_grid_items → catalog_variants` já era 100% genérica. Não foi necessário alterar o schema do banco, DTOs, Actions, Resources, Controllers ou tipos TypeScript — apenas popular o sistema com a semântica correta e remover as únicas referências de moda que existiam no código.

---

## Arquivos Criados

| Arquivo | Descrição |
|---------|-----------|
| `backend/database/seeders/ConstructionAttributeSeeder.php` | 10 grupos de atributos técnicos com 50+ valores |
| `backend/database/seeders/ConstructionGridSeeder.php` | 7 grades de variação prontas para geração de variantes |

---

## Arquivos Modificados

| Arquivo | Mudança |
|---------|---------|
| `backend/database/seeders/DatabaseSeeder.php` | Adicionados `ConstructionAttributeSeeder` e `ConstructionGridSeeder` no contexto do tenant demo |
| `backend/tests/Feature/Catalog/VariantTest.php` | SKU `'CAM-P-AZ'` → `'CBL-2P5-100M'` (cabo 2,5mm² 100m) |
| `frontend/src/features/catalog/components/variant-table.tsx` | Placeholder `"Ex.: CAMISETA-001"` → `"Ex.: PORTO-001"` |

---

## Grupos de Atributos Criados (ConstructionAttributeSeeder)

| # | Grupo | Tipo | Valores |
|---|-------|------|---------|
| 1 | **Bitola** | Text | 1,5mm² · 2,5mm² · 4,0mm² · 6,0mm² · 10,0mm² · 16,0mm² |
| 2 | **Diâmetro** | Text | 20mm · 25mm · 32mm · 40mm · 50mm · 75mm · 100mm |
| 3 | **Comprimento** | Text | 1m · 2m · 3m · 6m · 9m · 12m |
| 4 | **Espessura** | Text | 6mm · 8mm · 10mm · 12mm · 15mm · 18mm · 20mm · 25mm |
| 5 | **Volume** | Text | 900ml · 3,6L · 18L · 25L |
| 6 | **Potência** | Text | 600W · 900W · 1500W · 2000W · 3500W |
| 7 | **Tensão** | Text | 127V · 220V · Bivolt |
| 8 | **Material** | Text | PVC · Cobre · Aço Galvanizado · Alumínio · Madeira Maciça · Fibra de Vidro |
| 9 | **Cor** | Color | Branco · Marfim · Bege · Cinza Claro · Cinza Escuro · Preto · Azul · Verde · Vermelho · Amarelo |
| 10 | **Acabamento** | Text | Polido · Acetinado · Rústico · Natural · Fosco · Brilhante |

**Total:** 10 grupos · 56 valores de atributo

---

## Grades de Variação Criadas (ConstructionGridSeeder)

| Grade | Grupo | Variações |
|-------|-------|-----------|
| **Grade Cabo Elétrico** | Bitola | 1,5mm² / 2,5mm² / 4,0mm² / 6,0mm² / 10,0mm² |
| **Grade Tubo PVC** | Diâmetro | 20mm / 25mm / 32mm / 40mm / 50mm / 75mm / 100mm |
| **Grade Madeira Serrada** | Espessura | 15mm / 18mm / 20mm / 25mm |
| **Grade Tinta Volume** | Volume | 900ml / 3,6L / 18L |
| **Grade Cor Tinta** | Cor | Branco / Marfim / Bege / Cinza Claro / Cinza Escuro |
| **Grade Ferramentas Elétricas** | Tensão | 127V / 220V / Bivolt |
| **Grade Acabamento Porcelanato** | Acabamento | Polido / Acetinado / Rústico |

**Total:** 7 grades · 30 variantes pré-configuradas

---

## Verificação dos Componentes Frontend

### VariantPicker.tsx — Já era genérico
```tsx
// Usa grid_combination.attr_value dinamicamente — zero código fashion
const attrs = v.grid_combination?.map((g) => g.attr_value).join(' / ')
return <p>{attrs ?? v.name ?? v.sku}</p>
```

### variant-table.tsx — Corrigido
```tsx
// Antes
placeholder="Ex.: CAMISETA-001"

// Depois
placeholder="Ex.: PORTO-001"
```

---

## Fluxo de Uso Pós-Seed

```
Produto "Cabo Flexível 750V" (type: variable)
  ↓
Gerar Variantes → Selecionar "Grade Cabo Elétrico"
  ↓
Sistema cria 5 variantes automaticamente:
  • CBL-001-1,5mm²  (Bitola: 1,5 mm²)
  • CBL-001-2,5mm²  (Bitola: 2,5 mm²)
  • CBL-001-4,0mm²  (Bitola: 4,0 mm²)
  • CBL-001-6,0mm²  (Bitola: 6,0 mm²)
  • CBL-001-10,0mm² (Bitola: 10,0 mm²)
  ↓
PDV → Clicar no produto → VariantPicker exibe "2,5 mm²" como rótulo
```

---

## Validação TypeScript

```
npx tsc --noEmit → 0 erros ✅
```

---

## Como Executar

### Via DatabaseSeeder (ambiente fresh)

```bash
docker compose exec app php artisan migrate:fresh --seed
```

### Standalone (banco já existente com categoria seeder rodado)

```bash
# 1. Atributos (depende de TenantContext com tenant válido)
docker compose exec app php artisan db:seed --class=ConstructionAttributeSeeder

# 2. Grades (depende dos atributos existirem)
docker compose exec app php artisan db:seed --class=ConstructionGridSeeder
```

### Verificar resultado

```sql
-- Grupos de atributos criados
SELECT name, type, sort_order FROM catalog_attribute_groups ORDER BY sort_order;
-- Esperado: 10 linhas

-- Atributos por grupo
SELECT ag.name AS grupo, a.label, a.color_hex
FROM catalog_attributes a
JOIN catalog_attribute_groups ag ON ag.uuid = a.attribute_group_id
ORDER BY ag.sort_order, a.sort_order;
-- Esperado: 56 linhas

-- Grades criadas
SELECT g.name, ag.name AS grupo
FROM catalog_grids g
JOIN catalog_attribute_groups ag ON ag.uuid = g.attribute_group_id
ORDER BY g.created_at;
-- Esperado: 7 linhas
```

---

## Compatibilidade Preservada

| Recurso | Status |
|---------|--------|
| `GET /catalog/attribute-groups` | ✅ Sem mudança — retorna os novos grupos |
| `POST /catalog/attribute-groups` | ✅ Sem mudança — cria grupos via UI |
| `GET /catalog/grids` | ✅ Sem mudança — retorna as novas grades |
| `POST /catalog/variants/generate` | ✅ Sem mudança — gera variantes com os novos atributos |
| `AttributeTypeEnum` | ✅ Sem mudança — Text/Color/Number/Boolean já cobrem todos os casos |
| Tipos TypeScript | ✅ Sem mudança — `AttributeGroup`, `Attribute`, `Grid` são genéricos |
| Testes existentes | ✅ VariantTest atualizado (SKU CAM-P-AZ → CBL-2P5-100M) |

---

## Referências de Moda Removidas Neste Step

| Arquivo | Antes | Depois |
|---------|-------|--------|
| `VariantTest.php` | `'CAM-P-AZ'` (camiseta) | `'CBL-2P5-100M'` (cabo) |
| `variant-table.tsx` | `"Ex.: CAMISETA-001"` | `"Ex.: PORTO-001"` |

---

## Próximo Step Recomendado

**Step 05 — Seeds de Demonstração** (Fase 6):
- `ConstructionProductSeeder`: 10 produtos demo com variantes geradas pelas novas grades
  - Cabo Flexível 750V (variante por bitola)
  - Porcelanato Polido (variante por acabamento)
  - Tinta Acrílica Suvinil (variante por volume)
  - Tubo PVC Soldável (variante por diâmetro)
