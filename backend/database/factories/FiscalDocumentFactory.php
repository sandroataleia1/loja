<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Fiscal\Enums\FiscalDocumentStatusEnum;
use App\Modules\Fiscal\Enums\FiscalDocumentTypeEnum;
use App\Modules\Fiscal\Enums\FiscalProviderEnum;
use App\Modules\Fiscal\Models\FiscalDocument;
use App\Modules\Inventory\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FiscalDocument>
 */
final class FiscalDocumentFactory extends Factory
{
    protected $model = FiscalDocument::class;

    public function definition(): array
    {
        return [
            'store_id'         => Store::factory(),
            'sale_id'          => null,
            'type'             => FiscalDocumentTypeEnum::Nfce,
            'status'           => FiscalDocumentStatusEnum::Pending,
            'provider'         => FiscalProviderEnum::Stub,
            'access_key'       => null,
            'protocol'         => null,
            'xml_path'         => null,
            'pdf_path'         => null,
            'issued_at'        => null,
            'cancelled_at'     => null,
            'contingency_mode' => false,
            'error_message'    => null,
        ];
    }

    public function authorized(): static
    {
        return $this->state(fn (): array => [
            'status'     => FiscalDocumentStatusEnum::Authorized,
            'access_key' => str_pad((string) random_int(1, 99999999), 44, '0', STR_PAD_LEFT),
            'protocol'   => 'STUB-' . now()->format('YmdHis'),
            'xml_path'   => 'fiscal/test/document.xml',
            'issued_at'  => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status'        => FiscalDocumentStatusEnum::Rejected,
            'error_message' => '[SEFAZ] Rejeição 999: Erro de teste',
        ]);
    }

    public function error(): static
    {
        return $this->state([
            'status'        => FiscalDocumentStatusEnum::Error,
            'error_message' => 'Timeout de conexão com SEFAZ',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status'       => FiscalDocumentStatusEnum::Cancelled,
            'access_key'   => str_pad((string) random_int(1, 99999999), 44, '0', STR_PAD_LEFT),
            'issued_at'    => now()->subHour(),
            'cancelled_at' => now(),
        ]);
    }

    public function contingency(): static
    {
        return $this->state([
            'status'           => FiscalDocumentStatusEnum::Contingency,
            'contingency_mode' => true,
        ]);
    }
}
