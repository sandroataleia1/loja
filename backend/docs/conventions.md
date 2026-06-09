# Convenções Técnicas — Store SaaS

> Documento normativo. Todo código novo deve seguir estas convenções sem exceção.
> Cada regra é derivada do código que já existe no projeto — nada aqui é teórico.

---

## Índice

1. [Nomenclatura](#1-nomenclatura)
2. [Rotas](#2-rotas)
3. [Actions](#3-actions)
4. [DTOs](#4-dtos)
5. [Resources](#5-resources)
6. [Enums](#6-enums)
7. [Events & Listeners](#7-events--listeners)
8. [Exceptions](#8-exceptions)
9. [Services](#9-services)
10. [Testes](#10-testes)
11. [Migrations](#11-migrations)
12. [Tenant](#12-tenant)
13. [UUID](#13-uuid)
14. [Logs](#14-logs)
15. [Auditoria](#15-auditoria)
16. [Filas](#16-filas)
17. [Versionamento da API](#17-versionamento-da-api)

---

## 1. Nomenclatura

### Sufixos obrigatórios por artefato

| Artefato | Sufixo | Exemplo real no projeto |
|---|---|---|
| Controller | `Controller` | `AuthController`, `ProductController` |
| Action | `Action` | `LoginAction`, `LogoutAction`, `CreateProductAction` |
| DTO | `DTO` | `LoginDTO`, `CreateProductDTO` |
| FormRequest | `Request` | `LoginRequest`, `StoreProductRequest` |
| Resource | `Resource` | `UserResource`, `ProductResource` |
| Enum | `Enum` | `StatusEnum`, `CurrencyEnum` |
| Exception | `Exception` | `BusinessException`, `ConflictException` |
| Event | `Event` | `ProductCreatedEvent` |
| Listener | `Listener` | `SyncProductToSearchIndexListener` |
| Job | `Job` | `GenerateSalesReportJob` |
| Observer | `Observer` | `AuditObserver` |
| Scope | `Scope` | `TenantScope` |
| Service | `Service` | `StockService`, `PricingService` |
| Middleware | sem sufixo | `ResolveTenant` |
| Factory (DB) | `Factory` | `UserFactory`, `ProductFactory`, `TenantFactory` |

### Namespaces e diretórios

```
app/Core/{Módulo}/{Camada}/     → App\Core\{Módulo}\{Camada}\
app/Modules/{Módulo}/{Camada}/  → App\Modules\{Módulo}\{Camada}\
app/Shared/{Camada}/            → App\Shared\{Camada}\
```

Camadas válidas dentro de cada módulo:
`Actions` · `DTOs` · `Enums` · `Events` · `Exceptions` · `Http/Controllers` · `Http/Requests` · `Http/Resources` · `Jobs` · `Listeners` · `Models` · `Observers` · `Scopes` · `Services`

### Métodos de Action — sempre `execute()`

```php
// CORRETO — todo Action expõe exatamente um método público
final readonly class CreateProductAction
{
    public function execute(CreateProductDTO $dto): Product { ... }
}

// ERRADO — nome inventado, múltiplos métodos públicos
final class ProductCreator
{
    public function create(...): Product { ... }
    public function createWithAttributes(...): Product { ... }
}
```

### Métodos de Controller — verbos REST canônicos

```
index    → listar coleção paginada
show     → exibir recurso único
store    → criar recurso
update   → atualizar recurso
destroy  → remover recurso
```

Ações fora do CRUD usam nomes descritivos curtos: `activate`, `deactivate`, `publish`, `cancel`.

---

## 2. Rotas

### Estrutura de arquivos

```
routes/
  api.php                 ← registra versões (v1, v2...)
  api/
    v1.php                ← agrupa módulos, define middlewares
    v1/
      catalog.php
      inventory.php
      customers.php
      sales.php
      finance.php
      pos.php
      crm.php
      reports.php
```

### Registro de versão — `routes/api.php`

```php
Route::prefix('v1')->name('v1.')->group(base_path('routes/api/v1.php'));
```

### Estrutura do `routes/api/v1.php`

```php
// Rotas públicas — antes do grupo autenticado
Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])->name('login');
});

// Todas as rotas autenticadas — auth:sanctum + tenant juntos, sempre
Route::middleware(['auth:sanctum', 'tenant'])->group(function (): void {
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me',     [AuthController::class, 'me'])->name('me');
    });

    Route::prefix('catalog')->name('catalog.')
        ->group(base_path('routes/api/v1/catalog.php'));
});
```

### Arquivo de módulo — `routes/api/v1/catalog.php`

```php
// Recurso CRUD completo
Route::apiResource('products', ProductController::class);

// Ação customizada fora do CRUD
Route::post('products/{product}/activate', [ProductController::class, 'activate'])
    ->name('products.activate');
```

### Regras

- Prefixo de versão **somente** em `routes/api.php` — nunca no controller
- `middleware(['auth:sanctum', 'tenant'])` **sempre juntos** — nunca separados
- Rotas públicas declaradas **fora e antes** do grupo autenticado
- Nunca definir middleware no construtor do controller
- Nomes de rota em `snake_case` — `catalog.products.index`, `auth.login`

---

## 3. Actions

### Regras

- `final readonly class` — sem herança, sem interface obrigatória
- Único método público: `execute()` — retorno fortemente tipado
- Recebe DTO, devolve entidade ou primitivo — nunca `JsonResponse`
- Lança exceções de domínio para sinalizar falhas de negócio
- Sem lógica de autorização (Policy), sem validação de campos (FormRequest)
- Dependências injetadas via construtor (readonly)

### Exemplo real — `app/Core/Auth/Actions/LoginAction.php`

```php
final readonly class LoginAction
{
    public function execute(LoginDTO $dto): array
    {
        $user = User::where('email', $dto->email)
            ->where('is_active', true)
            ->first();

        if ($user === null || ! Hash::check($dto->password, $user->password)) {
            throw new BusinessException('Invalid credentials.');
        }

        $user->tokens()->where('name', $dto->deviceName)->delete();
        $token = $user->createToken($dto->deviceName)->plainTextToken;
        $user->update(['last_login_at' => now()]);

        return ['token' => $token, 'user' => $user];
    }
}
```

### Exemplo real — `app/Modules/Catalog/Actions/CreateProductAction.php`

```php
final readonly class CreateProductAction
{
    public function execute(CreateProductDTO $dto): Product
    {
        if (Product::where('sku', $dto->sku)->exists()) {
            throw new ConflictException("SKU '{$dto->sku}' já está em uso neste tenant.");
        }

        return Product::create([
            'name'        => $dto->name,
            'sku'         => $dto->sku,
            'description' => $dto->description,
            'price'       => $dto->price,
            'cost'        => $dto->cost,
            'status'      => $dto->status ?? StatusEnum::Active,
            'attributes'  => $dto->attributes,
        ]);
    }
}
```

### Action com dependência de Service

```php
final readonly class CreateOrderAction
{
    public function __construct(
        private readonly StockService $stock,
    ) {}

    public function execute(CreateOrderDTO $dto): Order
    {
        foreach ($dto->items as $item) {
            $this->stock->reserve($item->productUuid, $item->quantity);
        }

        return Order::create($dto->toArray());
    }
}
```

### Como o Controller usa a Action

```php
// Injeção via parâmetro do método — Laravel resolve automaticamente
public function store(StoreProductRequest $request, CreateProductAction $action): JsonResponse
{
    $product = $action->execute(CreateProductDTO::fromRequest($request));

    return $this->created(new ProductResource($product));
}
```

---

## 4. DTOs

### Regras

- `final class` estendendo `BaseDTO`
- Propriedades `public readonly`, todas com tipo explícito
- `fromRequest()` obrigatório — sanitiza antes de atribuir
- Valores monetários em **centavos** (`int`) — nunca `float`
- Nunca validar dentro do DTO — a validação já ocorreu no `FormRequest`

### `BaseDTO` — `app/Shared/DTOs/BaseDTO.php`

```php
abstract class BaseDTO
{
    abstract public static function fromRequest(Request $request): static;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
```

`toArray()` é usado diretamente em `Model::create($dto->toArray())`.

### Exemplo real — `app/Core/Auth/DTOs/LoginDTO.php`

```php
final class LoginDTO extends BaseDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly string $deviceName,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            email:      $request->string('email')->lower()->trim()->toString(),
            password:   $request->string('password')->toString(),
            deviceName: $request->string('device_name', $request->userAgent() ?? 'api')->toString(),
        );
    }
}
```

### Exemplo real — `app/Modules/Catalog/DTOs/CreateProductDTO.php`

```php
final class CreateProductDTO extends BaseDTO
{
    public function __construct(
        public readonly string      $name,
        public readonly string      $sku,
        public readonly int         $price,       // centavos
        public readonly ?string     $description = null,
        public readonly ?int        $cost = null,  // centavos
        public readonly ?StatusEnum $status = null,
        public readonly ?array      $attributes = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            name:        $request->string('name')->trim()->toString(),
            sku:         $request->string('sku')->upper()->trim()->toString(),
            price:       $request->integer('price'),
            description: $request->string('description')->toString() ?: null,
            cost:        $request->has('cost') ? $request->integer('cost') : null,
            status:      $request->has('status')
                             ? StatusEnum::from($request->string('status')->toString())
                             : null,
            attributes:  $request->array('attributes') ?: null,
        );
    }
}
```

### Dinheiro — sempre centavos

```php
// CORRETO — preço em centavos, tipo int
public readonly int $price;   // 5990 = R$ 59,90

// ERRADO — ponto flutuante é impreciso para dinheiro
public readonly float $price; // 59.90 → erros de arredondamento
```

Para operações monetárias complexas, usar o `Money` Value Object:

```php
// app/Shared/ValueObjects/Money.php
$total = Money::fromCents(5990)->multiply(1.1);  // com 10% de markup
$total->amount();   // 6589 (centavos)
$total->format();   // "65,89"
```

---

## 5. Resources

### Regras

- `final class` estendendo `JsonResource`
- Lista explícita de campos — nunca `$this->resource->toArray()` ou `parent::toArray()`
- UUID exposto como `uuid`, nunca como `id`
- Status Enum: expor `value` (string) e `label()` (tradução) separados
- Campos monetários: expor centavos (`price`) — o front converte se necessário
- Timestamps em ISO 8601: `->toIso8601String()`
- Relacionamentos: `$this->whenLoaded('relation', ...)`

### Exemplo real — `app/Modules/Catalog/Http/Resources/ProductResource.php`

```php
final class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'         => $this->uuid,
            'name'         => $this->name,
            'sku'          => $this->sku,
            'description'  => $this->description,
            'price'        => $this->price,            // centavos
            'price_float'  => $this->price / 100,      // R$ para exibição
            'cost'         => $this->cost,
            'status'       => $this->status?->value,   // "active"
            'status_label' => $this->status?->label(), // "Ativo"
            'attributes'   => $this->attributes,
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }
}
```

### Exemplo real — `app/Core/Auth/Http/Resources/UserResource.php`

```php
final class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'      => $this->uuid,
            'name'      => $this->name,
            'email'     => $this->email,
            'role'      => $this->role,
            'is_active' => $this->is_active,
        ];
    }
}
```

### Resposta paginada no Controller

```php
public function index(): JsonResponse
{
    $products = Product::latest()->paginate(20);

    return $this->success(
        data: ProductResource::collection($products),
        meta: [
            'current_page' => $products->currentPage(),
            'per_page'     => $products->perPage(),
            'total'        => $products->total(),
            'last_page'    => $products->lastPage(),
        ],
    );
}
```

---

## 6. Enums

### Regras

- Sempre `enum NomeEnum: string` — backed por string, nunca por int
- Método `label()` obrigatório para enums que são exibidos ao usuário
- Métodos de estado (`isActive()`, `isFinal()`, `canTransitionTo()`) dentro do Enum
- Enums compartilhados entre módulos → `app/Shared/Enums/`
- Enums específicos de módulo → `app/Modules/{Módulo}/Enums/`
- Cast no model via `protected function casts()`
- Validação no FormRequest via `Rule::enum(StatusEnum::class)`

### Exemplo real — `app/Shared/Enums/StatusEnum.php`

```php
enum StatusEnum: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
    case Pending  = 'pending';
    case Archived = 'archived';

    public function label(): string
    {
        return match($this) {
            self::Active   => 'Ativo',
            self::Inactive => 'Inativo',
            self::Pending  => 'Pendente',
            self::Archived => 'Arquivado',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
```

### Enum com transições de estado

```php
// app/Modules/Sales/Enums/OrderStatusEnum.php
enum OrderStatusEnum: string
{
    case Pending   = 'pending';
    case Confirmed = 'confirmed';
    case Shipped   = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function isFinal(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled], true);
    }

    public function canTransitionTo(self $next): bool
    {
        return match($this) {
            self::Pending   => $next === self::Confirmed || $next === self::Cancelled,
            self::Confirmed => $next === self::Shipped   || $next === self::Cancelled,
            self::Shipped   => $next === self::Delivered,
            default         => false,
        };
    }
}
```

### Cast no model e validação no FormRequest

```php
// Model
protected function casts(): array
{
    return array_merge(parent::casts(), [
        'status' => StatusEnum::class,
    ]);
}

// FormRequest
'status' => ['sometimes', Rule::enum(StatusEnum::class)],
```

---

## 7. Events & Listeners

### Regras

- Event: `final readonly class`, nome no **passado**, sufixo `Event`
- Listener: `final class`, implementa `ShouldQueue`, sufixo `Listener`
- Nenhuma lógica de negócio no Event — apenas estado transportado
- Todo Listener que faz I/O implementa `ShouldQueue`
- Declarar `public string $queue` no Listener para roteamento explícito

### Estrutura de Event

```php
// app/Modules/Catalog/Events/ProductCreatedEvent.php
final readonly class ProductCreatedEvent
{
    public function __construct(
        public readonly Product $product,
    ) {}
}
```

### Estrutura de Listener

```php
// app/Modules/Catalog/Listeners/NotifyLowStockListener.php
final class NotifyLowStockListener implements ShouldQueue
{
    public string $queue = 'notifications';
    public int    $tries = 3;

    public function handle(ProductCreatedEvent $event): void
    {
        // lógica assíncrona aqui
    }

    public function failed(ProductCreatedEvent $event, \Throwable $e): void
    {
        Log::channel('stack')->error('Falha no listener', [
            'listener'     => self::class,
            'product_uuid' => $event->product->uuid,
            'error'        => $e->getMessage(),
        ]);
    }
}
```

### Disparo — dentro da Action, após persistência

```php
final readonly class CreateProductAction
{
    public function execute(CreateProductDTO $dto): Product
    {
        $product = Product::create([...]);

        event(new ProductCreatedEvent($product));  // após salvar, nunca antes

        return $product;
    }
}
```

---

## 8. Exceptions

### Hierarquia

```
app/Shared/Exceptions/
  AppException.php          (abstract) — base de todas as exceções de domínio
  BusinessException.php     422 — regra de negócio violada
  NotFoundException.php     404 — recurso não encontrado
  ForbiddenException.php    403 — acesso negado a recurso existente
  ConflictException.php     409 — conflito de estado (duplicata, transição inválida)
```

### `AppException` — `app/Shared/Exceptions/AppException.php`

```php
abstract class AppException extends Exception
{
    public function __construct(
        string $message = '',
        private readonly array $errors = [],
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    abstract public function httpStatus(): int;

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors'  => $this->errors,
        ], $this->httpStatus());
    }
}
```

### Quando usar cada exceção

| Situação | Exceção | HTTP |
|---|---|---|
| Regra de negócio impediu a operação | `BusinessException` | 422 |
| Entidade não encontrada ou fora do tenant | `NotFoundException` | 404 |
| Usuário não tem permissão para o recurso | `ForbiddenException` | 403 |
| SKU/slug/email já existe (unicidade) | `ConflictException` | 409 |
| Transição de estado inválida (pedido já cancelado) | `ConflictException` | 409 |

### Uso na Action

```php
// 404 — produto não encontrado (ou de outro tenant)
$product = Product::find($uuid)
    ?? throw new NotFoundException("Produto '{$uuid}' não encontrado.");

// 409 — SKU já em uso no tenant
if (Product::where('sku', $dto->sku)->exists()) {
    throw new ConflictException("SKU '{$dto->sku}' já está em uso neste tenant.");
}

// 422 — regra de negócio
if ($product->status->isFinal()) {
    throw new BusinessException('Produtos arquivados não podem ser editados.');
}

// 403 — acesso negado
if ($user->role !== 'admin') {
    throw new ForbiddenException('Apenas administradores podem realizar esta operação.');
}
```

### O que NUNCA usar nas Actions

```php
// PROIBIDO
throw new \Exception('...');
throw new \RuntimeException('...');
abort(422, '...');
response()->json(['success' => false, ...], 422);
```

### Como as exceções chegam ao cliente

O handler em `bootstrap/app.php` captura todo `AppException` e chama `render()`:

```php
$exceptions->render(function (AppException $e, Request $request): JsonResponse {
    return $e->render();
});
```

Resposta resultante:
```json
{ "success": false, "message": "SKU 'CAM-001' já está em uso neste tenant.", "errors": {} }
```

---

## 9. Services

### Quando criar um Service

Um Service existe **exclusivamente** para lógica reutilizada por múltiplas Actions. Se a lógica é usada por uma única Action, ela fica na própria Action.

| Lógica usada em | Onde fica |
|---|---|
| Uma Action | Dentro da própria Action |
| Duas ou mais Actions | `Service` injetado nas Actions |

### Estrutura

```php
// app/Modules/Inventory/Services/StockService.php
final readonly class StockService
{
    public function reserve(string $productUuid, int $quantity): void
    {
        $stock = StockItem::where('product_uuid', $productUuid)
            ->lockForUpdate()
            ->firstOrFail();

        if ($stock->available < $quantity) {
            throw new BusinessException(
                "Estoque insuficiente para o produto '{$productUuid}'."
            );
        }

        $stock->decrement('available', $quantity);
        $stock->increment('reserved', $quantity);
    }

    public function release(string $productUuid, int $quantity): void
    {
        StockItem::where('product_uuid', $productUuid)
            ->increment('available', $quantity);
        StockItem::where('product_uuid', $productUuid)
            ->decrement('reserved', $quantity);
    }
}
```

### Injeção via construtor readonly

```php
final readonly class CreateOrderAction
{
    public function __construct(
        private readonly StockService $stock,
    ) {}

    public function execute(CreateOrderDTO $dto): Order
    {
        foreach ($dto->items as $item) {
            $this->stock->reserve($item->productUuid, $item->quantity);
        }
        return Order::create($dto->toArray());
    }
}
```

---

## 10. Testes

### Stack

- **Pest** — nunca PHPUnit diretamente
- `RefreshDatabase` aplicado globalmente em `Feature` via `tests/Pest.php`
- `actingAsTenantUser()` para todo teste que precise de autenticação
- Banco PostgreSQL real (`store_test`) — nunca mockar banco

### `tests/Pest.php`

```php
uses(Tests\TestCase::class)->in('Feature', 'Unit');
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class)->in('Feature');

// Custom expectations disponíveis em todos os testes
expect()->extend('toBeApiSuccess', function (): \Pest\Expectation {
    return $this->toHaveKey('success', true);
});
expect()->extend('toBeApiError', function (): \Pest\Expectation {
    return $this->toHaveKey('success', false);
});
```

### Estrutura de diretórios

```
tests/
  Feature/
    Auth/
      AuthTest.php
    Catalog/
      ProductTest.php
    Sales/
      OrderTest.php
  Unit/
    Shared/
      MoneyTest.php
    Modules/
      Catalog/
        CreateProductActionTest.php
```

### Padrão Feature — `tests/Feature/Catalog/ProductTest.php`

```php
describe('Criar produto', function (): void {

    it('cria produto com dados mínimos', function (): void {
        $this->actingAsTenantUser();

        $response = $this->postJson('/api/v1/catalog/products', [
            'name'  => 'Camiseta Básica',
            'sku'   => 'CAM-001',
            'price' => 5990,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.sku', 'CAM-001')
            ->assertJsonPath('data.status', StatusEnum::Active->value);

        $this->assertDatabaseHas('products', ['sku' => 'CAM-001']);
    });

    it('rejeita SKU duplicado no mesmo tenant com 409', function (): void {
        $this->actingAsTenantUser();
        Product::factory()->create(['sku' => 'CAM-001']);

        $this->postJson('/api/v1/catalog/products', [
            'name' => 'Outro', 'sku' => 'CAM-001', 'price' => 4990,
        ])->assertConflict()->assertJsonPath('success', false);
    });

    it('permite SKU igual em tenants diferentes', function (): void {
        $this->actingAsTenantUser(Tenant::factory()->create());
        Product::factory()->create(['sku' => 'CAM-001']);

        $this->actingAsTenantUser(Tenant::factory()->create());

        $this->postJson('/api/v1/catalog/products', [
            'name' => 'Camiseta', 'sku' => 'CAM-001', 'price' => 4990,
        ])->assertCreated();
    });

    it('requer autenticação', function (): void {
        $this->postJson('/api/v1/catalog/products', [])->assertUnauthorized();
    });

});
```

### Padrão Unit — `tests/Unit/Shared/MoneyTest.php`

```php
it('cria money a partir de float', function (): void {
    $money = Money::fromFloat(59.90);

    expect($money->amount())->toBe(5990)
        ->and($money->toFloat())->toBe(59.9);
});

it('lança exceção para valor negativo', function (): void {
    expect(fn () => new Money(-1))->toThrow(\InvalidArgumentException::class);
});
```

### Regras obrigatórias

- Cada `describe` agrupa testes de uma **operação** do ponto de vista do usuário
- Nome do teste descreve comportamento: `retorna 404 para UUID inexistente`
- Sempre testar: caso feliz + violação de negócio + isolamento de tenant + falta de autenticação
- Nunca mockar o banco — usar banco real `store_test` via Docker
- Factories nunca recebem `tenant_id` diretamente — usar `forTenant($tenant)`

### `actingAsTenantUser` — `tests/TestCase.php`

```php
protected function actingAsTenantUser(?Tenant $tenant = null): static
{
    $this->tenant = $tenant ?? Tenant::factory()->create();
    $this->user   = User::factory()->forTenant($this->tenant)->create();
    TenantContext::set($this->tenant->uuid);
    return $this->actingAs($this->user);
}
```

---

## 11. Migrations

### Ordenação por prefixo de data

```
0000_00_00_000000_create_tenants_table.php   ← sempre primeiro (sem FK)
0001_01_01_000000_create_users_table.php     ← depende de tenants
0001_01_01_000001_create_cache_table.php
0001_01_01_000002_create_jobs_table.php
2024_01_01_000001_create_audit_logs_table.php
2024_01_01_000002_create_products_table.php
YYYY_MM_DD_HHMMSS_create_{tabela}_table.php      ← novas entidades
YYYY_MM_DD_HHMMSS_add_{coluna}_to_{tabela}.php   ← adicionar coluna
YYYY_MM_DD_HHMMSS_drop_{coluna}_from_{tabela}.php
```

### Template obrigatório para entidades de domínio

```php
Schema::create('orders', function (Blueprint $table): void {
    // 1. PK — sempre UUID
    $table->uuid()->primary();

    // 2. FK de tenant — sempre segunda coluna
    $table->foreignUuid('tenant_id')
          ->constrained('tenants', 'uuid')
          ->cascadeOnDelete();

    // 3. FKs para outras entidades (se houver)
    $table->foreignUuid('customer_uuid')
          ->constrained('customers', 'uuid');

    // 4. Colunas de negócio
    $table->string('status', 30)->default('pending');
    $table->integer('total')->default(0);         // centavos
    $table->jsonb('metadata')->nullable();

    // 5. Timestamps e soft delete — sempre ao final
    $table->timestamps();
    $table->softDeletes();

    // 6. Índices — unique primeiro, depois índices de busca
    $table->unique(['tenant_id', 'order_number']);
    $table->index(['tenant_id', 'status']);
    $table->index(['tenant_id', 'created_at']);
});
```

### Regras

| Regra | Razão |
|---|---|
| `uuid()->primary()` em toda entidade de domínio | PKs expostas ao front devem ser UUIDs |
| `tenant_id` como segunda coluna com FK | Toda entidade pertence a um tenant |
| `integer` para dinheiro (centavos) | `decimal/float` têm erros de arredondamento |
| `jsonb` para JSON semiestruturado | PostgreSQL indexa e filtra `jsonb` nativamente |
| `softDeletes()` em toda entidade | Dados de varejo nunca são apagados fisicamente |
| Todo índice começa com `tenant_id` | Queries são sempre filtradas por tenant |
| `$table->id()` proibido em entidades de domínio | Reservado para tabelas de infraestrutura |

---

## 12. Tenant

### Fluxo de resolução

```
Request
  ↓
auth:sanctum  →  $request->user() disponível
  ↓
ResolveTenant middleware
  ├─ lê tenant_id do usuário autenticado
  ├─ valida que o tenant existe e está ativo
  └─ TenantContext::set($tenant->uuid)
       ↓
       BelongsToTenant trait → TenantScope ativo em todas as queries
```

### `TenantContext` — `app/Core/Tenancy/Services/TenantContext.php`

Singleton estático. Fonte única da verdade para o tenant da requisição corrente.

```php
TenantContext::set($uuid);       // definir — só o middleware faz isso
TenantContext::getId();          // ler (nullable) — para código que tolera ausência
TenantContext::getIdOrFail();    // ler (throws) — dentro de Models ao criar
TenantContext::clear();          // limpar — middleware de saída e tearDown de testes
TenantContext::isSet();          // verificar se está setado
```

### `BelongsToTenant` — injeção automática de `tenant_id`

```php
// O trait faz isso em todos os models que estendem BaseModel:
static::creating(function (Model $model): void {
    if (empty($model->tenant_id)) {
        $model->tenant_id = TenantContext::getIdOrFail();
    }
});
```

### `TenantScope` — macros disponíveis

```php
// Escopo padrão — ativo automaticamente, filtra por tenant
Product::where('status', 'active')->get();
// SQL: WHERE tenant_id = ? AND status = ?

// Remover escopo (jobs cross-tenant, reports de admin)
Product::withoutTenantScope()->where('status', 'active')->get();

// Query explícita para tenant específico (jobs em background)
Product::forTenant($tenantId)->where('status', 'active')->get();
```

### Regras absolutas

- **Nunca** aceitar `tenant_id` do corpo da request ou query string
- **Nunca** passar `tenant_id` manualmente em `Model::create()` — o trait injeta
- **Nunca** chamar `TenantContext::set()` dentro de Action ou Controller
- **Sempre** usar `try/finally` com `TenantContext::clear()` em Jobs

### Em Jobs — `TenantContext` obrigatório

```php
final class ProcessTenantReportJob implements ShouldQueue
{
    public function __construct(
        private readonly string $tenantId,  // passado no dispatch, nunca lido do contexto
        private readonly string $period,
    ) {}

    public function handle(): void
    {
        TenantContext::set($this->tenantId);

        try {
            // lógica do job — TenantScope está ativo
        } finally {
            TenantContext::clear();  // sempre, mesmo em caso de exceção
        }
    }
}
```

---

## 13. UUID

### Onde usar

- PK de toda entidade de domínio (todos os models que estendem `BaseModel`)
- PK de `Tenant` e `User` (que não estendem `BaseModel`)
- Identificador exposto ao cliente — nunca expor IDs numéricos

### `HasUuid` trait — `app/Shared/Traits/HasUuid.php`

```php
trait HasUuid
{
    protected static function bootHasUuid(): void
    {
        static::creating(function (Model $model): void {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function getIncrementing(): bool { return false; }
    public function getKeyType(): string    { return 'string'; }
}
```

### Configuração no model

```php
// BaseModel já faz isso para todas as entidades de domínio
protected $primaryKey = 'uuid';
```

### Route model binding — funciona automaticamente

`BaseModel` define `$primaryKey = 'uuid'`. O `getRouteKeyName()` do Eloquent retorna a PK, então o binding já usa `uuid` como chave de lookup.

```php
// routes/api/v1/catalog.php
Route::apiResource('products', ProductController::class);
// Laravel resolve {product} usando Product::where('uuid', $value)->first()

// Controller — injeção direta, sem lookup manual
public function show(Product $product): JsonResponse
{
    return $this->success(new ProductResource($product));
}
// O TenantScope garante que só produtos do tenant autenticado são resolvíveis
// UUID de outro tenant → 404 automático
```

### Migration

```php
// PK
$table->uuid()->primary();

// FK para outra entidade de domínio — sempre referenciar a coluna uuid
$table->foreignUuid('product_uuid')->constrained('products', 'uuid');
$table->foreignUuid('customer_uuid')->constrained('customers', 'uuid');
```

---

## 14. Logs

### Canais — `config/logging.php`

| Canal | Arquivo | Retenção | Quando usar |
|---|---|---|---|
| `stack` (default) | `storage/logs/laravel.log` | 14 dias | Erros de aplicação em geral |
| `audit` | `storage/logs/audit/audit.log` | 90 dias | Registros de auditoria |
| `slow_queries` | `storage/logs/slow-queries.log` | 7 dias | Queries > threshold (ms) |
| `stderr` | stdout/stderr do processo | — | Containers Docker |

### Slow query log — configurado em `AppServiceProvider`

```php
// Ativo apenas em produção — threshold via env DB_SLOW_QUERY_MS (padrão: 1000ms)
DB::listen(function (QueryExecuted $event) use ($threshold): void {
    if ($event->time >= $threshold) {
        Log::channel('slow_queries')->warning('Slow query detected', [
            'sql'      => $event->sql,
            'bindings' => $event->bindings,
            'time_ms'  => $event->time,
        ]);
    }
});
```

### Regras de contexto

```php
// CORRETO — contexto sempre inclui tenant_id e UUID da entidade
Log::channel('stack')->error('Falha na integração com gateway de pagamento', [
    'tenant_id'  => TenantContext::getId(),
    'order_uuid' => $order->uuid,
    'gateway'    => 'pagarme',
    'error'      => $e->getMessage(),
]);

// ERRADO — sem contexto, impossível rastrear
Log::error('Falha no pagamento');
```

### Níveis corretos

| Nível | Uso |
|---|---|
| `debug` | Rastreamento de fluxo interno (desabilitado em produção) |
| `info` | Eventos de negócio relevantes (pedido criado, pagamento aprovado) |
| `warning` | Degradação não-fatal (query lenta, retry de job) |
| `error` | Falha recuperável (integração externa, job falhado) |
| `critical` | Falha irrecuperável (banco indisponível, config corrompida) |

### Proibido

```php
// PROIBIDO — nunca logar dados sensíveis
Log::info('Login', ['password' => $dto->password]);
Log::info('Token', ['token' => $plainTextToken]);
```

---

## 15. Auditoria

### Como funciona

Todo model que estende `BaseModel` tem o trait `Auditable`, que registra o `AuditObserver`. O observer grava automaticamente `created`, `updated` e `deleted` na tabela `audit_logs`.

```
Model estende BaseModel
  → trait Auditable
    → bootAuditable() → static::observe(AuditObserver::class)
      → AuditObserver::created/updated/deleted
        → AuditLog::create([...])
```

### O que é gravado

```php
// created → estado inicial completo
['event' => 'created', 'old_values' => [], 'new_values' => $model->getAttributes()]

// updated → apenas o diff (antes e depois)
['event' => 'updated', 'old_values' => $model->getOriginal(), 'new_values' => $model->getChanges()]

// deleted → estado antes da exclusão
['event' => 'deleted', 'old_values' => $model->getAttributes(), 'new_values' => []]
```

### Excluir campos sensíveis

Declarar `$auditExclude` no model. O `AuditObserver` filtra automaticamente.

```php
// app/Core/Auth/Models/User.php
final class User extends Authenticatable
{
    // Campos nunca aparecem em old_values/new_values no audit_logs
    public array $auditExclude = ['password', 'remember_token'];
}
```

### Schema de `audit_logs`

```
id              bigserial PK
tenant_id       uuid nullable  → tenant do registro
user_id         uuid nullable  → quem executou a ação
event           varchar(50)    → 'created' | 'updated' | 'deleted'
auditable_type  varchar        → FQCN da classe (ex: App\Modules\Catalog\Models\Product)
auditable_id    varchar        → UUID da entidade
old_values      jsonb nullable → estado anterior
new_values      jsonb nullable → estado posterior / diff
ip_address      varchar(45)
user_agent      text
created_at      timestamp
```

### Consultar histórico de uma entidade

```php
AuditLog::where('auditable_type', Product::class)
    ->where('auditable_id', $product->uuid)
    ->orderByDesc('created_at')
    ->get();
```

---

## 16. Filas

### Supervisores Horizon — `config/horizon.php`

| Supervisor | Queues | Workers | Timeout | Tries | Uso |
|---|---|---|---|---|---|
| `supervisor-default` | `default` | 2 | 90s | 3 | Actions assíncronas gerais |
| `supervisor-notifications` | `notifications`, `emails` | 2 | 30s | 5 | Emails, push, SMS |
| `supervisor-reports` | `reports` | 1 | 600s | 1 | Relatórios e exportações pesadas |

### Estrutura de Job

```php
// app/Modules/Reports/Jobs/GenerateSalesReportJob.php
final class GenerateSalesReportJob implements ShouldQueue
{
    use Queueable;

    public string $queue   = 'reports';
    public int    $tries   = 1;
    public int    $timeout = 600;

    public function __construct(
        private readonly string $tenantId,  // sempre passado no dispatch
        private readonly string $period,    // 'YYYY-MM'
    ) {}

    public function handle(): void
    {
        TenantContext::set($this->tenantId);

        try {
            // lógica do relatório — TenantScope ativo
        } finally {
            TenantContext::clear();
        }
    }

    public function failed(\Throwable $e): void
    {
        TenantContext::clear();

        Log::channel('stack')->error('Falha ao gerar relatório de vendas', [
            'tenant_id' => $this->tenantId,
            'period'    => $this->period,
            'error'     => $e->getMessage(),
        ]);
    }
}
```

### Listener como Job

```php
final class SyncProductToSearchIndexListener implements ShouldQueue
{
    public string $queue = 'default';
    public int    $tries = 3;

    public function handle(ProductCreatedEvent $event): void
    {
        // I/O assíncrono
    }

    public function failed(ProductCreatedEvent $event, \Throwable $e): void
    {
        Log::channel('stack')->error('Falha ao indexar produto', [
            'product_uuid' => $event->product->uuid,
            'error'        => $e->getMessage(),
        ]);
    }
}
```

### Dispatch dentro de transação

```php
// Nunca despachar job dentro de DB::transaction diretamente
// O job pode disparar antes do commit
DB::transaction(function () use ($order): void {
    $order->save();

    DB::afterCommit(function () use ($order): void {
        GenerateInvoiceJob::dispatch($order->tenant_id, $order->uuid);
    });
});
```

### Regras

- `tenantId` sempre passado como argumento no construtor — nunca lido de `TenantContext`
- `try/finally` com `TenantContext::clear()` obrigatório em todo Job
- Jobs de relatórios e exportações → queue `reports`
- Jobs de comunicação (email, push) → queue `notifications`
- `failed()` obrigatório em Jobs longos ou críticos

---

## 17. Versionamento da API

### Versão ativa

```
/api/v1/...
```

### Adicionar v2

1. Criar `routes/api/v2.php`
2. Registrar em `routes/api.php`:

```php
Route::prefix('v1')->name('v1.')->group(base_path('routes/api/v1.php'));
Route::prefix('v2')->name('v2.')->group(base_path('routes/api/v2.php'));
```

Controllers, Resources e DTOs são **duplicados por versão** — nunca compartilhados entre versões diferentes.

### Formato de resposta — imutável entre versões

```jsonc
// Sucesso com dado único
{ "success": true, "data": { "uuid": "...", "name": "..." } }

// Sucesso com coleção paginada
{
  "success": true,
  "data": [ ... ],
  "meta": { "current_page": 1, "per_page": 20, "total": 42, "last_page": 3 }
}

// Sucesso sem corpo (204)
// body vazio — logout, delete

// Erro de validação (422)
{ "success": false, "message": "Os dados informados são inválidos.", "errors": { "sku": ["..."] } }

// Erro de domínio (409, 404, 403, 422)
{ "success": false, "message": "SKU 'CAM-001' já está em uso.", "errors": {} }
```

`success` é obrigatório em **todas** as respostas exceto 204.

### O que é breaking change

| Breaking — requer nova versão | Non-breaking — pode ir na versão atual |
|---|---|
| Remover ou renomear campo do response | Adicionar campo novo ao response |
| Mudar tipo de campo (`string` → `int`) | Adicionar endpoint novo |
| Mudar código HTTP de sucesso | Adicionar parâmetro opcional ao request |
| Renomear chave no JSON | Expandir Enum com valor novo |
| Remover endpoint | Adicionar `meta` ao response |

### Deprecação de versão

```php
// Adicionar cabeçalhos de deprecação na versão sendo descontinuada
return response()
    ->json($payload)
    ->header('Deprecation', 'true')
    ->header('Sunset', 'Sat, 31 Dec 2026 23:59:59 GMT')
    ->header('Link', '</api/v2/catalog/products>; rel="successor-version"');
```

Versões nunca são removidas sem aviso prévio de mínimo **6 meses**.
