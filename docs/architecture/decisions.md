# Architecture Decision Records (ADR)

## ADR-001 - Multiempresa

Decisão:

Shared Database + Tenant Column

Modelo:

tenant_id em todas as entidades de negócio.

Motivação:

- Menor custo operacional
- Menor complexidade
- Melhor para SaaS

---

## ADR-002 - UUID

Todas entidades principais possuem:

- id
- uuid

UUID é identificador técnico.

Usuários nunca trabalham com UUID.

---

## ADR-003 - Código Operacional

Todas entidades operacionais possuem código.

Exemplos:

CLI000001
PRO000001
VEN000001
PED000001
LOJ000001

Os códigos são independentes por tenant.

---

## ADR-004 - Arquitetura Backend

Padrão adotado:

Controller
→ Action
→ Service
→ Repository (somente quando necessário)

Evitar:

- Clean Architecture excessiva
- Over engineering

---

## ADR-005 - Produto e Variante

Produto não possui estoque.

Produto representa catálogo.

Variante representa item vendável.

Exemplo:

Produto:

Camiseta Dry Fit

Variantes:

Preto P
Preto M
Preto G

---

## ADR-006 - Categorias

Produto pode possuir múltiplas categorias.

Implementação:

product_categories

Não utilizar:

products.category_id

---

## ADR-007 - Estoque

Estoque sempre pertence à variante.

Nunca ao produto.

Correto:

InventoryItem
ProductVariant

Errado:

products.stock

---

## ADR-008 - Estoque por Loja

Estoque é controlado por loja.

InventoryItem representa:

Variante + Loja

---

## ADR-009 - Estoque por Movimentação

Saldo é consequência.

Toda alteração gera:

InventoryMovement

Não atualizar estoque diretamente.

---

## ADR-010 - Condicional

Condicional é domínio próprio.

Não é venda pendente.

Permitir:

- Conversão parcial
- Devolução parcial
- Controle de vencimento

---

## ADR-011 - RBAC

Role pertence ao tenant.

Permission é global.

Não utilizar:

is_admin

Não utilizar:

role == admin

Toda autorização passa por permissões.

---

## ADR-012 - PDV

PDV é aplicação independente.

Stack:

Tauri
SQLite

Estratégia:

Offline First

Backend authoritative.

---

## ADR-013 - Fiscal

Venda e documento fiscal são domínios separados.

Venda pode existir sem NFC-e.

---

## ADR-014 - Consumidor Final

Todo tenant possui:

Consumidor Final

Regras:

- obrigatório
- não removível
- não inativável

---

## ADR-015 - Loja Padrão

Ao criar tenant:

Criar loja:

Matriz

Código:

LOJ000001

(6 dígitos — alinhado ao ADR-003. Corrigido na Fase 13; o texto anterior dizia
`LOJ0001`.)

---

## ADR-016 - Canal Padrão

Ao criar tenant:

Criar canal:

PDV Principal

Código:

CAN000001

(6 dígitos — alinhado ao ADR-003. Corrigido na Fase 13; o texto anterior dizia
`CAN0001`.)

---

## ADR-017 - Contexto de Tenant Fail-Closed (Fase 13)

Decisão:

`TenantScope` falha fechado.

Quando o contexto de tenant é nulo, qualquer consulta a um modelo multi-tenant
**lança** `TenantContextMissingException` — nunca retorna dados de todos os
tenants.

Implicações:

- Todo job, listener enfileirado e comando que toca modelo escopado DEVE
  estabelecer contexto (`TenantContext::set`) ou usar `forTenant($id)` /
  `withoutTenantScope()` explicitamente para varreduras cross-tenant.
- Listeners que podem rodar síncronos (`QUEUE_CONNECTION=sync`) devem usar
  `TenantContext::runFor($tenantId, fn)`, que **restaura** o contexto anterior.

Motivação:

- Eliminar vazamento entre tenants em contexto de background (blocker A2-01).

---

## ADR-018 - Compra gera Contas a Pagar (Fase 13)

Decisão:

Recebimento de compra gera título a pagar.

`ReceivePurchaseAction` emite `PurchaseReceived`; o módulo Finance cria um
`FinancialEntry` (Expense/Pending) por recebimento, espelhando venda → contas a
receber. Recebimentos parciais geram um título cada.

Motivação:

- Fechar o ciclo financeiro de compras (gap A9-01).

---

## ADR-019 - PDV exige Caixa Aberto (Fase 13)

Decisão:

Conclusão de venda com `sales_channel = pdv` exige sessão de caixa **aberta**.

Demais canais (ecommerce, social, marketplace) não dependem de caixa.

Motivação:

- Honrar a domain-rule "venda exige caixa aberto" sem bloquear omnichannel
  (A7-02). A validação é autoritativa no backend, não apenas no cliente PDV.
