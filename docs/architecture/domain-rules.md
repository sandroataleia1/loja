# Domain Rules

## Tenant

Toda entidade de negócio deve possuir:

tenant_id

Nenhuma consulta pode ignorar tenant.

**Fail-closed (Fase 13):** sem contexto de tenant, consultas a modelos
multi-tenant **lançam exceção** (`TenantContextMissingException`) — nunca
retornam dados de todos os tenants. Jobs/listeners/comandos de background DEVEM
estabelecer contexto via `TenantContext::set`/`runFor`, ou usar
`forTenant($id)` / `withoutTenantScope()` explicitamente em varreduras
cross-tenant. Use `runFor()` (restaura o contexto anterior) em listeners que
podem rodar síncronos.

**Entidades-pai × itens-filho:** entidades consultadas diretamente (incl.
`PurchaseReceipt`) possuem `tenant_id` próprio. Itens-filho puros (acessados
apenas via o agregado-raiz já escopado — ex.: `*_items`, `conditional_status_history`,
`customer_addresses/contacts`, `fiscal_document_items/events/responses`) podem
não ter `tenant_id`; reavaliar se algum ganhar route binding próprio.

---

## Usuários

Usuário pode futuramente participar de múltiplos tenants.

Utilizar TenantUser.

---

## Clientes

Código:

CLI000001

Consumidor Final:

is_default_consumer = true

Não pode:

- excluir
- inativar

---

## Produtos

Código:

PRO000001

Produto representa catálogo.

Produto não representa estoque.

---

## Variantes

Representam item vendável.

Toda venda referencia:

product_variant_id

Nunca:

product_id

---

## Categorias

Produto pode pertencer a várias categorias.

Utilizar tabela pivot.

---

## Estoque

Estoque pertence à variante.

Estoque é multi-loja.

---

## Inventory Item

Representa:

Variante + Loja

Campos fundamentais:

- current_stock
- reserved_stock
- available_stock

---

## Inventory Movement

Obrigatório para:

- Entrada
- Saída
- Ajuste
- Venda
- Transferência
- Condicional

---

## Reservas

Reserva não reduz saldo físico.

Reserva reduz disponibilidade.

---

## Condicional

Cliente obrigatório.

Prazo obrigatório.

Permitir:

- devolução parcial
- conversão parcial

Status:

OPEN
PARTIALLY_RETURNED
RETURNED
PARTIALLY_CONVERTED
CONVERTED
OVERDUE
CANCELLED

---

## Vendas

Venda sempre referencia variante.

Venda baixa estoque.

Cancelamento devolve estoque.

**Item sem variante (Fase 13):** `product_variant_id` é nullable de propósito
(itens manuais/avulsos e preservação de histórico de variante deletada). A baixa
de estoque corretamente ignora itens sem variante (não há estoque a debitar).

## Compras

Recebimento de compra dá entrada no estoque (InventoryMovement IN).

**Recebimento gera contas a pagar** (Fase 13): cada recebimento cria um
`FinancialEntry` Expense/Pending no valor Σ(qty_received × unit_cost).

## Financeiro

Venda concluída gera contas a receber (FinancialEntry Income; PAID para
métodos instantâneos, PENDING para cartão).

**Cancelamento de venda estorna o financeiro** (Fase 13): os `FinancialEntry` da
venda são cancelados (revertendo saldo de conta quando aplicável).

Toda movimentação financeira é auditável e nunca deletada (apenas cancelada).

---

## Caixa

Venda exige caixa aberto.

Caixa pertence à loja.

**Escopo (Fase 13):** a exigência de caixa aberto é imposta no backend para
vendas de canal **PDV** (`sales_channel = pdv`). Canais não-PDV (ecommerce,
social, marketplace) não dependem de caixa. Concluir uma venda PDV sem sessão
aberta retorna erro de negócio (422).

---

## Fiscal

Venda não depende de NFC-e.

Documento fiscal possui ciclo próprio.

Status fiscal independente.

---

## Permissões

Toda autorização passa por RBAC.

Não utilizar verificações hardcoded.

---

## Eventos

Toda ação importante gera evento.

Exemplos:

CustomerCreated
ProductCreated
SaleCompleted
InventoryAdjusted

---

## Auditoria

Toda operação crítica deve gerar auditoria.

Exemplos:

- alteração de estoque
- cancelamento venda
- emissão fiscal
- alteração preço
- alteração permissões

---

## Preparação para Futuro

A arquitetura deve permanecer compatível com:

- Ecommerce
- Social Commerce
- Omnichannel
- IA
- Marketplace

Sem exigir refatorações estruturais.
