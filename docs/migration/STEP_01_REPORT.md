# STEP 01 REPORT — Quantidades Decimais e Unidade de Medida

**Data:** 2026-06-20
**Escopo:** Suporte a `DECIMAL(15,3)` em todos os campos de quantidade e campo `unit_of_measure` em produtos

---

## Objetivo

Converter o sistema de quantidades inteiras para decimais, suportando valores como `3,75 m²`, `2,50 m`, `15,250 kg`, e adicionar o campo Unidade de Medida ao produto com os valores: **UN · M · M² · M³ · KG · LT · CX · SC**.

---

## Arquivos Criados

| Arquivo | Descrição |
|---------|-----------|
| `backend/app/Modules/Catalog/Enums/UnitOfMeasureEnum.php` | Enum PHP com 8 unidades, métodos `label()` e `isDecimalAllowed()` |
| `backend/database/migrations/2026_06_20_000002_add_unit_of_measure_and_decimal_quantities.php` | Migration: `unit_of_measure` em produtos + `DECIMAL(15,3)` em 10 tabelas |

---

## Arquivos Modificados

### Backend — Models

| Arquivo | Mudança |
|---------|---------|
| `backend/app/Modules/Catalog/Models/Product.php` | Cast `unit_of_measure → UnitOfMeasureEnum`, fillable atualizado |
| `backend/app/Modules/Sales/Models/SaleItem.php` | Cast `quantity → decimal:3`, aritmética com `round()` |
| `backend/app/Modules/Sales/Models/SaleReturnItem.php` | Cast `quantity_returned → decimal:3` |
| `backend/app/Modules/Inventory/Models/InventoryBalance.php` | Casts `decimal:3`, `applyQuantityChange(float\|int)`, `getAvailableQuantityAttribute(): float` |
| `backend/app/Modules/Inventory/Models/StockMovement.php` | 5 casts `decimal:3` |
| `backend/app/Modules/Inventory/Models/InventoryAdjustment.php` | 3 casts `decimal:3` |
| `backend/app/Modules/Inventory/Models/StockCountItem.php` | 3 casts `decimal:3` |
| `backend/app/Modules/Purchasing/Models/PurchaseOrderItem.php` | 3 casts `decimal:3`, `pendingQuantity(): float` |
| `backend/app/Modules/Purchasing/Models/PurchaseReceiptItem.php` | Cast `quantity_received → decimal:3` |
| `backend/app/Modules/Conditional/Models/ConditionalItem.php` | 3 casts `decimal:3`, `totalCents()` com `round()`, `pendingQuantity(): float` |

### Backend — Catálogo (DTOs, Requests, Actions, Resources)

| Arquivo | Mudança |
|---------|---------|
| `backend/app/Modules/Catalog/DTOs/CreateProductDTO.php` | `$unitOfMeasure: ?UnitOfMeasureEnum` (substituiu `$gender`) |
| `backend/app/Modules/Catalog/DTOs/UpdateProductDTO.php` | Idem |
| `backend/app/Modules/Catalog/Http/Requests/StoreProductRequest.php` | `unit_of_measure: nullable, Rule::enum(UnitOfMeasureEnum::class)` |
| `backend/app/Modules/Catalog/Http/Requests/UpdateProductRequest.php` | Idem |
| `backend/app/Modules/Catalog/Actions/CreateProductAction.php` | Passa `unit_of_measure` para `Product::create()` |
| `backend/app/Modules/Catalog/Actions/UpdateProductAction.php` | Idem |
| `backend/app/Modules/Catalog/Http/Resources/ProductResource.php` | Expõe `unit_of_measure` e `unit_of_measure_label` |

### Backend — Fábrica e Testes

| Arquivo | Mudança |
|---------|---------|
| `backend/database/factories/ProductFactory.php` | `unit_of_measure => UnitOfMeasureEnum::UN` (substituiu `gender`) |
| `backend/tests/Feature/Catalog/CatalogTest.php` | Produtos renomeados para material de construção, `gender` removido, `unit_of_measure` adicionado |

### Frontend — Tipos

| Arquivo | Mudança |
|---------|---------|
| `frontend/src/types/shared-types.ts` | `type UnitOfMeasure`, interface `Product` com `unit_of_measure / unit_of_measure_label` |
| `frontend/src/types/contracts.ts` | `CreateProductRequest.unit_of_measure?: UnitOfMeasure \| null` |

### Frontend — Catálogo

| Arquivo | Mudança |
|---------|---------|
| `frontend/src/features/catalog/components/product-form.tsx` | UNIT_OPTIONS, Zod schema, default `'UN'`, select substituiu gender |
| `frontend/src/app/(app)/products/[uuid]/page.tsx` | Exibe `unit_of_measure_label` no lugar de `gender_label` |

### Frontend — PDV

| Arquivo | Mudança |
|---------|---------|
| `frontend/src/features/pdv/stores/pdvCartStore.ts` | `PdvCartItem.unitOfMeasure: string \| null`, `Math.round()` no subtotal |
| `frontend/src/features/pdv/components/cart/CartItemRow.tsx` | Exibe `"3,75 M2"`, +/- com step decimal (0.5 para M/M2/M3/KG/LT) |
| `frontend/src/features/pdv/components/product/ProductGrid.tsx` | Passa `unitOfMeasure` no `addItem()` |

---

## Detalhes Técnicos

### Migration: Colunas Geradas (MySQL)

MySQL não permite modificar colunas referenciadas por uma coluna gerada (`STORED`). A solução foi:

```php
// 1. Dropar a coluna gerada via raw SQL
DB::statement('ALTER TABLE purchase_order_items DROP COLUMN total_cost');

// 2. Modificar as colunas base com Blueprint
Schema::table('purchase_order_items', function (Blueprint $table): void {
    $table->decimal('quantity', 15, 3)->unsigned(false)->change();
    $table->decimal('received_quantity', 15, 3)->default(0)->unsigned(false)->change();
});

// 3. Recriar a coluna gerada com novo tipo
DB::statement('
    ALTER TABLE purchase_order_items
    ADD COLUMN total_cost DECIMAL(15,3)
    GENERATED ALWAYS AS (quantity * unit_cost) STORED
');
```

Tabelas afetadas: `inventory_adjustments.difference`, `stock_count_items.difference`, `purchase_order_items.total_cost`.

### Aritmética Decimal em PHP

Para evitar imprecisão em ponto flutuante ao multiplicar quantidade × preço:

```php
// Em SaleItem::buildFromVariantInput() e ConditionalItem::totalCents()
$subtotal = (int) round((float) $input['quantity'] * (int) $input['unit_price_cents']);
```

### Aritmética Decimal no TypeScript (PDV)

```typescript
// pdvCartStore.ts — subtotalCents()
sum + Math.round(item.unitPriceCents * item.quantity) - item.discountCents

// CartItemRow.tsx — lineTotal
Math.round(item.unitPriceCents * item.quantity) - item.discountCents
```

### UnitOfMeasureEnum — isDecimalAllowed()

```php
public function isDecimalAllowed(): bool {
    return in_array($this, [self::M, self::M2, self::M3, self::KG, self::LT], true);
}
```

Unidades UN, CX, SC usam quantidades inteiras. M, M², M³, KG, LT permitem decimais.

---

## Tabelas com DECIMAL(15,3) após a migration

| Tabela | Coluna(s) |
|--------|-----------|
| `catalog_products` | *(novo)* `unit_of_measure VARCHAR(10)` |
| `sale_items` | `quantity`, `returned_quantity` |
| `sale_return_items` | `quantity_returned` |
| `inventory_balances` | `quantity`, `reserved_quantity` |
| `inventory_movements` | `quantity`, `quantity_before`, `quantity_after`, `reserved_before`, `reserved_after` |
| `inventory_adjustments` | `previous_quantity`, `new_quantity`, `difference` (gerada) |
| `stock_count_items` | `system_quantity`, `counted_quantity`, `difference` (gerada) |
| `purchase_order_items` | `quantity`, `received_quantity`, `total_cost` (gerada) |
| `purchase_receipt_items` | `quantity_received` |
| `conditional_items` | `quantity`, `returned_quantity`, `sold_quantity` |

---

## Verificação TypeScript

```
npx tsc --noEmit → 0 erros
```

---

## Pendências pós-Step 01

1. **Executar a migration** (Docker deve estar up):
   ```bash
   docker compose exec app php artisan migrate
   ```

2. **Executar os testes**:
   ```bash
   docker compose exec app ./vendor/bin/pest
   ```

3. **Itens restantes da Fase 1** (não faziam parte do escopo deste step):
   - Remover filtro de `gender` no `ProductController`
   - Deletar `ProductGenderEnum.php`
   - Limpar `ProductGender` type de `shared-types.ts` e `contracts.ts`
   - Remover `gender?: string` do `catalog.service.ts`

---

## Próximo Step Recomendado

**Step 02 — Remover `gender` completamente** (Fase 1 itens 1.6, 1.8, 1.10 parcial, 1.11):
- Deletar `ProductGenderEnum.php`
- Remover filtro no controller
- Limpar tipos TypeScript
- Criar migration para dropar coluna `gender` da tabela `catalog_products`
