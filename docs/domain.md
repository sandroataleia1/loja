# Documento de Domínio — Plataforma Operacional SaaS para Varejo de Material de Construção

## Objetivo

Definir os conceitos centrais, regras de negócio fundamentais, entidades principais e contratos conceituais da plataforma.

Este documento serve para:

- alinhar backend, frontend e PDV
- evitar inconsistências arquiteturais
- evitar regras conflitantes entre IA/agentes
- reduzir dívida técnica
- definir linguagem oficial do sistema
- padronizar futuras implementações

---

# Visão do Produto

A plataforma NÃO é um ERP contábil tradicional.

A plataforma é:

> uma plataforma operacional omnichannel para varejo de material de construção.

Foco principal:

- catálogo inteligente
- operação de loja
- estoque
- PDV offline-first
- CRM
- WhatsApp
- vendedores
- omnichannel
- multiloja
- IA operacional

---

# Arquitetura Geral

## Backend

- Laravel 12
- PostgreSQL
- Redis
- Docker
- Sanctum
- Multi-tenant shared database

---

## Frontend Administrativo

- Next.js
- ShadCN
- Tailwind
- React Hook Form

---

## PDV Offline

- Tauri 2
- React
- SQLite local
- Offline-first

---

# Conceitos Fundamentais

# Tenant

## Definição

Representa a empresa dona dos dados.

Toda operação pertence obrigatoriamente a um tenant.

---

## Exemplos

- Construção Center
- Depósito & Ferragens Prime
- Material Total

---

## Regras

- todo dado operacional possui tenant_id
- tenant_id nunca vem do frontend
- tenant é resolvido via backend/auth
- isolamento entre tenants é obrigatório
- vazamento entre tenants é falha crítica

---

## Estrutura Base

```text
Tenant
├── Stores
├── Users
├── Products
├── Sales
├── Inventory
└── Settings
```

---

# Store (Loja)

## Definição

Representa unidade física, filial ou operação específica.

Uma empresa pode possuir múltiplas lojas.

---

## Exemplos

Empresa:
Construção Center

Lojas:

- Centro
- Shopping Norte
- Outlet

---

## Regras

- store pertence a tenant
- estoque pertence à loja
- venda pertence à loja
- caixa pertence à loja
- transferência pode ocorrer entre lojas

---

# User

## Definição

Usuário autenticado da plataforma.

---

## Regras

- usuário pertence a tenant
- usuário pode pertencer a uma loja principal
- usuário possui roles e permissions
- toda ação deve ser auditada

---

## Possíveis Roles

- super_admin
- admin
- gerente
- vendedor
- caixa
- estoque
- financeiro

---

# Product

## Definição

Entidade principal do catálogo.

Representa o produto pai.

O produto NÃO possui estoque direto.

---

## Exemplos

- Camiseta Oversized
- Calça Skinny Jeans
- Vestido Longo Premium

---

## Responsabilidades

- nome
- descrição
- categoria
- marca
- coleção
- tags
- mídia
- atributos

---

## Regras

- produto possui variantes
- produto pode possuir múltiplas imagens
- produto pode possuir múltiplas categorias
- produto deve suportar catálogo digital
- produto deve suportar omnichannel

---

# Product Variant

## Definição

Representa uma variação vendável do produto.

A variante é a unidade operacional real.

---

## Exemplos

Produto:
Camiseta Oversized

Variantes:

- Preta P
- Preta M
- Preta G
- Branca M

---

## Regras

- variante possui SKU
- variante possui código de barras opcional
- variante possui estoque próprio
- variante pode possuir preço próprio
- variante pode possuir mídia específica

---

## Observação Crítica

Estoque SEMPRE pertence à variante.

Nunca diretamente ao produto.

---

# Attribute

## Definição

Representa características configuráveis de variantes.

---

## Exemplos

- Cor
- Tamanho
- Material
- Voltagem

---

# Attribute Option

## Definição

Representa valores possíveis de um atributo.

---

## Exemplos

Cor:

- Preto
- Branco
- Azul

Tamanho:

- P
- M
- G
- GG

---

# SKU

## Definição

Identificador operacional único da variante.

---

## Estratégia

Modelo híbrido:

- sistema gera automaticamente
- usuário pode editar

---

## Exemplo

```text
TSHIRT-PT-M
```

---

# Category

## Definição

Classificação hierárquica de produtos.

---

## Exemplos

- Masculino
- Feminino
- Calçados
- Acessórios

---

# Brand

## Definição

Marca do produto.

---

## Exemplos

- Nike
- Adidas
- Marca Própria

---

# Collection

## Definição

Agrupamento sazonal/comercial.

---

## Exemplos

- Verão 2026
- Inverno Premium
- Black Edition

---

# Tag

## Definição

Marcadores livres para organização e busca.

---

## Exemplos

- lançamento
- premium
- promoção
- tendência

---

# Product Media

## Definição

Arquivos relacionados ao produto.

---

## Tipos

- imagem
- vídeo
- banner

---

## Regras

- produto pode possuir múltiplos arquivos
- mídia deve ser desacoplada do produto
- uploads devem ser segregados por tenant

---

# Inventory

## Definição

Representa saldo atual da variante por loja.

---

## Estrutura Conceitual

```text
Inventory
├── tenant_id
├── store_id
├── product_variant_id
├── quantity
├── reserved_quantity
└── available_quantity
```

---

## Regras

- estoque pertence à loja
- estoque pertence à variante
- estoque nunca é alterado manualmente sem movimentação
- available_quantity = quantity - reserved_quantity

---

# Stock Movement

## Definição

Registro imutável de movimentação de estoque.

---

## Tipos

- SALE
- PURCHASE
- TRANSFER
- RETURN
- LOSS
- ADJUSTMENT
- INVENTORY

---

## Regras

- estoque é calculado através das movimentações
- toda alteração gera movimentação
- movimentações são auditáveis
- movimentações suportam sincronização futura

---

# Cart

## Definição

Carrinho temporário de venda.

---

## Regras

- pode existir offline
- pode ser sincronizado posteriormente
- pertence a usuário/caixa

---

# Sale

## Definição

Representa venda concluída.

---

## Regras

- venda pertence ao tenant
- venda pertence à loja
- venda pertence ao caixa
- venda possui itens
- venda gera movimentação de estoque
- venda pode ser sincronizada posteriormente

---

# Sale Item

## Definição

Item individual da venda.

---

## Regras

- referencia variante
- armazena preço histórico
- armazena desconto histórico
- não depende do preço atual do produto

---

# Cash Register (Caixa)

## Definição

Sessão operacional do PDV.

---

## Regras

- caixa pertence à loja
- caixa possui abertura
- caixa possui fechamento
- vendas pertencem ao caixa
- caixa pode operar offline

---

# Offline-First

## Definição

PDV deve operar independentemente da internet.

---

## Regras

- venda nunca depende de conexão online
- dados críticos ficam em SQLite local
- sincronização ocorre posteriormente
- sistema deve suportar reconexão automática

---

# Sync Queue

## Definição

Fila local de sincronização.

---

## Responsabilidades

- armazenar eventos pendentes
- retry automático
- sincronização incremental
- prevenção de duplicidade
- controle de falhas

---

# Auditoria

## Definição

Registro completo de operações críticas.

---

## Campos obrigatórios

- tenant_id
- store_id
- user_id
- ação
- before
- after
- IP
- timestamp

---

# Eventos de Domínio

## Objetivo

Desacoplar módulos.

---

## Exemplos

- ProductCreated
- ProductUpdated
- StockTransferred
- SaleCompleted
- CustomerCreated

---

# Regras Arquiteturais Obrigatórias

## Backend

Fluxo obrigatório:

```text
Request
→ Controller
→ Action
→ Service (se necessário)
→ Model
→ Resource
```

---

## Regras

- controllers finos
- DTO obrigatório
- enums obrigatórios
- UUID obrigatório
- sem arrays crus
- sem lógica em controllers
- sem repositories desnecessários
- sem abstrações excessivas

---

# Convenções Obrigatórias

## API

```text
/api/v1
```

---

## Response Success

```json
{
  "success": true,
  "data": {},
  "meta": {}
}
```

---

## Response Error

```json
{
  "success": false,
  "message": "",
  "errors": {}
}
```

---

# Objetivos Futuros

A arquitetura deve suportar:

- catálogo digital
- WhatsApp
- CRM
- IA operacional
- omnichannel
- aplicativo mobile
- marketplace
- múltiplos caixas
- NFC-e
- integração fiscal
- analytics
- recomendação inteligente
- reposição automática
- previsão de demanda

---

# Objetivo Final

Construir uma plataforma operacional sólida, escalável e sustentável para varejo de material de construção, evitando:

- dívida técnica
- acoplamento excessivo
- spaghetti architecture
- refatorações destrutivas
- inconsistência entre módulos
- vazamento multi-tenant
