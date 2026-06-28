<?php

declare(strict_types=1);

namespace App\Modules\Customers\Services;

use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\CustomerAuthorizedBuyer;
use App\Modules\Customers\Models\CustomerGuarantor;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;

final class CreditContractService
{
    public function generateContract(Customer $customer, ?CustomerGuarantor $guarantor = null): string
    {
        $customer->loadMissing([
            'addresses', 'contacts', 'guarantors', 'assets', 'cards',
            'bankReferences', 'commercialReferences', 'authorizedBuyers',
        ]);

        $settings  = $this->tenantSettings($customer->tenant_id);
        $activeBuyers = $customer->authorizedBuyers
            ->filter(fn (CustomerAuthorizedBuyer $b) => $b->isValid())
            ->values();

        $html = View::make('customers.credit-contract', compact(
            'customer', 'guarantor', 'settings', 'activeBuyers',
        ))->render();

        return $this->renderPdf($html);
    }

    public function generateBlankContract(): string
    {
        $settings = ['contract_city' => '', 'interest_rate' => '1,00', 'fine_rate' => '2,00'];
        $html     = View::make('customers.credit-contract-blank', compact('settings'))->render();

        return $this->renderPdf($html);
    }

    private function tenantSettings(string $tenantId): array
    {
        // Lê configurações do tenant. Fallback para padrões da Alves Shopping.
        return [
            'contract_city'          => 'Parauapebas',
            'contract_interest_rate' => '1,00',
            'contract_fine_rate'     => '2,00',
            'contract_credit_days'   => 30,
        ];
    }

    private function renderPdf(string $html): string
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
