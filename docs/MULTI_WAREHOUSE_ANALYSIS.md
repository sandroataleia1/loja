# Análise: Suporte a Múltiplos Depósitos

**Contexto:** Material de construção exige múltiplos locais de armazenagem desde cedo
(loja, depósito principal, depósito externo, pátio de ferragens).
Este documento registra o que a fundação atual suporta e o que precisa ser resolvido
antes do Módulo 06 (Estoque).

---

## Veredicto

**A fundação suporta múltiplos depósitos. O modelo está correto.**

O saldo nunca está no produto. Ele é armazenado em `inventory_balances`,
que tem `UNIQUE(store_id, variant_id)` — um registro por local × variante.
Toda entrada, saída, reserva e ajuste passa pelo `InventoryService`,
que exige `store_id` em todos os métodos.

Há **um gap de schema** que precisa ser corrigido antes de o Módulo 06 expor
a interface de gerenciamento de depósitos.

---

## O que já está implementado

### `inventory_balances` — saldo por local × variante
```
store_id    FK → stores.uuid
variant_id  FK → catalog_variants.uuid
quantity             INTEGER
reserved_quantity    INTEGER
UNIQUE(store_id, variant_id)
```
Não existe `stock_quantity` em `catalog_variants`. Deliberado — o comentário
na migration diz explicitamente que estoque pertence ao módulo Inventory.

### `inventory_movements` — ledger imutável
Cada linha registra `store_id` (origem), `quantity`, `quantity_before`,
`quantity_after` e `reference_type/reference_id` para rastreabilidade.
A reversão cria uma nova linha com sinal oposto; nunca se edita o histórico.

### `stock_transfers` — transferência entre locais
```
origin_store_id       FK → stores.uuid
destination_store_id  FK → stores.uuid
status: pending → in_transit → received | cancelled
```
Com `stock_transfer_items` (quantity_requested / quantity_sent / quantity_received),
o fluxo suporta despacho parcial e conferência no recebimento.

### `stock_counts` — inventário físico por local
`store_id` + `status (draft → in_progress → committed)`.
Cada item guarda `system_quantity` (snapshot) e `counted_quantity`,
e ao commit gera movimento de ajuste automático.

### RBAC multi-loja — `tenant_user_stores`
Allowlist: ausência de registros = acesso a todos os locais.
Um operador de depósito externo só vê as lojas vinculadas ao seu usuário.

### Frontend
`InventoryFilters.store_id`, formulários de transferência e ajuste,
e `inventory-table` já têm coluna "Loja". O frontend assume multi-loja.

---

## O gap: `stores.type` não existe na migration

O modelo `Store.php` tem `'type'` no `$fillable`.
O tipo `Store` no frontend inclui `type: string`.
Mas a migration `2026_05_29_000003_create_inventory_tables.php`
**não criou essa coluna**.

Sem esse campo:
- Não é possível distinguir programaticamente loja de depósito de pátio.
- A interface de gerenciamento de locais não pode filtrar por tipo.
- Relatórios de inventário não conseguem agrupar por "todos os depósitos externos".

### Valores necessários

| Valor | Uso |
|---|---|
| `store` | Ponto de venda com caixa |
| `warehouse` | Depósito sem PDV |
| `yard` | Pátio / área externa (ferragens, madeira, pedras) |
| `ecommerce` | CD exclusivo para e-commerce (sem balcão) |

O campo `is_ecommerce` atual cobre parcialmente o último caso,
mas não cobre os outros três.

---

## `InventoryPool` — preparação para roteamento omnichannel

A tabela `inventory_pools` existe (`type: store | ecommerce | marketplace | warehouse`)
mas ainda não tem FK para `inventory_balances`. A ligação está marcada
como "migração futura" no comentário da migration original.

**Impacto para o Módulo 06:** zero. O Módulo 06 não precisa de pools.
Pools entram quando o Módulo de Omnichannel (08 ou 09) implementar
roteamento automático de estoque por canal de venda.

---

## O que corrigir antes do Módulo 06

### Migration necessária (única)

```php
// 2026_06_23_300001_add_type_to_stores.php
public function up(): void
{
    if (Schema::hasColumn('stores', 'type')) {
        return;
    }

    Schema::table('stores', function (Blueprint $table): void {
        $table->string('type', 20)
              ->default('store')
              ->after('code');
    });
}
```

Valores: `store | warehouse | yard | ecommerce`
Default `store` preserva todos os registros existentes sem rotina de backfill.

### Nenhuma mudança de model ou controller necessária

`Store.php` já tem `'type'` no `$fillable` e a API já aceita o campo.
O `StoreResource` (ou resource equivalente) só precisa expor o campo,
o que acontece automaticamente se ele for incluído no `toArray`.

---

## Impacto para o Módulo 06

| Funcionalidade | Depende de `stores.type`? |
|---|---|
| Consultar saldo por local | Não |
| Ajuste manual de estoque | Não |
| Transferência entre locais | Não |
| Contagem física (inventário) | Não |
| Filtrar locais por tipo na UI | **Sim** |
| Relatório "saldo por depósito externo" | **Sim** |
| Regra "não vender do pátio direto" (se quiser) | **Sim** |

As quatro primeiras funcionalidades funcionam hoje sem nenhuma alteração.
O filtro e os relatórios por tipo exigem a migration acima.

---

## Recomendação

1. Criar a migration `add_type_to_stores` antes de iniciar o Módulo 06.
2. Ao cadastrar locais no sistema, orientar o usuário a definir o tipo
   (o formulário de cadastro de Store já existe via `/store-access`).
3. **Não** conectar `InventoryPool` ao estoque agora — deixar para
   quando o Omnichannel for implementado.
4. O Módulo 06 pode ser construído sobre a fundação atual sem
   refatoração de schema.

---

_Análise gerada em 2026-06-23. Baseada em leitura direta das migrations,
models, services e routes do projeto._
