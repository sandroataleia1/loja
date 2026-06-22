# PAYMENT_ARCHITECTURE

Módulo de Formas de Pagamento e Condições de Pagamento — ERP SaaS  
Data: 2026-06-22  
Status: Proposta de Arquitetura (sem implementação)

---

## 1. Contexto e Objetivo

O sistema atual possui um módulo de pagamentos funcional para PDV, com `payment_methods` e `payment_conditions` como catálogos simples (apenas nome + flag ativo). As entidades existem, mas sem as regras financeiras necessárias para um ERP profissional.

**Objetivo:** Enriquecer as entidades existentes para suportar:

- Desconto percentual e fixo por condição
- Juros percentual e fixo por condição
- Multa e carência
- Geração automática de parcelas com vencimentos calculados
- Reutilização em PDV, Orçamentos e Pedidos

---

## 2. Diagnóstico do Sistema Atual

### 2.1 O que existe

| Entidade | Tabela | Estado Atual |
|---|---|---|
| PaymentMethod | `payment_methods` | Catálogo simples: name, type, is_active, sort_order |
| PaymentCondition | `payment_conditions` | Catálogo simples: name, is_active, sort_order |
| PaymentTransaction | `payment_transactions` | Transação do PDV com enum hardcoded |
| FinancialEntry | `financial_entries` | Lançamento contábil com parcelas |
| FinancialInstallment | `financial_installments` | Parcelas por lançamento |
| Order | `orders` | payment_terms como texto livre, sem FK |
| Quote | `quotes` | payment_terms como texto livre, sem FK |

### 2.2 Lacunas críticas identificadas

1. **`payment_conditions`** não possui regras financeiras (desconto, juros, multa, carência, parcelamento)
2. **`orders`** e **`quotes`** armazenam condição de pagamento como texto livre — sem FK para o catálogo
3. **`payment_transactions`** usa `PaymentMethodEnum` hardcoded em PHP, desacoplado do catálogo `payment_methods`
4. Não existe geração automática de `financial_installments` baseada em regras de `payment_conditions`
5. `PaymentMethodEnum` não possui: `boleto`, `bank_transfer`, `convention` (convênio)
6. Nenhuma condição do seeder possui regras de parcelamento, apenas nomes

### 2.3 O que está funcionando e deve ser preservado

- Fluxo completo do PDV: `Sale → PaymentTransaction → SaleCompleted → FinancialEntry`
- Integração PIX com Asaas: `PixCharge → webhook → status update`
- `financial_entries` + `financial_installments` para controle de contas a receber/pagar
- CRUD de `payment_methods` e `payment_conditions` via API e frontend

---

## 3. Modelagem Recomendada

### Princípio central

**Forma de pagamento** = canal/meio (como o cliente paga).  
**Condição de pagamento** = regras financeiras e cronograma (quando e quanto em cada parcela).

São independentes. Um pedido de R$ 1.000 pode ser:
- Crédito + 30/60/90 (forma: crédito, condição: 3 parcelas mensais)
- PIX + À vista com 5% de desconto (forma: pix, condição: à vista com desconto)
- Crediário + Entrada + 30/60 (forma: crediário, condição: entrada + 2 parcelas)

### Camadas do módulo

```
┌─────────────────────────────────────────────────────┐
│                  CATÁLOGO (configuração)              │
│  payment_methods  ←→  payment_conditions              │
│  (como paga)           (quando e quanto paga)         │
└──────────────┬──────────────────┬────────────────────┘
               │                  │
     ┌─────────▼────────┐  ┌──────▼──────────────┐
     │    PDV / Venda   │  │  Orçamento / Pedido  │
     │  PaymentTrans.   │  │  Orders / Quotes     │
     └─────────┬────────┘  └──────┬───────────────┘
               │                  │
     ┌─────────▼──────────────────▼───────────────┐
     │              FINANCEIRO                      │
     │  FinancialEntry → FinancialInstallment       │
     └─────────────────────────────────────────────┘
```

---

## 4. Entidades e Tabelas

### 4.1 `payment_methods` — Enriquecida

Tabela existente. Adicionar campos de configuração por tipo de forma.

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | uuid PK | Existente |
| `tenant_id` | uuid FK nullable | Existente (null = sistema) |
| `name` | varchar(100) | Existente |
| `type` | enum | Existente — ampliar casos |
| `is_active` | bool | Existente |
| `is_system` | bool | Existente |
| `sort_order` | int | Existente |
| `accepts_change` | bool default false | **NOVO** — permite troco (ex: dinheiro) |
| `max_installments` | int default 1 | **NOVO** — limite de parcelas (crédito) |
| `min_installment_value_cents` | int default 0 | **NOVO** — valor mínimo por parcela |
| `requires_authorization` | bool default false | **NOVO** — exige código de autorização |
| `created_at` / `updated_at` | timestamp | Existente |
| `deleted_at` | timestamp nullable | Existente |

**Enum `type` ampliado:**

| Valor | Label | Novo? |
|---|---|---|
| `cash` | Dinheiro | Existente |
| `pix` | PIX | Existente |
| `debit` | Débito | Existente (`debit_card`) — renomear |
| `credit` | Crédito | Existente (`credit_card`) — renomear |
| `boleto` | Boleto | Existente (seeder) — **faltando no Enum PHP** |
| `bank_transfer` | Transferência | Existente (seeder) — **faltando no Enum PHP** |
| `store_credit` | Crediário | Existente |
| `convention` | Convênio | **NOVO** |
| `voucher` | Voucher | Existente |
| `check` | Cheque | Existente (seeder) — **faltando no Enum PHP** |

---

### 4.2 `payment_conditions` — Enriquecida

Tabela existente. Adicionar todas as regras financeiras.

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | uuid PK | Existente |
| `tenant_id` | uuid FK nullable | Existente (null = sistema) |
| `name` | varchar(100) | Existente |
| `type` | enum | **NOVO** — `a_vista`, `parcelado`, `entrada_parcelas`, `variavel` |
| **DESCONTO** | | |
| `discount_type` | enum | **NOVO** — `none`, `percent`, `fixed` |
| `discount_value` | decimal(10,4) default 0 | **NOVO** — valor do desconto |
| **JUROS** | | |
| `interest_type` | enum | **NOVO** — `none`, `percent_month`, `percent_total`, `fixed_per_installment`, `fixed_total` |
| `interest_value` | decimal(10,4) default 0 | **NOVO** — valor dos juros |
| **MULTA** | | |
| `fine_percent` | decimal(10,4) default 0 | **NOVO** — % de multa por atraso |
| `fine_after_days` | int default 0 | **NOVO** — dias de carência antes da multa |
| **CARÊNCIA** | | |
| `grace_days` | int default 0 | **NOVO** — dias de carência geral |
| **PARCELAMENTO** | | |
| `installment_count` | int default 1 | **NOVO** — número de parcelas (0 = variável) |
| `first_due_days` | int default 0 | **NOVO** — dias até o primeiro vencimento |
| `interval_days` | int default 30 | **NOVO** — intervalo entre parcelas |
| `has_entry` | bool default false | **NOVO** — possui entrada no ato |
| `entry_percent` | decimal(10,4) default 0 | **NOVO** — % do total como entrada |
| `is_variable` | bool default false | **NOVO** — parcelas definidas no momento da venda |
| **CONTROLE** | | |
| `is_active` | bool | Existente |
| `is_system` | bool | Existente |
| `sort_order` | int | Existente |
| `created_at` / `updated_at` | timestamp | Existente |
| `deleted_at` | timestamp nullable | Existente |

**Exemplos de preenchimento:**

| Condição | type | installment_count | first_due_days | interval_days | has_entry | discount_type | discount_value |
|---|---|---|---|---|---|---|---|
| À vista | `a_vista` | 1 | 0 | 0 | false | none | 0 |
| À vista PIX 5% desc | `a_vista` | 1 | 0 | 0 | false | percent | 5.0000 |
| 30 dias | `parcelado` | 1 | 30 | 0 | false | none | 0 |
| 30/60 | `parcelado` | 2 | 30 | 30 | false | none | 0 |
| 30/60/90 | `parcelado` | 3 | 30 | 30 | false | none | 0 |
| 30/60/90/120 | `parcelado` | 4 | 30 | 30 | false | none | 0 |
| Entrada + 30/60 | `entrada_parcelas` | 3 | 0 | 30 | true | none | 0 |
| Parcelamento variável | `variavel` | 0 | 30 | 30 | false | none | 0 |

**Enum `type`:**

| Valor | Descrição |
|---|---|
| `a_vista` | Pagamento único, vence imediatamente ou em X dias |
| `parcelado` | N parcelas iguais com intervalo fixo |
| `entrada_parcelas` | Entrada no ato + N-1 parcelas periódicas |
| `variavel` | Número de parcelas definido no momento da venda |

---

### 4.3 `payment_transactions` — Enriquecida

Tabela existente. Vincular ao catálogo e adicionar dados de parcelamento.

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | uuid PK | Existente |
| `tenant_id` | uuid FK | Existente |
| `sale_id` | uuid FK | Existente |
| `payment_method_id` | uuid FK nullable | **NOVO** — FK para catálogo payment_methods |
| `payment_condition_id` | uuid FK nullable | **NOVO** — FK para catálogo payment_conditions |
| `method` | enum | Existente — manter para retrocompatibilidade |
| `amount_cents` | int | Existente |
| `discount_cents` | int default 0 | **NOVO** — desconto aplicado nesta transação |
| `interest_cents` | int default 0 | **NOVO** — juros aplicados |
| `fine_cents` | int default 0 | **NOVO** — multa aplicada |
| `installment_number` | int default 1 | **NOVO** — número da parcela atual |
| `total_installments` | int default 1 | **NOVO** — total de parcelas |
| `due_date` | date nullable | **NOVO** — data de vencimento |
| `status` | enum | Existente |
| `external_reference` | varchar nullable | Existente |
| `notes` | text nullable | Existente |
| `metadata` | jsonb nullable | Existente |
| `paid_at` | timestamp nullable | Existente |
| `created_at` / `updated_at` | timestamp | Existente |
| `deleted_at` | timestamp nullable | Existente |

---

### 4.4 `orders` — Enriquecida

Substituir `payment_terms` (texto) por FKs para o catálogo.

| Coluna | Tipo | Descrição |
|---|---|---|
| ... | ... | Colunas existentes |
| `payment_method_id` | uuid FK nullable | **NOVO** — FK para payment_methods |
| `payment_condition_id` | uuid FK nullable | **NOVO** — FK para payment_conditions |
| `payment_terms` | varchar(200) | Existente — **deprecar** gradualmente |
| `discount_type` | enum | Existente |
| `discount_cents` | int | Existente |

---

### 4.5 `quotes` — Enriquecida

Mesma alteração que `orders`.

| Coluna | Tipo | Descrição |
|---|---|---|
| ... | ... | Colunas existentes |
| `payment_method_id` | uuid FK nullable | **NOVO** — FK para payment_methods |
| `payment_condition_id` | uuid FK nullable | **NOVO** — FK para payment_conditions |
| `payment_terms` | varchar(200) | Existente — **deprecar** gradualmente |

---

### 4.6 `sales` — Enriquecida

| Coluna | Tipo | Descrição |
|---|---|---|
| ... | ... | Colunas existentes |
| `payment_method_id` | uuid FK nullable | **NOVO** |
| `payment_condition_id` | uuid FK nullable | **NOVO** |

---

## 5. Relacionamentos

```
payment_methods ──┬── payment_transactions (payment_method_id)
                  ├── orders (payment_method_id)
                  ├── quotes (payment_method_id)
                  └── sales (payment_method_id)

payment_conditions ──┬── payment_transactions (payment_condition_id)
                     ├── orders (payment_condition_id)
                     ├── quotes (payment_condition_id)
                     └── sales (payment_condition_id)

sales ──────────────── payment_transactions (sale_id, 1:N)
                   └── financial_entries via SaleCompleted event (polymorphic)

financial_entries ──── financial_installments (financial_entry_id, 1:N)

tenant_payment_gateways ── pix_charges (tenant_id, 1:N)
pix_charges ──────────── sales (sale_id, nullable)
```

---

## 6. Fluxos

### 6.1 Configuração (back-office)

```
Administrador
  → Acessa /settings/payment-methods
  → Cria/edita forma de pagamento com tipo e configurações
  
  → Acessa /settings/payment-conditions
  → Cria/edita condição com regras financeiras e parcelamento
  → Visualiza preview de parcelas para um valor simulado
```

### 6.2 Geração de Parcelas (cálculo automático)

```
Entrada:
  total_cents = 300000 (R$ 3.000,00)
  payment_condition: tipo=parcelado, installment_count=3,
                     first_due_days=30, interval_days=30,
                     interest_type=percent_total, interest_value=2.0

Algoritmo:
  1. Aplicar desconto: total_com_desconto = total * (1 - discount%)
  2. Aplicar juros: total_com_juros = total_com_desconto * (1 + interest%)
  3. Dividir em N parcelas iguais (centavos: distribuir resto na última)
  4. Calcular vencimentos:
     parcela[1].vencimento = data_venda + first_due_days
     parcela[N].vencimento = data_venda + first_due_days + (N-1) * interval_days
  5. Se has_entry=true:
     parcela[1].vencimento = data_venda (entrada no ato)
     parcela[2..N].vencimento = data_venda + (N-1) * interval_days

Saída:
  [
    { numero: 1, vencimento: "2026-07-22", valor_cents: 102000 },
    { numero: 2, vencimento: "2026-08-22", valor_cents: 102000 },
    { numero: 3, vencimento: "2026-09-22", valor_cents: 102000 },
  ]
```

### 6.3 Fluxo PDV (Venda no Balcão)

```
Caixa seleciona produtos
  → Seleciona forma de pagamento (payment_method)
  → Seleciona condição de pagamento (payment_condition)
  → Sistema calcula parcelas automaticamente
  → Caixa confirma valores
  → CreateSaleAction:
      - Cria Sale com payment_method_id + payment_condition_id
      - Cria PaymentTransaction(s) com installment_number, due_date
  → SaleCompleted event:
      - CreateFinancialEntryFromSaleAction:
          - Cria FinancialEntry por forma de pagamento
          - Cria FinancialInstallment por parcela com due_date calculado
```

### 6.4 Fluxo Orçamento → Pedido → Faturamento

```
Vendedor
  → Cria Orçamento (Quote) com payment_method_id + payment_condition_id
  → Cliente aprova
  → Converte para Pedido (Order), herda payment_method/condition
  → Faturamento gera FinancialEntry:
      - Regras de payment_condition aplicadas
      - FinancialInstallment gerado por parcela com vencimentos
  → Cobrança:
      - Boleto: gera boleto por parcela
      - PIX: gera cobrança por parcela
      - Crediário: controle interno de vencimentos
```

### 6.5 Fluxo de Multa e Carência

```
FinancialInstallment.due_date = 2026-07-22
grace_days = 3
fine_percent = 2%
interest_type = percent_month, interest_value = 1%

Data de pagamento = 2026-07-28 (6 dias de atraso, 3 dias após carência)

Dias de atraso efetivo = max(0, dias_atraso - grace_days) = 3 dias
Multa = amount_cents * fine_percent = amount_cents * 0.02
Juros = amount_cents * (interest_value/30) * dias_atraso_efetivo
Valor total = amount_cents + multa + juros
```

---

## 7. Migrations Necessárias

### Migration 1 — `alter_payment_methods_add_config`

```
// Tabela: payment_methods
// Adicionar:

$table->boolean('accepts_change')->default(false)->after('sort_order');
$table->unsignedInteger('max_installments')->default(1)->after('accepts_change');
$table->unsignedInteger('min_installment_value_cents')->default(0)->after('max_installments');
$table->boolean('requires_authorization')->default(false)->after('min_installment_value_cents');

// Atualizar enum type (depende do banco):
// MySQL: MODIFY COLUMN type ENUM('cash','pix','debit','credit','boleto','bank_transfer','store_credit','convention','voucher','check')
```

**Dados do seeder a atualizar:**

| method | accepts_change | max_installments | requires_authorization |
|---|---|---|---|
| cash | true | 1 | false |
| pix | false | 1 | false |
| credit | false | 12 | true |
| debit | false | 1 | true |
| boleto | false | 1 | false |
| bank_transfer | false | 1 | false |
| store_credit | false | 12 | false |
| convention | false | 24 | false |

---

### Migration 2 — `alter_payment_conditions_add_financial_rules`

```
// Tabela: payment_conditions
// Adicionar após name:

$table->enum('type', ['a_vista','parcelado','entrada_parcelas','variavel'])
      ->default('a_vista')->after('name');
$table->enum('discount_type', ['none','percent','fixed'])->default('none')->after('type');
$table->decimal('discount_value', 10, 4)->default(0)->after('discount_type');
$table->enum('interest_type', ['none','percent_month','percent_total','fixed_per_installment','fixed_total'])
      ->default('none')->after('discount_value');
$table->decimal('interest_value', 10, 4)->default(0)->after('interest_type');
$table->decimal('fine_percent', 10, 4)->default(0)->after('interest_value');
$table->unsignedInteger('fine_after_days')->default(0)->after('fine_percent');
$table->unsignedInteger('grace_days')->default(0)->after('fine_after_days');
$table->unsignedInteger('installment_count')->default(1)->after('grace_days');
$table->unsignedInteger('first_due_days')->default(0)->after('installment_count');
$table->unsignedInteger('interval_days')->default(30)->after('first_due_days');
$table->boolean('has_entry')->default(false)->after('interval_days');
$table->decimal('entry_percent', 10, 4)->default(0)->after('has_entry');
$table->boolean('is_variable')->default(false)->after('entry_percent');
```

---

### Migration 3 — `alter_payment_transactions_add_installment_fields`

```
// Tabela: payment_transactions
// Adicionar:

$table->foreignUuid('payment_method_id')
      ->nullable()
      ->after('sale_id')
      ->constrained('payment_methods')
      ->nullOnDelete();

$table->foreignUuid('payment_condition_id')
      ->nullable()
      ->after('payment_method_id')
      ->constrained('payment_conditions')
      ->nullOnDelete();

$table->unsignedInteger('discount_cents')->default(0)->after('amount_cents');
$table->unsignedInteger('interest_cents')->default(0)->after('discount_cents');
$table->unsignedInteger('fine_cents')->default(0)->after('interest_cents');
$table->unsignedSmallInteger('installment_number')->default(1)->after('fine_cents');
$table->unsignedSmallInteger('total_installments')->default(1)->after('installment_number');
$table->date('due_date')->nullable()->after('total_installments');
```

---

### Migration 4 — `alter_orders_add_payment_references`

```
// Tabela: orders
// Adicionar:

$table->foreignUuid('payment_method_id')
      ->nullable()
      ->after('customer_id')
      ->constrained('payment_methods')
      ->nullOnDelete();

$table->foreignUuid('payment_condition_id')
      ->nullable()
      ->after('payment_method_id')
      ->constrained('payment_conditions')
      ->nullOnDelete();

// Manter payment_terms para retrocompatibilidade — não remover nesta migration
```

---

### Migration 5 — `alter_quotes_add_payment_references`

```
// Tabela: quotes
// Adicionar (mesmo padrão que orders):

$table->foreignUuid('payment_method_id')
      ->nullable()
      ->after('customer_id')
      ->constrained('payment_methods')
      ->nullOnDelete();

$table->foreignUuid('payment_condition_id')
      ->nullable()
      ->after('payment_method_id')
      ->constrained('payment_conditions')
      ->nullOnDelete();
```

---

### Migration 6 — `alter_sales_add_payment_references`

```
// Tabela: sales
// Adicionar:

$table->foreignUuid('payment_method_id')
      ->nullable()
      ->after('customer_id')
      ->constrained('payment_methods')
      ->nullOnDelete();

$table->foreignUuid('payment_condition_id')
      ->nullable()
      ->after('payment_method_id')
      ->constrained('payment_conditions')
      ->nullOnDelete();
```

---

### Migration 7 — Atualizar dados do PaymentSeeder

Não é uma migration DDL, mas o `PaymentSeeder` deve ser atualizado para popular os novos campos em todas as condições do sistema.

---

## 8. APIs Necessárias

### 8.1 Enriquecimento das APIs existentes

#### GET /payment-methods

```jsonc
// Resposta atual: [{ uuid, name, type, is_active, is_system, sort_order }]
// Resposta enriquecida:
[{
  "uuid": "...",
  "name": "Cartão de Crédito",
  "type": "credit",
  "accepts_change": false,
  "max_installments": 12,
  "min_installment_value_cents": 1000,
  "requires_authorization": true,
  "is_active": true,
  "is_system": true,
  "sort_order": 4
}]
```

#### GET /payment-conditions

```jsonc
// Resposta enriquecida:
[{
  "uuid": "...",
  "name": "30/60/90",
  "type": "parcelado",
  "discount_type": "none",
  "discount_value": 0,
  "interest_type": "none",
  "interest_value": 0,
  "fine_percent": 2,
  "fine_after_days": 0,
  "grace_days": 3,
  "installment_count": 3,
  "first_due_days": 30,
  "interval_days": 30,
  "has_entry": false,
  "entry_percent": 0,
  "is_variable": false,
  "is_active": true,
  "is_system": true,
  "sort_order": 9
}]
```

#### POST /payment-conditions (criar condição com regras)

```jsonc
// Request body:
{
  "name": "Entrada + 60/90",
  "type": "entrada_parcelas",
  "discount_type": "percent",
  "discount_value": 0,
  "interest_type": "none",
  "interest_value": 0,
  "fine_percent": 2.0,
  "fine_after_days": 3,
  "grace_days": 0,
  "installment_count": 3,
  "first_due_days": 0,
  "interval_days": 30,
  "has_entry": true,
  "entry_percent": 33.33
}
```

#### PUT /payment-methods/{uuid} e PUT /payment-conditions/{uuid}

Aceitar todos os novos campos nas requisições de atualização.

---

### 8.2 Nova API — Calcular Parcelas

#### POST /payment-conditions/{uuid}/calculate

Calcula e retorna o cronograma de parcelas para um valor dado. Usado para preview no frontend.

**Request:**
```jsonc
{
  "amount_cents": 300000,
  "sale_date": "2026-06-22"
}
```

**Response:**
```jsonc
{
  "original_amount_cents": 300000,
  "discount_cents": 0,
  "interest_cents": 6000,
  "total_amount_cents": 306000,
  "installments": [
    { "number": 1, "due_date": "2026-07-22", "amount_cents": 102000 },
    { "number": 2, "due_date": "2026-08-22", "amount_cents": 102000 },
    { "number": 3, "due_date": "2026-09-22", "amount_cents": 102000 }
  ]
}
```

Esta API deve ser chamada:
- Ao selecionar uma condição no formulário de orçamento/pedido
- Ao selecionar uma condição no PDV
- Na tela de configuração para preview

---

### 8.3 Impacto nas APIs de Order e Quote

#### POST /orders e PUT /orders/{uuid}

Adicionar ao request body:
```jsonc
{
  "payment_method_id": "uuid-da-forma",
  "payment_condition_id": "uuid-da-condicao"
}
```

Manter `payment_terms` como campo opcional por retrocompatibilidade.

#### POST /quotes e PUT /quotes/{uuid}

Mesma mudança.

---

## 9. Impactos no Sistema Atual

### 9.1 Backend

| Arquivo | Impacto | Nível |
|---|---|---|
| `PaymentMethodEnum.php` | Adicionar casos: `boleto`, `bank_transfer`, `convention`, `check`. Atualizar `label()` e `isInstant()` | Médio |
| `PaymentConditionController.php` | Aceitar e validar novos campos no store/update | Médio |
| `PaymentMethodController.php` | Aceitar e validar novos campos no store/update | Baixo |
| `PaymentCondition.php` | Adicionar novos campos ao `$fillable` e `$casts` | Baixo |
| `PaymentMethod.php` | Adicionar novos campos ao `$fillable` e `$casts` | Baixo |
| `CreateSaleAction.php` | Aceitar `payment_method_id` e `payment_condition_id`, passar para PaymentTransaction | Médio |
| `AddPaymentAction.php` | Persistir `payment_method_id`, `condition_id`, `installment_number`, `total_installments`, `due_date` | Médio |
| `CreateFinancialEntryFromSaleAction.php` | Usar `payment_condition_id` para gerar `FinancialInstallment` com vencimentos corretos | Alto |
| `PaymentSeeder.php` | Popular novos campos em todas as condições e formas do sistema | Baixo |
| `Order.php` | Adicionar relacionamentos `belongsTo(PaymentMethod)` e `belongsTo(PaymentCondition)` | Baixo |
| `Quote.php` | Mesmos relacionamentos | Baixo |
| `OrderController.php` | Aceitar `payment_method_id` e `payment_condition_id` no store/update | Médio |

**Novo serviço a criar:**

`InstallmentCalculatorService` — responsável por calcular cronograma de parcelas a partir de uma `PaymentCondition` + valor + data. Centraliza a lógica de cálculo, usada pelo novo endpoint `/calculate` e por `CreateFinancialEntryFromSaleAction`.

---

### 9.2 Frontend

| Arquivo/Página | Impacto | Nível |
|---|---|---|
| `settings/payment-conditions/page.tsx` | Formulário com todos os novos campos + preview de parcelas | Alto |
| `settings/payment-methods/page.tsx` | Adicionar campos: accepts_change, max_installments, min_installment_value_cents, requires_authorization | Médio |
| `payment.service.ts` | Atualizar tipos TypeScript com novos campos; adicionar `calculateInstallments()` | Médio |
| PDV `PaymentModal.tsx` | Adicionar seleção de condição de pagamento; mostrar preview de parcelas | Alto |
| PDV `CardPaymentForm.tsx` | Usar `max_installments` do payment_method selecionado | Baixo |
| Orçamento (Quote form) | Substituir campo texto `payment_terms` por selects de forma + condição | Alto |
| Pedido (Order form) | Mesma substituição | Alto |
| `usePayment.ts` | Incluir `payment_method_id`, `payment_condition_id` no payload | Médio |

---

### 9.3 Banco de Dados

| Tabela | Operação | Risco |
|---|---|---|
| `payment_methods` | ALTER TABLE — adicionar colunas com DEFAULT | Baixo (colunas anuláveis/com default) |
| `payment_conditions` | ALTER TABLE — adicionar colunas com DEFAULT | Baixo |
| `payment_transactions` | ALTER TABLE — adicionar FKs nullable e colunas | Baixo |
| `orders` | ALTER TABLE — adicionar FKs nullable | Baixo |
| `quotes` | ALTER TABLE — adicionar FKs nullable | Baixo |
| `sales` | ALTER TABLE — adicionar FKs nullable | Baixo |

Todas as novas colunas são nullable ou possuem DEFAULT, portanto não há risco de quebra de dados existentes.

---

## 10. Riscos e Mitigações

### Risco 1 — Desalinhamento entre PaymentMethodEnum e catálogo

**Problema:** `PaymentTransaction.method` usa `PaymentMethodEnum` hardcoded (Cash, Pix, CreditCard, DebitCard, StoreCredit, Voucher). O catálogo `payment_methods` tem mais tipos. Se um tenant criar uma forma customizada, não existe correspondência no enum.

**Mitigação:** 
- Curto prazo: adicionar casos faltantes ao enum (boleto, bank_transfer, check, convention)
- Médio prazo: migrar `PaymentTransaction.method` para usar `payment_method_id` FK em vez do enum
- O campo `method` deve ser mantido como fallback durante a transição

---

### Risco 2 — Cálculos financeiros com imprecisão decimal

**Problema:** PHP nativo pode ter imprecisão em operações com ponto flutuante para cálculos de juros compostos e distribuição de centavos.

**Mitigação:**
- Usar `bcmath` (extensão PHP) ou a biblioteca `brick/money` para todos os cálculos
- Arredondar centavos: distribuir o resto (centavos residuais) na última parcela
- Testar casos-limite: valores ímpares, percentuais fracionados, parcelas com distribuição desigual

---

### Risco 3 — Migração de dados legados em orders/quotes

**Problema:** Registros existentes em `orders` e `quotes` têm `payment_terms` como texto livre (ex: "30/60 dias"). Não é possível migrar automaticamente para `payment_condition_id` sem correspondência manual.

**Mitigação:**
- Manter `payment_terms` como coluna deprecated (não remover)
- Novos registros usam FKs; registros antigos mantêm texto
- Frontend exibe texto legado quando `payment_condition_id` é null
- Não criar migration de dados — deixar registros antigos como estão

---

### Risco 4 — Geração automática de parcelas no PDV vs. Faturamento

**Problema:** O PDV atual cria `PaymentTransaction` único por forma de pagamento (sem parcelas). Adicionar lógica de parcelamento ao PDV muda o comportamento atual.

**Mitigação:**
- Parcelamento no PDV deve ser opt-in por forma de pagamento (crédito)
- Para condições à vista, o comportamento atual é preservado (1 transação)
- Para condições parceladas no PDV, criar múltiplas `PaymentTransaction` com installment_number

---

### Risco 5 — Juros sobre parcelamento no PDV vs. Faturamento

**Problema:** No PDV (à vista), aplicar desconto é simples. No faturamento (30/60/90), juros e multa têm semânticas diferentes.

**Mitigação:**
- Desconto: aplicado imediatamente no total da venda/pedido
- Juros de parcelamento: embutidos no valor das parcelas no momento da geração
- Juros de atraso (fine_percent): calculados somente no ato do recebimento, não na geração
- `InstallmentCalculatorService` gera parcelas COM juros de parcelamento embutidos, SEM juros de atraso

---

### Risco 6 — Condições do sistema (is_system=true)

**Problema:** Condições do sistema não podem ser alteradas por tenants. Se o seeder atualizar com novos campos, isso pode conflitar com tenants que clonaram condições.

**Mitigação:**
- Atualizar PaymentSeeder para popular todos os novos campos nas condições do sistema
- Condições `is_system=true` do tenant são somente leitura (já implementado)
- Tenants podem criar condições próprias copiando as do sistema como base

---

## 11. Ordem de Implementação Recomendada

```
Fase 1 — Banco e modelos (sem quebrar nada)
  1.1. Migration: alter payment_methods (adicionar colunas opcionais)
  1.2. Migration: alter payment_conditions (adicionar colunas opcionais)
  1.3. Atualizar PaymentMethod.php e PaymentCondition.php ($fillable, $casts)
  1.4. Atualizar PaymentSeeder com novos campos em todas as condições

Fase 2 — Lógica de cálculo (novo serviço)
  2.1. Criar InstallmentCalculatorService
  2.2. Criar endpoint POST /payment-conditions/{uuid}/calculate
  2.3. Testar cálculos com todos os tipos de condição

Fase 3 — APIs enriquecidas
  3.1. Atualizar PaymentConditionController: validação e persistência dos novos campos
  3.2. Atualizar PaymentMethodController: validação e persistência dos novos campos
  3.3. Adicionar PaymentMethodEnum: boleto, bank_transfer, convention, check

Fase 4 — Vincular Orders e Quotes
  4.1. Migration: alter orders (payment_method_id, payment_condition_id)
  4.2. Migration: alter quotes (mesmos campos)
  4.3. Atualizar Order.php, Quote.php (relacionamentos)
  4.4. Atualizar OrderController, QuoteController (aceitar e persistir FKs)

Fase 5 — Vincular Sales e Transactions
  5.1. Migration: alter payment_transactions (payment_method_id, condition_id, installment fields)
  5.2. Migration: alter sales (payment_method_id, payment_condition_id)
  5.3. Atualizar CreateSaleAction e AddPaymentAction
  5.4. Atualizar CreateFinancialEntryFromSaleAction para gerar parcelas com vencimentos

Fase 6 — Frontend Settings
  6.1. Atualizar page.tsx de payment-conditions: novos campos + preview de parcelas
  6.2. Atualizar page.tsx de payment-methods: novos campos
  6.3. Atualizar payment.service.ts com tipos TypeScript completos

Fase 7 — Frontend Operacional
  7.1. Atualizar formulários de Orçamento e Pedido: selects forma + condição
  7.2. Atualizar PDV PaymentModal: selecionar condição + visualizar parcelas
  7.3. Atualizar usePayment.ts com novos campos no payload
```

---

## 12. Glossário

| Termo | Definição |
|---|---|
| Forma de pagamento | Canal pelo qual o cliente paga (Dinheiro, PIX, Cartão, etc.) |
| Condição de pagamento | Regras de prazo, desconto, juros e parcelamento |
| Parcela | Uma das N divisões do valor total a receber |
| Carência (grace_days) | Dias após vencimento antes de aplicar multa/juros |
| Multa (fine_percent) | Percentual cobrado sobre atraso além da carência |
| Juros (interest) | Acréscimo pelo parcelamento (embutido na geração de parcelas) |
| Entrada (has_entry) | Parcela paga no ato da venda, sem prazo |
| À vista | Pagamento único, imediato ou em prazo único |
| Parcelado | Múltiplas parcelas com intervalo fixo |
| Crediário | Parcelamento interno do estabelecimento, sem operadora |
| Convênio | Crédito vinculado a uma entidade (empresa, associação) |
| is_system | Registro criado pelo sistema, compartilhado entre tenants, não editável |
