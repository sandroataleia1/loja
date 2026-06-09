<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Http\Controllers;

use App\Modules\Fiscal\Actions\CancelFiscalDocumentAction;
use App\Modules\Fiscal\Actions\RequestFiscalDocumentAction;
use App\Modules\Fiscal\DTOs\CancelFiscalDocumentDTO;
use App\Modules\Fiscal\DTOs\RequestFiscalDocumentDTO;
use App\Modules\Fiscal\Http\Requests\CancelFiscalDocumentRequest;
use App\Modules\Fiscal\Http\Requests\RequestFiscalDocumentRequest;
use App\Modules\Fiscal\Http\Resources\FiscalDocumentResource;
use App\Modules\Fiscal\Jobs\ProcessFiscalDocumentJob;
use App\Modules\Fiscal\Models\FiscalDocument;
use App\Shared\Exceptions\BusinessException;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class FiscalDocumentController extends Controller
{
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $documents = FiscalDocument::query()
            ->when($request->string('sale_id')->value(), fn ($q, $v) => $q->where('sale_id', $v))
            ->when($request->string('store_id')->value(), fn ($q, $v) => $q->where('store_id', $v))
            ->when($request->string('status')->value(), fn ($q, $v) => $q->where('status', $v))
            ->when($request->string('type')->value(), fn ($q, $v) => $q->where('type', $v))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            data: FiscalDocumentResource::collection($documents),
            meta: [
                'current_page' => $documents->currentPage(),
                'per_page'     => $documents->perPage(),
                'total'        => $documents->total(),
                'last_page'    => $documents->lastPage(),
            ],
        );
    }

    public function store(RequestFiscalDocumentRequest $request, RequestFiscalDocumentAction $action): JsonResponse
    {
        $this->authorize('emitManualFiscal', FiscalDocument::class);

        $document = $action->execute(RequestFiscalDocumentDTO::fromRequest($request));

        return $this->created(new FiscalDocumentResource($document));
    }

    public function show(FiscalDocument $fiscalDocument): JsonResponse
    {
        return $this->success(new FiscalDocumentResource($fiscalDocument));
    }

    public function cancel(CancelFiscalDocumentRequest $request, FiscalDocument $fiscalDocument, CancelFiscalDocumentAction $action): JsonResponse
    {
        $this->authorize('cancel', $fiscalDocument);

        $document = $action->execute($fiscalDocument, CancelFiscalDocumentDTO::fromRequest($request)->reason);

        return $this->success(new FiscalDocumentResource($document));
    }

    public function retry(FiscalDocument $fiscalDocument): JsonResponse
    {
        $this->authorize('retry', $fiscalDocument);

        if (! $fiscalDocument->status->canRetry()) {
            throw new BusinessException(
                "Documento no status '{$fiscalDocument->status->label()}' não pode ser reenviado."
            );
        }

        ProcessFiscalDocumentJob::dispatch($fiscalDocument->uuid, $fiscalDocument->tenant_id);

        return $this->success(new FiscalDocumentResource($fiscalDocument));
    }
}
