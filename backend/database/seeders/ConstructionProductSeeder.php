<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\Enums\ProductStatusEnum;
use App\Modules\Catalog\Enums\ProductTypeEnum;
use App\Modules\Catalog\Enums\UnitOfMeasureEnum;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Variant;
use App\Shared\Actions\GenerateInternalCodeAction;
use App\Shared\Enums\SequenceEntityEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seed de produtos reais para ERP de Material de Construção.
 *
 * Cria 5 produtos exemplares com variantes e dados fiscais completos.
 * Depende de ConstructionCategorySeeder e ConstructionAttributeSeeder.
 * Idempotente: ignora produtos cujo slug já existe.
 */
final class ConstructionProductSeeder extends Seeder
{
    public function __construct(
        private readonly GenerateInternalCodeAction $generateCode,
    ) {}

    public function run(): void
    {
        $this->ensureTenantContext();

        foreach ($this->catalog() as $productData) {
            $this->upsertProduct($productData);
        }

        $this->command?->info('ConstructionProductSeeder: 5 produtos de construção criados.');
    }

    // ── Tenant ────────────────────────────────────────────────────────────────

    private function ensureTenantContext(): void
    {
        if (TenantContext::isSet()) {
            return;
        }

        $tenant = Tenant::where('slug', 'loja-demo')->first() ?? Tenant::first();

        if ($tenant === null) {
            throw new \RuntimeException(
                'ConstructionProductSeeder requer um tenant. Execute DatabaseSeeder primeiro.'
            );
        }

        TenantContext::set($tenant->uuid);
        $this->command?->info("ConstructionProductSeeder: usando tenant '{$tenant->name}'.");
    }

    // ── Core ──────────────────────────────────────────────────────────────────

    /** @param array<string, mixed> $data */
    private function upsertProduct(array $data): void
    {
        $slug = Str::slug($data['name']);

        if (Product::where('slug', $slug)->exists()) {
            return;
        }

        $brand    = $this->upsertBrand($data['brand']);
        $category = Category::where('slug', Str::slug($data['category']))->first();

        $code = $this->generateCode->execute(
            tenantId: TenantContext::getIdOrFail(),
            entity:   SequenceEntityEnum::Product,
        );

        /** @var Product $product */
        $product = Product::create([
            'code'            => $code,
            'name'            => $data['name'],
            'slug'            => $slug,
            'description'     => $data['description'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'type'            => ProductTypeEnum::Simple,
            'unit_of_measure' => $data['unit'],
            'status'          => ProductStatusEnum::Active,
            'brand_id'        => $brand->uuid,
            'base_price_cents' => (int) round($data['base_price'] * 100),
            'cost_price_cents' => isset($data['cost_price']) ? (int) round($data['cost_price'] * 100) : null,
            'ncm'             => $data['ncm'] ?? null,
            'cfop_default'    => $data['cfop'] ?? '5102',
            'origin_code'     => 0,
            'is_featured'     => false,
            'is_digital'      => false,
            'is_publishable'  => true,
        ]);

        if ($category !== null) {
            $product->categories()->attach($category->uuid, ['sort_order' => 10]);
        }

        $this->createVariant($product, $data);
    }

    /** @param array<string, mixed> $data */
    private function createVariant(Product $product, array $data): void
    {
        $variantCode = $this->generateCode->execute(
            tenantId: TenantContext::getIdOrFail(),
            entity:   SequenceEntityEnum::ProductVariant,
        );

        Variant::create([
            'product_id'   => $product->uuid,
            'code'         => $variantCode,
            'sku'          => $data['sku'],
            'name'         => $data['variant_name'] ?? $data['name'],
            'barcode'      => $data['barcode'] ?? null,
            'price_cents'  => (int) round($data['base_price'] * 100),
            'cost_cents'   => isset($data['cost_price']) ? (int) round($data['cost_price'] * 100) : null,
            'is_active'    => true,
            'is_default'   => true,
            'sort_order'   => 10,
            'ncm'          => $data['ncm'] ?? null,
            'cfop_default' => $data['cfop'] ?? '5102',
            'origin_code'  => 0,
        ]);
    }

    private function upsertBrand(string $name): Brand
    {
        $slug = Str::slug($name);
        $existing = Brand::where('slug', $slug)->first();
        if ($existing !== null) {
            return $existing;
        }

        $code = $this->generateCode->execute(
            tenantId: TenantContext::getIdOrFail(),
            entity:   SequenceEntityEnum::Brand,
        );

        return Brand::create([
            'code'      => $code,
            'name'      => $name,
            'slug'      => $slug,
            'is_active' => true,
        ]);
    }

    // ── Catálogo ──────────────────────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    private function catalog(): array
    {
        return [
            [
                'name'             => 'Cimento Portland CP-II Votoran 50kg',
                'short_description'=> 'Cimento Portland Composto CP-II-E-32, saco 50kg. Indicado para uso geral em argamassas, concreto simples e armado.',
                'description'      => 'O Cimento Portland Composto CP-II Votoran é um dos produtos mais utilizados na construção civil brasileira. Com adição de escória granulada de alto-forno (tipo E), oferece boa resistência inicial e excelente durabilidade. Recomendado para obras residenciais e comerciais de médio porte.',
                'brand'            => 'Votoran',
                'category'         => 'Cimento Portland',
                'unit'             => UnitOfMeasureEnum::SC,
                'sku'              => 'CIM-CPII-VOT-50',
                'barcode'          => '7891536015567',
                'variant_name'     => 'Cimento CP-II Votoran 50kg',
                'base_price'       => 39.90,
                'cost_price'       => 31.00,
                'ncm'              => '25232100',
                'cfop'             => '5102',
            ],
            [
                'name'             => 'Tubo PVC Soldável Amanco 25mm 6m',
                'short_description'=> 'Tubo PVC rígido soldável para água fria, DN 25mm, comprimento 6m. Pressão nominal 6 kgf/cm² (PN6). NBR 5648.',
                'description'      => 'Tubo PVC soldável Amanco para instalações de água fria. Fabricado conforme NBR 5648, com alta resistência a pressão e durabilidade. Ideal para instalações hidráulicas residenciais e comerciais. Cor azul para fácil identificação.',
                'brand'            => 'Amanco',
                'category'         => 'Tubos e Conexões PVC',
                'unit'             => UnitOfMeasureEnum::UN,
                'sku'              => 'TUB-PVC-25MM-6M',
                'barcode'          => '7891748010253',
                'variant_name'     => 'Tubo PVC 25mm 6m',
                'base_price'       => 18.90,
                'cost_price'       => 14.50,
                'ncm'              => '39172100',
                'cfop'             => '5102',
            ],
            [
                'name'             => 'Porcelanato Natural Formigres 60x60cm',
                'short_description'=> 'Porcelanato técnico natural 60x60cm, PEI 4, absorção < 0,5%. Ideal para pisos de alto tráfego.',
                'description'      => 'Porcelanato técnico Formigres na dimensão 60x60cm com acabamento natural. Alta resistência mecânica e à abrasão (PEI 4), indicado para pisos de alto tráfego em ambientes residenciais e comerciais. Produto conforme ABNT NBR 15463.',
                'brand'            => 'Formigres',
                'category'         => 'Porcelanato',
                'unit'             => UnitOfMeasureEnum::M2,
                'sku'              => 'POR-NAT-60X60-FOR',
                'barcode'          => '7896300000015',
                'variant_name'     => 'Porcelanato Natural 60x60',
                'base_price'       => 62.90,
                'cost_price'       => 48.00,
                'ncm'              => '69089010',
                'cfop'             => '5102',
            ],
            [
                'name'             => 'Tinta Acrílica Premium Suvinil 18L',
                'short_description'=> 'Tinta acrílica premium lavável para interiores e exteriores. Lata 18 litros. Rendimento: até 480 m² (2 demãos).',
                'description'      => 'A Tinta Acrílica Premium Suvinil é uma tinta de alta qualidade com cobertura superior e maior resistência às intempéries. Lavável, antimofo e com alta cobertura. Indicada para paredes internas e externas em alvenaria e concreto. Rendimento estimado de 480 m² em 2 demãos (lata 18L).',
                'brand'            => 'Suvinil',
                'category'         => 'Tinta Acrílica',
                'unit'             => UnitOfMeasureEnum::LT,
                'sku'              => 'TIN-ACR-SUV-18L',
                'barcode'          => '7891053026549',
                'variant_name'     => 'Tinta Suvinil Acrílica Premium 18L',
                'base_price'       => 289.90,
                'cost_price'       => 220.00,
                'ncm'              => '32091000',
                'cfop'             => '5102',
            ],
            [
                'name'             => 'Vergalhão CA-50 Belgo 10mm 12m',
                'short_description'=> 'Vergalhão de aço CA-50, bitola 10mm, comprimento 12 metros. NBR 7480. Uso estrutural em pilares, vigas e lajes.',
                'description'      => 'Vergalhão de aço nervurado CA-50 da marca Belgo, bitola 10mm e comprimento 12 metros. Produzido conforme ABNT NBR 7480, com alta resistência mecânica ao escoamento (500 MPa mínimo). Indicado para uso estrutural em pilares, vigas, lajes e fundações.',
                'brand'            => 'Belgo Bekaert',
                'category'         => 'Perfis e Cantoneiras',
                'unit'             => UnitOfMeasureEnum::UN,
                'sku'              => 'VER-CA50-10MM-12M',
                'barcode'          => '7896008300109',
                'variant_name'     => 'Vergalhão CA-50 10mm 12m',
                'base_price'       => 42.90,
                'cost_price'       => 33.00,
                'ncm'              => '72142000',
                'cfop'             => '5102',
            ],
        ];
    }
}
