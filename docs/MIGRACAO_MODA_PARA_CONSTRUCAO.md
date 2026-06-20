# Plano de Migração: SaaS de Moda → Material de Construção

> **Arquiteto responsável:** IA Assistente  
> **Data:** 2026-06-20  
> **Status:** ⚠️ SUPERSEDED — Substituído por `docs/migration/` (STEP_01 a STEP_05 + FINAL_AUDIT)

---

## Sumário Executivo

A mudança de domínio de **varejo de moda** para **material de construção** não exige reescrita da plataforma. A arquitetura multi-tenant com catálogo genérico (Produto → Variante → Atributo) já suporta qualquer segmento. O que muda são:

1. **Dados de referência** — categorias, atributos e grids de variação
2. **Enums de domínio** — remover conceitos de vestuário (gênero)
3. **UI do catálogo** — rótulos, seletores e formulários
4. **Seeds e dados de exemplo** — substituir por construção
5. **Textos e labels** — nomenclatura de negócio

O que **não muda**: arquitetura multi-tenant, RBAC, Estoque, Financeiro, Fiscal, PDV, Sync, PIX/Asaas.

---

## Estrutura do Plano

| Parte | Escopo | Esforço estimado |
|-------|--------|-----------------|
| [Parte 1](#parte-1--enums-e-modelos-de-domínio) | Enums e modelos de domínio | Baixo — 1 dia |
| [Parte 2](#parte-2--categorias-e-atributos-de-construção) | Categorias e atributos | Médio — 2 dias |
| [Parte 3](#parte-3--grids-de-variação-técnica) | Grids de variação técnica | Médio — 2 dias |
| [Parte 4](#parte-4--frontend--catálogo) | Frontend — catálogo | Médio — 3 dias |
| [Parte 5](#parte-5--frontend--pdv-web) | Frontend — PDV Web | Baixo — 1 dia |
| [Parte 6](#parte-6--seeds-e-dados-de-demonstração) | Seeds e dados demo | Baixo — 1 dia |
| [Parte 7](#parte-7--documentação-e-ajustes-finais) | Documentação e ajustes finais | Baixo — 1 dia |

**Total estimado: ~11 dias de dev**

---

## Parte 1 — Enums e Modelos de Domínio

### Objetivo
Remover enums exclusivos de moda e adaptar tipos de produto para o contexto de construção.

### O que remover

#### `backend/app/Modules/Catalog/Enums/ProductGenderEnum.php`
- Cases atuais: `Masculino`, `Feminino`, `Unissex`, `Infantil`, `Todos`
- **Ação:** Deletar o enum completamente
- Verificar e remover referência em: `Product.php ($casts)`, `ProductResource.php`, `ProductRequest.php`, `ProductFactory.php`, migrations

#### Campo `gender` na tabela `catalog_products`
- **Ação:** Criar migration para dropar a coluna `gender`
- Não tem relação com construção; não haverá substituição direta

### O que adaptar

#### `backend/app/Modules/Catalog/Enums/ProductTypeEnum.php`
- Cases atuais: `Simple`, `Variant`, `Kit`
- **Ação:** Manter como está — genérico e adequado para construção
- Exemplos de uso em construção: cimento = `Simple`, porcelanato = `Variant` (dimensões), kit hidráulico = `Kit`

#### `backend/app/Modules/Catalog/Enums/AttributeTypeEnum.php`
- Cases atuais: `Text`, `Color`, `Number`, `Boolean`
- **Ação:** Manter + avaliar adicionar `Select` (dropdown de opções fixas)
- `Color` permanece (tintas têm cores); `Number` cobre medidas e dimensões

### Checklist Parte 1

- [ ] 1.1 Remover `ProductGenderEnum.php`
- [ ] 1.2 Remover `$casts['gender']` em `Product.php`
- [ ] 1.3 Remover campo `gender` de `ProductResource.php` e `ProductRequest.php`
- [ ] 1.4 Remover campo `gender` de `ProductFactory.php`
- [ ] 1.5 Criar migration `drop_gender_from_catalog_products`
- [ ] 1.6 Remover referência ao gender no frontend (`apps/admin/src/features/catalog/`)
- [ ] 1.7 Rodar suite de testes: `docker exec store_app php artisan test`

---

## Parte 2 — Categorias e Atributos de Construção

### Objetivo
Criar a hierarquia de categorias e os atributos técnicos usados em material de construção.

### Taxonomia de Categorias Proposta

```
Materiais de Construção
├── Alvenaria
│   ├── Tijolos e Blocos
│   ├── Argamassa e Cimento
│   └── Areia e Brita
├── Revestimentos
│   ├── Porcelanato e Cerâmica
│   ├── Pedra Natural
│   └── Pastilhas
├── Pintura
│   ├── Tinta Acrílica
│   ├── Tinta Esmalte
│   ├── Massa Corrida e Selador
│   └── Impermeabilizante
├── Hidráulica
│   ├── Tubos e Conexões PVC
│   ├── Metais e Louças
│   └── Caixas d'Água
├── Elétrica
│   ├── Cabos e Fios
│   ├── Disjuntores e Quadros
│   └── Tomadas e Interruptores
├── Ferragens e Fixação
│   ├── Parafusos e Buchas
│   ├── Pregos e Arames
│   └── Dobradiças e Fechaduras
├── Madeira e Compensados
│   ├── Tábuas e Vigas
│   ├── MDF e Compensado
│   └── Portas e Janelas
├── Cobertura
│   ├── Telhas
│   ├── Calhas e Rufos
│   └── Impermeabilizante para Telhado
├── Ferramentas
│   ├── Ferramentas Manuais
│   ├── Ferramentas Elétricas
│   └── EPIs
└── Jardim e Paisagismo
    ├── Terra e Substrato
    └── Produtos para Jardim
```

### Atributos Técnicos Padrão por Categoria

| Categoria | Atributos Relevantes |
|-----------|---------------------|
| Porcelanato / Cerâmica | Dimensão (ex: 60x60cm, 90x90cm), Acabamento (Polido, Acetinado, Rústico), PEI, Absorção |
| Tintas | Cor (código + nome), Rendimento (m²/L), Número de Demãos, Diluição |
| Cabos Elétricos | Bitola (mm²), Voltagem (110V/220V/Bivolt), Cor do isolamento, Metragem |
| Tubos PVC | Diâmetro (mm), Comprimento (m), Classe (PN/PB), Tipo (Soldável/Rosca) |
| Cimento / Argamassa | Tipo (CP II, CP III, ACIII), Peso (kg), Validade |
| Madeira | Espessura (mm), Largura (cm), Comprimento (m), Tratamento |
| Tijolos / Blocos | Dimensão (comprimento × largura × altura cm), Resistência (MPa) |
| Ferramentas Elétricas | Voltagem, Potência (W), Velocidade (RPM), Garantia |

### Implementação

#### Seeder de Categorias (`CategorySeeder.php`)
- Criar `backend/database/seeders/ConstructionCategorySeeder.php`
- Hierarquia 2 níveis (categoria pai + subcategoria)
- Campos: `name`, `slug`, `parent_id`, `tenant_id` = NULL (globais) ou por tenant

#### Seeder de Atributos (`AttributeSeeder.php`)
- Criar `backend/database/seeders/ConstructionAttributeSeeder.php`
- Atributos globais reutilizáveis: Dimensão, Cor, Voltagem, Peso, Diâmetro, Acabamento

### Checklist Parte 2

- [ ] 2.1 Criar `ConstructionCategorySeeder.php` com hierarquia de 2 níveis (10 categorias pai, 30 subcategorias)
- [ ] 2.2 Criar `ConstructionAttributeSeeder.php` com 12 atributos técnicos
- [ ] 2.3 Registrar ambos os seeders em `DatabaseSeeder.php`
- [ ] 2.4 Testar seeders: `docker exec store_app php artisan db:seed --class=ConstructionCategorySeeder`
- [ ] 2.5 Validar estrutura no painel admin (CRUD de categorias)

---

## Parte 3 — Grids de Variação Técnica

### Objetivo
Substituir os grids de moda (Tamanho + Cor) por grids de variação técnica para construção.

### Contexto Atual
O sistema usa `AttributeGroup` + `Grid` para definir combinações de variantes.
Exemplo moda: Grid "Camiseta Padrão" = Tamanho (P/M/G/GG) × Cor (Branco/Preto/Azul)

### Novo Modelo Mental para Construção
Variantes em construção são geralmente **1 dimensão** (não 2):
- Porcelanato 60x60cm, 90x90cm — variante de **Dimensão**
- Tinta 3,6L, 18L — variante de **Volume**
- Cabo 1,5mm², 2,5mm² — variante de **Bitola**
- Tubo PVC 50mm, 75mm, 100mm — variante de **Diâmetro**

### Grids Padrão a Criar (via Seeder ou Admin)

| Grid | Atributo | Valores |
|------|----------|---------|
| Grid Porcelanato | Dimensão | 30x30, 45x45, 60x60, 80x80, 90x90, 120x60 |
| Grid Tinta | Volume | 900ml, 3,6L, 18L |
| Grid Cabo Elétrico | Bitola (mm²) | 1,5 / 2,5 / 4,0 / 6,0 / 10,0 / 16,0 |
| Grid Tubo PVC | Diâmetro (mm) | 20 / 25 / 32 / 40 / 50 / 60 / 75 / 100 |
| Grid Madeira | Espessura (mm) | 15 / 18 / 20 / 25 |
| Grid Saco (peso) | Peso (kg) | 5 / 20 / 25 / 40 / 50 |
| Grid Cor Tinta | Cor | Branco / Marfim / Bege / Cinza / Outros |

### Adaptação do Frontend — `VariantPicker`

O `VariantPicker.tsx` atual exibe botões visuais de cor e tamanho. Para construção:

- Remover estilo de "cor como bolinha colorida"
- Substituir por seleção técnica: `<Select>` ou botões de texto
- Rótulo dinâmico baseado no `attribute.name` do grupo (ex: "Dimensão", "Bitola")
- O picker deve funcionar com 1 ou 2 atributos (construção geralmente 1, mas tinta pode ter Cor + Volume)

### Checklist Parte 3

- [ ] 3.1 Criar `ConstructionGridSeeder.php` com 7 grids + valores listados acima
- [ ] 3.2 Adaptar `VariantPicker.tsx`: remover render especial de "cor" (bolinha colorida)
- [ ] 3.3 Garantir que `VariantPicker` exiba rótulo do atributo dinamicamente
- [ ] 3.4 Testar criação de produto Porcelanato com grid de Dimensão
- [ ] 3.5 Testar PDV: buscar produto, selecionar dimensão e adicionar ao carrinho

---

## Parte 4 — Frontend — Catálogo

### Objetivo
Adaptar os formulários e listagens do módulo de catálogo para o contexto de construção.

### Componentes a adaptar

#### `apps/admin/src/features/catalog/`

| Componente / Página | Mudança Necessária |
|--------------------|--------------------|
| `ProductForm.tsx` | Remover campo "Gênero"; adicionar campos técnicos (NCM para fiscal de construção) |
| `ProductList.tsx` | Remover coluna "Gênero"; coluna "Unidade" mais visível (un, m², kg, m, L) |
| `variant-table.tsx` | Substituir cabeçalhos "Tamanho / Cor" por atributo dinâmico |
| `category-selector.tsx` | Testar com nova hierarquia de construção |
| `AttributeGroupForm.tsx` | Garantir suporte a atributo único por grid |

#### Unidades de Medida

Construção usa unidades variadas. Garantir que o campo de unidade de venda cubra:

| Unidade | Código | Uso |
|---------|--------|-----|
| Unidade | UN | Parafusos, ferramentas |
| Metro quadrado | M2 | Piso, cerâmica |
| Metro linear | ML | Cabo, tubo, madeira |
| Quilograma | KG | Cimento, areia (saco) |
| Litro | L | Tinta, solvente |
| Saco | SC | Cimento 50kg, areia |
| Rolo | RL | Cabo, fita, manta |
| Peça | PC | Tijolos, telhas |

Se não houver enum de unidade, criar `UnitOfMeasureEnum` ou campo texto livre validado.

#### Campos Adicionais Desejáveis (Fase Futura)

- `ncm_code` — Código NCM obrigatório para NF-e de construção (maioria NCM 6908, 3214, 8544, etc.)
- `weight_kg` e `dimensions_cm` — para frete e logística (ex: saco de 50kg)
- `min_purchase_qty` — mínimo por caixa/palete (ex: cerâmica vende por m², mínimo 1 cx = 2,16m²)

### Checklist Parte 4

- [ ] 4.1 Remover campo `gender` do `ProductForm.tsx`
- [ ] 4.2 Adicionar campo `unit_of_measure` (UN/M2/ML/KG/L/SC/RL/PC) no `ProductForm.tsx`
- [ ] 4.3 Remover coluna "Gênero" da `ProductList.tsx`; exibir "Unidade" em destaque
- [ ] 4.4 Adaptar `variant-table.tsx` para exibir nome de atributo dinâmico (não "Tamanho/Cor" fixo)
- [ ] 4.5 Validar `category-selector.tsx` com nova árvore de categorias de construção
- [ ] 4.6 Testar criação de 5 produtos diferentes: Cimento (Simple), Porcelanato (Variant/Dimensão), Tinta (Variant/Volume+Cor), Parafuso (Simple/Caixa), Kit Hidráulico (Kit)
- [ ] 4.7 Garantir que o formulário de produto não quebre com `gender = null` em registros existentes

---

## Parte 5 — Frontend — PDV Web

### Objetivo
Ajustes pontuais no PDV para refletir o contexto de material de construção.

### O que mudar

| Elemento | Situação Atual | Ação |
|----------|---------------|------|
| `VariantPicker` no PDV | Botões visuais de cor/tamanho | Herda mudança da Parte 3 |
| Rótulo "Tamanho" | Hardcoded em alguns lugares | Tornar dinâmico via `attribute.name` |
| Exibição de unidade no carrinho | Sem destaque | Exibir unidade ao lado da quantidade (ex: "2 M2") |
| Casas decimais na quantidade | Inteiro | Permitir decimais para M2, ML, KG (ex: 3,75 m²) |

### Quantidade com Decimais

Material de construção vende por fração: 3,75m² de porcelanato, 12,5kg de argamassa.

- Carrinho: campo de quantidade aceitar `step="0.01"`
- Backend: verificar se `quantity` em `SaleItem` é `decimal` ou `integer`
- Se for `integer`: criar migration para alterar para `decimal(10,3)` e ajustar cálculos

### Checklist Parte 5

- [ ] 5.1 Verificar tipo da coluna `quantity` em `sale_items` e `conditional_items`
- [ ] 5.2 Se necessário: migration para `decimal(10,3)` em quantity nas tabelas de venda
- [ ] 5.3 Ajustar `CartItemRow.tsx`: `input type="number" step="0.01"` para unidades não-inteiras
- [ ] 5.4 Exibir unidade de medida ao lado da quantidade no carrinho (ex: "2 cx" / "3,75 m²")
- [ ] 5.5 Ajustar `CartTotals.tsx` para calcular corretamente com qty decimal
- [ ] 5.6 Testar venda de 3,75m² de porcelanato no PDV Web
- [ ] 5.7 Testar impressão do cupom com quantidade decimal

---

## Parte 6 — Seeds e Dados de Demonstração

### Objetivo
Criar um conjunto de dados realistas de material de construção para demonstração e onboarding.

### Produtos Demo a Criar (via Seeder ou Fixture)

| Produto | Tipo | Categoria | Variante |
|---------|------|-----------|----------|
| Cimento CP II-E 50kg (Votorantim) | Simple | Alvenaria > Cimento | - |
| Porcelanato Polido Cinza 60x60cm | Variant | Revestimentos > Porcelanato | Dimensão |
| Tinta Acrílica Branco Neve 18L (Suvinil) | Variant | Pintura > Tinta Acrílica | Volume |
| Cabo Flexível 2,5mm² 100m (Tramontina) | Variant | Elétrica > Cabos | Bitola |
| Tubo PVC Soldável 50mm × 6m | Variant | Hidráulica > Tubos | Diâmetro |
| Parafuso Cabeça Philips 4,2×38mm (c/ 100un) | Simple | Ferragens > Parafusos | - |
| Argamassa ACIII 20kg (Weber) | Simple | Alvenaria > Argamassa | - |
| Rolo de Lã de Carneiro 23cm | Simple | Pintura > Acessórios | - |
| Telha Ondulada Eternit 2,13m | Simple | Cobertura > Telhas | - |
| Kit Registro + Válvula + Ligação | Kit | Hidráulica > Metais | - |

### Clientes Demo

- Consumidor Final (padrão, já existe)
- Construtora Exemplo Ltda. (CNPJ)
- João da Silva — Pedreiro Autônomo (CPF)

### Fornecedores Demo

- Votorantim Cimentos
- Suvinil Tintas
- Tigre (PVC/Hidráulica)
- Tramontina (Cabos)

### Checklist Parte 6

- [ ] 6.1 Criar `ConstructionProductSeeder.php` com 10 produtos demo (incluindo variantes)
- [ ] 6.2 Criar `ConstructionSupplierSeeder.php` com 4 fornecedores
- [ ] 6.3 Criar `ConstructionCustomerSeeder.php` com 2 clientes adicionais (CPF + CNPJ)
- [ ] 6.4 Registrar todos em `DatabaseSeeder.php` (apenas para `--class=Demo` ou ambiente local)
- [ ] 6.5 Testar seeder completo em banco limpo
- [ ] 6.6 Validar estoque inicial dos produtos (mínimo 1 unidade para testar PDV)

---

## Parte 7 — Documentação e Ajustes Finais

### Objetivo
Atualizar textos, labels globais e documentação para refletir o novo domínio.

### Labels e Textos

| Localização | Texto Atual | Texto Novo |
|-------------|------------|------------|
| `apps/admin/src/` — título do app | "Store / Moda" ou similar | "Material de Construção" |
| Sidebar admin | "Catálogo de Moda" | "Catálogo" |
| Emails e notificações | referências a "loja de moda" | "loja" ou "empresa" |
| README.md | descrição da plataforma | atualizar para construção |
| `docs/` | referências a varejo de moda | atualizar |

### Fiscal — NCM de Construção

O módulo fiscal já existe (Sprint 11). Para construção, os NCMs mais usados precisam estar disponíveis no cadastro de produto. Não é necessário alterar o módulo, apenas:

- Confirmar que o campo `ncm_code` existe em `catalog_products` (checar migration)
- Se não existir: adicionar migration com campo `string(8) nullable`
- Orientar usuário sobre NCMs comuns de construção (documentação)

### NCMs Mais Comuns em Material de Construção

| Produto | NCM |
|---------|-----|
| Tinta imobiliária | 3209.10.00 |
| Cimento Portland | 2523.29.10 |
| Porcelanato | 6908.90.00 |
| Cabo elétrico | 8544.49.00 |
| Tubo PVC | 3917.21.00 |
| Parafusos aço | 7318.15.00 |
| Argamassa | 3214.90.00 |
| Tijolo cerâmico | 6904.10.00 |

### Checklist Parte 7

- [ ] 7.1 Verificar e atualizar título/branding do app no frontend
- [ ] 7.2 Verificar existência do campo `ncm_code` em `catalog_products`; criar migration se ausente
- [ ] 7.3 Adicionar campo NCM no `ProductForm.tsx` (input com máscara XXXX.XX.XX)
- [ ] 7.4 Atualizar `README.md` na raiz do projeto
- [ ] 7.5 Atualizar `memory/project_store_saas.md` (arquivo de memória do assistente)
- [ ] 7.6 Executar suite completa de testes: `docker exec store_app php artisan test`
- [ ] 7.7 Build de produção do frontend: `pnpm build` em `apps/admin/`
- [ ] 7.8 Testar fluxo completo: cadastrar produto → vender no PDV → imprimir cupom → fechar caixa

---

## Decisões Arquiteturais

### O que NÃO muda

| Componente | Motivo |
|------------|--------|
| Modelo Produto → Variante | Genérico; funciona para qualquer atributo técnico |
| Estoque por variante (InventoryMovement) | Invariante de negócio; obrigatório manter |
| Multi-tenancy (BelongsToTenant) | Arquitetura central; não tem relação com domínio |
| RBAC e permissões | Permissões são genéricas |
| Módulo Fiscal (NFC-e / NF-e) | Mais crítico em construção do que em moda (B2B) |
| PDV (Tauri + Web) | Funcionamento idêntico; apenas UI points |
| Financeiro (contas a pagar/receber) | Genérico |
| Compras (Purchasing) | Mais usado em construção (B2B com fornecedores) |

### Decisão: Quantidade Decimal

Material de construção exige quantidade decimal (3,75 m², 12,5 kg). **Recomendação:** alterar `quantity` em `sale_items` para `decimal(10,3)`. Isso é uma mudança simples mas requer migration e testes de cálculo de totais.

### Decisão: NCM no Produto

Para NF-e de construção (B2B), o NCM é obrigatório por produto. **Recomendação:** adicionar campo `ncm_code varchar(8) nullable` na tabela `catalog_products` agora (Parte 7). É não-bloqueante e facilita o módulo fiscal no futuro.

### Decisão: Unidade de Medida

Adicionar campo `unit_of_measure` no produto (UN/M2/ML/KG/L/SC/RL/PC). Isso impacta o PDV (exibição da quantidade) e futuro cálculo de estoque por lote.

---

## Ordem de Execução Recomendada

```
Parte 1 (Enums)
     ↓
Parte 2 (Categorias + Atributos) — pode rodar em paralelo com Parte 3
     ↓
Parte 3 (Grids de Variação)
     ↓
Parte 4 (Frontend Catálogo)
     ↓
Parte 5 (PDV Web) — pode rodar em paralelo com Parte 6
     ↓
Parte 6 (Seeds Demo)
     ↓
Parte 7 (Documentação e ajustes finais)
```

---

## Riscos e Mitigações

| Risco | Probabilidade | Impacto | Mitigação |
|-------|--------------|---------|-----------|
| Quantidade decimal quebra cálculo de total | Média | Alto | Testar `total_cents = unit_price_cents × qty` com float; usar `round()` |
| Seeder de categorias conflita com tenant_id | Baixa | Médio | Criar categorias globais (tenant_id = NULL) igual ao padrão atual |
| `VariantPicker` com 1 atributo quebra UI | Baixa | Baixo | Testar com grid de 1 dimensão antes de ir para produção |
| Campo `gender` em registros legados | Baixa | Baixo | Migration com `nullable` antes de dropar; não há dados de produção ainda |
| NCM incorreto gera rejeição de NF-e | Alta (uso real) | Alto | Documentar NCMs; campo obrigatório no cadastro de produto |

---

## Critério de Conclusão

A migração está completa quando:

1. ✅ Não existe referência a `gender`, `ProductGenderEnum`, "Tamanho", "Cor" como conceito fixo no código
2. ✅ É possível cadastrar um produto Porcelanato 60x60cm com variantes de dimensão
3. ✅ É possível vender 3,75m² de porcelanato no PDV Web com quantidade decimal
4. ✅ Cupom impresso exibe "3,75 M2 × R$ 45,00 = R$ 168,75"
5. ✅ Todos os 472 testes continuam passando
6. ✅ Build de produção do frontend sem erros de TypeScript

---

*Documento gerado em 2026-06-20. Atualizar `[ ]` para `[x]` conforme cada item for concluído.*
