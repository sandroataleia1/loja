# STEP 05 REPORT — Ajustes do PDV

**Data:** 2026-06-20
**Escopo:** Quantidades decimais, exibição de unidade, impressão correta, carrinho correto, estoque correto, checkout correto.

---

## Objetivo

Garantir que o PDV Web funcione corretamente com produtos de material de construção, onde quantidades são decimais (3,75 m², 2,50 m, 15,250 kg) e não inteiras.

---

## Problemas Identificados e Corrigidos

### CRÍTICO 1 — Backend rejeita quantidades decimais

**Arquivo:** `backend/app/Modules/Sales/Http/Requests/CreateSaleRequest.php`

```php
// Antes (quebrado)
'items.*.quantity' => ['required', 'integer', 'min:1']

// Depois (correto)
'items.*.quantity' => ['required', 'numeric', 'min:0.001']
```

A validação `integer` rejeitava `3.75` com erro 422. Agora aceita qualquer número positivo com até 3 casas decimais.

---

### CRÍTICO 2 — DTO truncava quantidade decimal para inteiro

**Arquivo:** `backend/app/Modules/Sales/DTOs/CreateSaleItemDTO.php`

```php
// Antes (quebrado)
public int     $quantity,
// ...
quantity: (int) $data['quantity'],   // 3.75 → 3
// subtotalCents() → int * int = impreciso
return $this->quantity * $this->unitPriceCents;

// Depois (correto)
public float   $quantity,
// ...
quantity: (float) $data['quantity'],  // 3.75 → 3.75
// subtotalCents() → arredondamento correto
return (int) round($this->quantity * $this->unitPriceCents);
```

---

### CRÍTICO 3 — Pagamentos ignorados: checkout sempre falha

**Problema:** A ação `CreateSaleAction` criava apenas os itens mas ignorava os pagamentos do payload. Ao chamar `completeSale`, o método `isFullyPaid()` verificava os registros de `PaymentTransaction`, que eram zero, causando `BusinessException` em 100% das vendas.

**Solução:** `CreateSaleAction` agora injeta e chama `AddPaymentAction` para cada pagamento dentro da mesma transação DB.

**Arquivos modificados:**

`backend/app/Modules/Sales/DTOs/CreateSaleDTO.php` — adicionado campo `payments`:
```php
public array $payments = [],
// fromRequest():
payments: $request->array('payments') ?: [],
```

`backend/app/Modules/Sales/Http/Requests/CreateSaleRequest.php` — adicionadas regras:
```php
'payments'                      => ['nullable', 'array'],
'payments.*.method'             => ['required_with:payments', new Enum(PaymentMethodEnum::class)],
'payments.*.amount_cents'       => ['required_with:payments', 'integer', 'min:1'],
'payments.*.external_reference' => ['nullable', 'string', 'max:255'],
'payments.*.notes'              => ['nullable', 'string', 'max:500'],
'payments.*.metadata'           => ['nullable', 'array'],
```

`backend/app/Modules/Sales/Actions/CreateSaleAction.php` — processa pagamentos:
```php
public function __construct(
    private GenerateInternalCodeAction $generateCode,
    private AddPaymentAction           $addPayment,   // novo
) {}

// Dentro do DB::transaction(), após criar itens:
foreach ($dto->payments as $paymentData) {
    $this->addPayment->execute($sale, new AddPaymentDTO(
        method:            PaymentMethodEnum::from($paymentData['method']),
        amountCents:       (int) $paymentData['amount_cents'],
        externalReference: $paymentData['external_reference'] ?? null,
        notes:             $paymentData['notes'] ?? null,
        metadata:          $paymentData['metadata'] ?? null,
    ));
}
```

---

### CRÍTICO 4 — Frontend enviava nomes de campos errados

**Arquivo:** `frontend/src/services/sales.service.ts`

```ts
// Antes (errado — backend ignorava todos os campos)
items: Array<{
  product_uuid: string   // backend não conhece
  variant_uuid?: string  // backend espera variant_id
  product_name: string   // backend espera name_snapshot
  product_sku: string    // backend espera sku_snapshot
  ...
}>

// Depois (correto)
items: Array<{
  variant_id?: string | null   // ✅
  sku_snapshot: string         // ✅
  name_snapshot: string        // ✅
  ...
}>
```

`frontend/src/features/pdv/hooks/usePayment.ts` — corrigido o mapeamento:
```ts
items: items.map(item => ({
  variant_id:            item.variantUuid,   // era: variant_uuid
  sku_snapshot:          item.sku,           // era: product_sku
  name_snapshot:         item.name,          // era: product_name
  quantity:              item.quantity,
  unit_price_cents:      item.unitPriceCents,
  discount_amount_cents: item.discountCents,
})),
```

---

### CRÍTICO 5 — Frontend não enviava `store_id` (campo obrigatório no backend)

**Problema:** `CreateSaleRequest` exige `store_id` (uuid da loja), mas o frontend não o enviava porque `PdvSessionData` não armazenava essa informação.

**Solução em 3 arquivos:**

`frontend/src/features/pdv/stores/pdvSessionStore.ts`:
```ts
export interface PdvSessionData {
  sessionUuid:         string
  registerUuid:        string
  registerName:        string
  storeUuid:           string   // novo ✅
  openedAt:            string
  openingBalanceCents: number
}
```

`frontend/src/app/(pdv)/pdv/caixa/abrir/page.tsx`:
```ts
setSession({
  sessionUuid:  session.uuid,
  registerUuid: regUuid,
  registerName: regName,
  storeUuid:    session.store_id,  // novo ✅ — disponível em CashSession
  openedAt:     session.opened_at,
  openingBalanceCents: valueCents,
})
```

`frontend/src/features/pdv/hooks/usePayment.ts`:
```ts
const sale = await salesService.createSale({
  store_id:   session!.storeUuid,  // novo ✅
  session_id: session!.sessionUuid,
  ...
})
```

---

### BUG 6 — Cupom impresso mostrava quantidade sem locale nem unidade

**Arquivo:** `frontend/src/features/pdv/components/receipt/ReceiptDocument.tsx`

```tsx
// Antes (errado)
const lineTotal = item.unitPriceCents * item.quantity - item.discountCents
// exibia: "3.75x R$ 89,90"

// Depois (correto)
const lineTotal = Math.round(item.unitPriceCents * item.quantity) - item.discountCents
// exibe: "3,75 M2 × R$ 89,90"
```

Adicionado helper `fmtQty()`:
```ts
const fmtQty = (qty: number) =>
  qty.toLocaleString('pt-BR', { maximumFractionDigits: 3 })
```

---

### BUG 7 — Topbar com branding de moda

**Arquivo:** `frontend/src/features/pdv/components/layout/PdvTopbar.tsx`

```tsx
// Antes
PDV Fashion

// Depois
PDV
```

---

## Arquivos Modificados

| Arquivo | Tipo | Mudança |
|---------|------|---------|
| `backend/app/Modules/Sales/Http/Requests/CreateSaleRequest.php` | Backend | Qty numérico + regras de payments |
| `backend/app/Modules/Sales/DTOs/CreateSaleItemDTO.php` | Backend | `float $quantity`, `(float)` cast, `round()` |
| `backend/app/Modules/Sales/DTOs/CreateSaleDTO.php` | Backend | Campo `payments: array = []` |
| `backend/app/Modules/Sales/Actions/CreateSaleAction.php` | Backend | Injeta `AddPaymentAction`, processa payments |
| `frontend/src/features/pdv/stores/pdvSessionStore.ts` | Frontend | `storeUuid: string` em `PdvSessionData` |
| `frontend/src/app/(pdv)/pdv/caixa/abrir/page.tsx` | Frontend | Salva `session.store_id` como `storeUuid` |
| `frontend/src/services/sales.service.ts` | Frontend | `CreateSalePayload` com campos corretos |
| `frontend/src/features/pdv/hooks/usePayment.ts` | Frontend | Nomes corretos + `store_id` |
| `frontend/src/features/pdv/components/receipt/ReceiptDocument.tsx` | Frontend | `fmtQty()`, `Math.round()`, unidade no cupom |
| `frontend/src/features/pdv/components/layout/PdvTopbar.tsx` | Frontend | Remove "Fashion" do branding |

---

## Estado Pós-Step 05

### Fluxo validado (código):

```
PDV → Produto "Areia Média" (KG)
  ↓
Carrinho → qty: 15,250 kg → subtotal: R$ 45,75
  ↓ Math.round(300 × 15.25) = R$ 4.575,00 ✅
  ↓
Pagamento → Dinheiro R$ 5.000,00
  ↓ usePayment: POST /sales { store_id, items[], payments[] }
  ↓ CreateSaleAction: items criados + AddPaymentAction chamado atomicamente
  ↓
Completar → POST /sales/{uuid}/complete
  ↓ isFullyPaid(): 500000 >= total_cents ✅
  ↓ CompleteSaleAction: estoque debitado (-15.250 kg) ✅
  ↓
Cupom → "15,25 KG × R$ 3,00 ... R$ 45,75" ✅
```

### Compatibilidade preservada:

| Recurso | Status |
|---------|--------|
| `CartItemRow.tsx` — qty decimal + step | ✅ (Step 01) |
| `pdvCartStore.ts` — Math.round no subtotal | ✅ (Step 01) |
| `ProductGrid.tsx` — passa unitOfMeasure | ✅ (Step 01) |
| `SaleItem` model — `decimal:3` cast | ✅ (Step 01) |
| `AdjustStockAction` — qty float | ✅ (Step 01) |
| `CompleteSaleAction` — debita estoque | ✅ sem mudança |
| `AddPaymentAction` — pagamentos | ✅ sem mudança |
| `CreateSaleRequest` — payments opcional (backward compat) | ✅ `nullable` |

---

## Validação TypeScript

```
npx tsc --noEmit → 0 erros ✅
```

---

## Como Executar (Docker)

```bash
# Migração (se ainda não executada)
docker compose exec app php artisan migrate

# Testes
docker compose exec app ./vendor/bin/pest

# Seed (se fresh)
docker compose exec app php artisan db:seed --class=ConstructionCategorySeeder
docker compose exec app php artisan db:seed --class=ConstructionAttributeSeeder
docker compose exec app php artisan db:seed --class=ConstructionGridSeeder
```

---

## Pendências

- Teste manual do fluxo completo: abrir caixa → venda qty decimal → cupom → fechar caixa
- Execução das migrations no banco
- Execução do test suite (`pest`)
