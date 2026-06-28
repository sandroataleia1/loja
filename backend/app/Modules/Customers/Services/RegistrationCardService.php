<?php

declare(strict_types=1);

namespace App\Modules\Customers\Services;

use App\Core\Tenancy\Models\Tenant;
use App\Modules\Customers\Models\Customer;
use App\Modules\Settings\Services\CustomFieldService;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Gera a ficha de cadastro do cliente em PDF via dompdf.
 *
 * Preenchida: dados do cliente + campos custom.
 * Em branco:  formulário para preenchimento manual no balcão.
 */
final class RegistrationCardService
{
    public function __construct(private readonly CustomFieldService $customFields) {}

    /**
     * Gera PDF preenchido com os dados do cliente.
     *
     * @return \Illuminate\Http\Response
     */
    public function generatePdf(Customer $customer): mixed
    {
        $customer->loadMissing(['addresses', 'contacts', 'priceList', 'tags']);

        $tenant = Tenant::find($customer->tenant_id);

        $customFieldValues = $this->customFields->valuesForEntity(
            $customer->tenant_id,
            'customer',
            $customer->uuid,
        );

        $pdf = Pdf::loadView('customers.registration-card', [
            'customer'     => $customer,
            'tenant'       => $tenant,
            'customFields' => $customFieldValues,
            'blank'        => false,
            'date'         => now()->format('d/m/Y'),
        ])->setPaper('a4', 'portrait');

        $filename = 'ficha-'.str()->slug($customer->name ?? 'cliente').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Gera PDF em branco (formulário para preencher à mão no balcão).
     *
     * @return \Illuminate\Http\Response
     */
    public function generateBlankPdf(string $tenantId): mixed
    {
        $tenant = Tenant::find($tenantId);

        $pdf = Pdf::loadView('customers.registration-card', [
            'customer'     => null,
            'tenant'       => $tenant,
            'customFields' => [],
            'blank'        => true,
            'date'         => now()->format('d/m/Y'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('ficha-cadastro-em-branco.pdf');
    }

    /**
     * Gera HTML (mantido para compatibilidade — endpoint de impressão via browser).
     */
    public function generateHtml(Customer $customer): string
    {
        $customer->loadMissing(['addresses', 'contacts', 'priceList', 'tags']);

        $tenant = Tenant::find($customer->tenant_id);

        $customFieldValues = $this->customFields->valuesForEntity(
            $customer->tenant_id,
            'customer',
            $customer->uuid,
        );

        return view('customers.registration-card', [
            'customer'     => $customer,
            'tenant'       => $tenant,
            'customFields' => $customFieldValues,
            'blank'        => false,
            'date'         => now()->format('d/m/Y'),
        ])->render();
    }
}
