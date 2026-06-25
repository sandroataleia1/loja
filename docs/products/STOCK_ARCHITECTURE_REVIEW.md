# REVISÃO ARQUITETURAL — ESTOQUE DO MÓDULO DE PRODUTOS

**Data:** 2026-06-24
**Domínio:** ERP SaaS para Material de Construção

---

## 1. CONCLUSÃO PRINCIPAL

**A arquitetura de estoque está CORRETA para um ERP profissional.**

Não há `quantity`, `stock` ou `current_stock` nas tabelas de produtos ou variantes. O estoque reside exclusivamente em `inventory_balances` (variante × loja), com suporte a quantidades fracionadas (DECIMAL 15,3) e rastreabilidade completa via movimentações imutáveis.

---

## 2. MODELO IDENTIFICADO

### Modelo Implementado: Estoque por Variante × Loja

```
Produto (catalog_products)
└── Variante (catalog_variants)
      └── InventoryBalance (inventory_balances)
            ├── store_id          → FK para stores
            ├── variant_id        → FK para catalog_variants
            ├── quantity          → DECIMAL(15,3) — saldo atual
            └── reserved_quantity → DECIMAL(15,3) — reservado
```

**Chave única:** (store_id, variant_id) — um saldo por variante por loja.

---

## 3. ANÁLISE DOS MODELOS DE ESTOQUE

### Modelo INADEQUADO (NÃO utilizado)

```
Produto → quantidade
```

**Por que é inadequado:**
- Não suporta multi-loja
- Perde rastreabilidade de movimentações
- Impossível distinguir disponível vs. reservado
- Não suporta transferências entre lojas
- Violação de normalização: mistura definição com quantidade

### Modelo ADEQUADO (utilizado) — Estoque por Loja

```
Produto → Variante → InventoryBalance × Loja
```

**Vantagens:**
- Multi-loja nativo
- Separação entre disponível e reservado
- Saldo calculado em tempo real via `available_quantity = quantity - reserved_quantity`
- Atomic updates via raw SQL (race-condition safe)

### Modelo ALTERNATIVO (considerado) — Por Movimentações

```
Produto → Movimentações → Saldo calculado
```

**Por que não foi adotado:**
- Performance: calcular saldo por SUM de movimentos é caro
- O sistema usa HÍBRIDO: `inventory_balances` (saldo) + `inventory_movements` (histórico)
- Melhor dos dois mundos: leitura rápida + rastreabilidade completa

---

## 4. ESTRUTURA DETALHADA

### 4.1 Tabela `inventory_balances`

```sql
CREATE TABLE inventory_balances (
    uuid               UUID PRIMARY KEY,
    tenant_id          UUID NOT NULL,
    store_id           UUID NOT NULL REFERENCES stores(uuid),
    variant_id         UUID NOT NULL REFERENCES catalog_variants(uuid),
    quantity           DECIMAL(15,3) NOT NULL DEFAULT 0,
    reserved_quantity  DECIMAL(15,3) NOT NULL DEFAULT 0,
    created_at         TIMESTAMP,
    updated_at         TIMESTAMP,
    UNIQUE (store_id, variant_id)
);
```

**Campos protegidos:** `quantity` e `reserved_quantity` estão no `$guarded` do model — nunca atualizar diretamente via `update()`. Sempre usar `AdjustStockAction` ou `ReserveStockAction`.

### 4.2 Tabela `inventory_movements` (imutável)

```sql
CREATE TABLE inventory_movements (
    uuid               UUID PRIMARY KEY,
    tenant_id          UUID NOT NULL,
    store_id           UUID NOT NULL,
    variant_id         UUID NOT NULL,
    type               VARCHAR(20) NOT NULL,        -- MovementTypeEnum
    quantity           DECIMAL(15,3) NOT NULL,      -- + entrada, - saída
    quantity_before    DECIMAL(15,3) NOT NULL,       -- snapshot
    quantity_after     DECIMAL(15,3) NOT NULL,       -- snapshot
    reserved_before    DECIMAL(15,3) NOT NULL,
    reserved_after     DECIMAL(15,3) NOT NULL,
    reference_type     VARCHAR(100),                 -- 'sale', 'purchase_order', etc.
    reference_id       UUID,                         -- FK polimórfica
    created_by         UUID REFERENCES users(uuid),
    notes              TEXT,
    created_at         TIMESTAMP
    -- SEM updated_at: imutável por design
    -- SEM deleted_at: sem soft delete
);
```

**Reversão:** Nova movimentação com quantidade negativa — nunca DELETE ou UPDATE.

### 4.3 Suporte a Quantidades Fracionadas

Todos os campos de quantidade usam `DECIMAL(15,3)` conforme `DOMAIN_RULES.md`:

| Cenário | Exemplo |
|---------|---------|
| Metro quadrado de piso | 325.750 m² |
| Quilograma de cimento | 152.500 kg |
| Metro linear de cabo | 48.500 m |
| Litros de tinta | 3.600 L |

---

## 5. RISCOS E LIMITAÇÕES IDENTIFICADAS

### R1 — Sem Conversão de Unidades (CRÍTICO)

**Problema:** Produto tem `unit_of_measure = CX` (caixa), mas estoque é em `quantity` sem conversão.

**Exemplo real:**
- Piso Cerâmico 60×60 é vendido em **M²** mas estocado em **CX** (cx = 1.62m²)
- Sistema atual não tem mecanismo para converter: "vendeu 10m², descontar N caixas"

**Impacto:**
- Compras: quantidade em SC vs. KG
- Vendas: quantidade em M² vs. CX
- Estoque: inconsistência se unidade de venda ≠ unidade de estoque

**Solução planejada (Fase 9):**
```sql
CREATE TABLE unit_conversions (
    id              BIGINT PRIMARY KEY,
    tenant_id       UUID NOT NULL,
    from_unit       VARCHAR(10) NOT NULL,  -- 'CX'
    to_unit         VARCHAR(10) NOT NULL,  -- 'UN'
    factor          DECIMAL(15,6) NOT NULL, -- 12.000000
    variant_id      UUID NULLABLE,          -- conversão específica por SKU
    created_at      TIMESTAMP
);
```

### R2 — Sem Saldo Mínimo / Ponto de Reposição

**Problema:** Não há `minimum_stock`, `reorder_point` ou `maximum_stock` por variante ou loja.

**Impacto:** Sistema não pode alertar quando estoque está abaixo do mínimo.

**Recomendação para Módulo 06:** Adicionar campos de controle ao `inventory_balances` ou criar tabela `stock_parameters`.

### R3 — Transferências sem Validação de Saldo

**Status incerto:** Verificar se `StockTransferAction` valida saldo disponível antes de criar transferência.

**Risco:** Saldo negativo no estoque de origem.

---

## 6. ARQUITETURA RECOMENDADA PARA MÓDULO 06

```
inventory_balances (existente — manter)
├── + minimum_quantity DECIMAL(15,3)  → ponto de reposição
├── + maximum_quantity DECIMAL(15,3)  → estoque máximo
└── + location VARCHAR(50)            → localização no depósito (prateleira, corredor)

unit_conversions (NOVO — Fase 9)
├── from_unit    → 'CX'
├── to_unit      → 'UN'
├── factor       → 12.000000
└── variant_id   → (nullable) conversão específica por produto

stock_alerts (NOVO — Módulo 06)
├── variant_id
├── store_id
├── alert_type   → 'below_minimum' | 'out_of_stock' | 'overstock'
└── notified_at
```

---

## 7. FLUXO DE MOVIMENTAÇÃO DE ESTOQUE

```
Entrada (Compra):
  PurchaseOrder aprovada
  → AdjustStockAction.execute(+qty, 'purchase_receipt')
  → inventory_balances.quantity += qty
  → inventory_movements (imutável)

Saída (Venda):
  Sale confirmada
  → ReserveStockAction.execute(+reserved)   ← reserva no checkout
  → inventory_balances.reserved += qty
  → AdjustStockAction.execute(-qty, 'sale')  ← confirma saída
  → inventory_balances.quantity -= qty
  → inventory_balances.reserved -= qty

Transferência:
  StockTransfer criada
  → Origem: AdjustStock(-qty, 'transfer_out')
  → Destino: AdjustStock(+qty, 'transfer_in')
  → inventory_movements em ambas as lojas
```

---

## 8. CHECKLIST DE CONFORMIDADE

| Critério | Status | Observação |
|----------|--------|-----------|
| Estoque não está em catalog_products | ✅ | Campo quantity AUSENTE |
| Estoque não está em catalog_variants | ✅ | Campo quantity AUSENTE |
| Estoque em tabela separada (inventory_balances) | ✅ | Por variante × loja |
| Suporte a multi-loja | ✅ | UNIQUE(store_id, variant_id) |
| Quantidade fracionada DECIMAL(15,3) | ✅ | Suporta 325.750 m² |
| Movimentações imutáveis (auditoria) | ✅ | Sem deleted_at nem updated_at |
| Reserva de estoque (PDV) | ✅ | reserved_quantity separado |
| Transferência entre lojas | ✅ | stock_transfers implementado |
| Contagem física (inventário) | ✅ | stock_counts implementado |
| Conversão de unidades | ❌ | Não implementado — Fase 9 |
| Estoque mínimo / reposição | ❌ | Não implementado — Módulo 06 |
| Localização no depósito | ❌ | Não implementado — Módulo 06 |

---

## 9. DECISÃO FINAL

**Manter arquitetura atual.** Não implementar estoque no produto. Não quebrar tabelas existentes.

**Ações imediatas (Fase 9 deste módulo):**
- Implementar `unit_conversions` (migration + model + service)

**Ações futuras (Módulo 06):**
- Implementar alertas de estoque mínimo
- Implementar localização no depósito (endereçamento)
- Implementar relatório de curva ABC de estoque
