<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Services\CreditContractService;
use App\Modules\Customers\Services\RegistrationCardService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * GET /api/v1/customers/{customer}/registration-card   → PDF preenchido
 * GET /api/v1/customers/registration-card/blank        → PDF em branco
 * GET /api/v1/customers/{customer}/contract            → Contrato de crediário preenchido
 * GET /api/v1/customers/{customer}/contract/blank      → Contrato em branco
 * GET /api/v1/customers/contract/template              → Somente o contrato (sem dados)
 */
final class RegistrationCardController extends Controller
{
    public function __construct(
        private readonly RegistrationCardService $service,
        private readonly CreditContractService $contractService,
    ) {}

    public function __invoke(Customer $customer): Response
    {
        return $this->service->generatePdf($customer);
    }

    public function blank(Request $request): Response
    {
        $tenantId = TenantContext::getIdOrFail();

        return $this->service->generateBlankPdf($tenantId);
    }

    public function contract(Customer $customer): Response
    {
        $customer->loadMissing(['guarantors', 'authorizedBuyers', 'assets', 'cards', 'bankReferences']);
        $guarantor = $customer->guarantors->first();
        $pdf       = $this->contractService->generateContract($customer, $guarantor);

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="contrato-' . $customer->code . '.pdf"',
        ]);
    }

    public function contractBlank(): Response
    {
        $pdf = $this->contractService->generateBlankContract();

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="contrato-crediario-em-branco.pdf"',
        ]);
    }

    public function contractTemplate(): Response
    {
        return $this->contractBlank();
    }
}
