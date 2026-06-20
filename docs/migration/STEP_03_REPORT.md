# STEP 03 REPORT — Catálogo de Categorias de Construção

**Data:** 2026-06-20
**Escopo:** Implementação do seeder de categorias para ERP de Material de Construção. Criação de 10 categorias pai e 40 subcategorias com descrições, relacionamentos hierárquicos e códigos gerados automaticamente.

---

## Objetivo

Substituir a taxonomia vazia (ou de moda) por uma hierarquia coerente com o domínio de material de construção, com:
- 10 categorias pai definidas pelo usuário
- 4 subcategorias por categoria pai (40 no total)
- Slugs únicos por tenant
- Códigos gerados via `GenerateInternalCodeAction` (`CAT000001`, `CAT000002`, …)
- Seeder idempotente (re-executável sem duplicar dados)

---

## Arquivos Criados

| Arquivo | Descrição |
|---------|-----------|
| `backend/database/seeders/ConstructionCategorySeeder.php` | Seeder principal com taxonomia de 50 categorias |

---

## Arquivos Modificados

| Arquivo | Mudança |
|---------|---------|
| `backend/database/seeders/DatabaseSeeder.php` | Adicionado `$this->call(ConstructionCategorySeeder::class)` dentro do contexto do tenant demo |

---

## Taxonomia Implementada

| # | Categoria Pai | Subcategorias |
|---|---------------|---------------|
| 1 | **Cimento e Argamassa** | Cimento Portland · Argamassa de Assentamento · Argamassa de Revestimento · Cal e Gesso |
| 2 | **Pisos e Revestimentos** | Porcelanato · Cerâmica · Pedras Naturais · Piso Vinílico e Laminado |
| 3 | **Hidráulica** | Tubos e Conexões PVC · Metais e Louças Sanitárias · Caixas d'Água e Cisternas · Bombas e Pressurizadores |
| 4 | **Elétrica** | Cabos e Fios · Disjuntores e Quadros · Tomadas e Interruptores · Iluminação |
| 5 | **Ferragens** | Parafusos e Buchas · Pregos e Arames · Dobradiças e Fechaduras · Perfis e Cantoneiras |
| 6 | **Ferramentas** | Ferramentas Manuais · Ferramentas Elétricas · EPIs e Segurança · Medição e Nível |
| 7 | **Tintas** | Tinta Acrílica · Tinta Esmalte e Verniz · Impermeabilizante · Massa Corrida e Selador |
| 8 | **Madeira** | Tábuas e Vigas · MDF e Compensado · Portas e Janelas · Decks e Pergolados |
| 9 | **Cobertura** | Telhas · Calhas e Rufos · Impermeabilizante para Telhado · Estrutura Metálica |
| 10 | **Acabamentos** | Rodapés e Soleiras · Molduras e Perfis · Silicone e Vedação · Rejuntes e Caulins |

**Total:** 50 categorias (10 pai + 40 filhas)

---

## Detalhes Técnicos

### Idempotência

O método `upsert()` verifica existência pelo slug antes de criar:

```php
private function upsert(string $name, ?string $parentId, int $sortOrder, ?string $description): Category
{
    $slug = Str::slug($name);
    $existing = Category::where('slug', $slug)->first();
    if ($existing !== null) {
        return $existing;
    }
    // cria somente se não existe
    $code = $this->generateCode->execute(...);
    return Category::create([...]);
}
```

### Execução Standalone

O seeder suporta `php artisan db:seed --class=ConstructionCategorySeeder` sem precisar de `TenantContext` pré-configurado:

```php
private function ensureTenantContext(): void
{
    if (TenantContext::isSet()) return;

    $tenant = Tenant::where('slug', 'loja-demo')->first()
        ?? Tenant::first();

    if ($tenant === null) {
        throw new \RuntimeException('Nenhum tenant encontrado. Execute DatabaseSeeder primeiro.');
    }

    TenantContext::set($tenant->uuid);
}
```

### Geração de Códigos

Usa `GenerateInternalCodeAction` com `SequenceEntityEnum::Category`:
- Prefixo: `CAT`
- Padding: 6 dígitos
- Resultado: `CAT000001` … `CAT000050`
- Thread-safe via `lockForUpdate()` na tabela `tenant_sequences`

### Relacionamentos

Cada subcategoria referencia o `uuid` da categoria pai via `parent_id`:

```php
// Categoria pai criada primeiro
$parent = $this->upsert('Cimento e Argamassa', null, 10, '...');

// Subcategorias referenciam o uuid do pai
$this->upsert('Cimento Portland', $parent->uuid, 10, '...');
```

O relacionamento usa a FK `catalog_categories.parent_id → catalog_categories.uuid` com `ON DELETE SET NULL` (definido na migration original).

### Integração com BelongsToTenant

O `Category` usa `BelongsToTenant` trait, que:
1. Auto-preenche `tenant_id` via `TenantContext::getIdOrFail()` no evento `creating`
2. Aplica `WHERE tenant_id = :id` em todas as queries via `TenantScope`

O seeder garante que `TenantContext` está configurado antes de qualquer operação.

---

## Como Executar

### Via DatabaseSeeder (migrate:fresh)

```bash
docker compose exec app php artisan migrate:fresh --seed
```

### Standalone (banco já existente)

```bash
docker compose exec app php artisan db:seed --class=ConstructionCategorySeeder
```

### Verificar resultado

```sql
-- Ver categorias criadas
SELECT code, name, parent_id, sort_order
FROM catalog_categories
ORDER BY sort_order, name;

-- Contar por nível
SELECT
  CASE WHEN parent_id IS NULL THEN 'Pai' ELSE 'Filha' END AS nivel,
  COUNT(*) AS total
FROM catalog_categories
WHERE deleted_at IS NULL
GROUP BY nivel;
-- Esperado: Pai=10, Filha=40
```

---

## Pendências Pós-Step 03

1. **Executar o seeder** (quando Docker estiver up):
   ```bash
   docker compose exec app php artisan db:seed --class=ConstructionCategorySeeder
   ```

2. **Etapas futuras da Fase 3:**
   - `ConstructionAttributeSeeder` — grupos de atributos (Dimensão, Volume, Bitola, etc.)
   - `ConstructionGridSeeder` — grids de variação para porcelanato, tinta, cabo elétrico, etc.

3. **Verificar API de categorias:**
   ```bash
   curl -H "Authorization: Bearer $TOKEN" /api/v1/catalog/categories
   # Deve retornar as 50 categorias hierarquizadas
   ```

---

## Próximo Step Recomendado

**Step 04 — Atributos e Grids de Construção** (Fase 3 itens 3.2 e 3.3):
- `ConstructionAttributeSeeder`: grupos de atributos técnicos (Dimensão, Bitola, Diâmetro, Volume, Cor, etc.)
- `ConstructionGridSeeder`: grids de variação para porcelanato (Dimensão), tinta (Volume), cabo (Bitola)
