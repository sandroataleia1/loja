<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\CustomerDocument;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class CustomerDocumentController extends Controller
{
    use AuthorizesRequests;
    use HasApiResponse;

    public const ALLOWED_TYPES = [
        'rg', 'cpf', 'cnpj', 'comprovante_residencia',
        'comprovante_renda', 'contrato_social', 'outro',
    ];

    public function index(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        return $this->success($customer->documents()->with('uploadedBy')->get()->map(fn ($doc) => [
            'uuid'           => $doc->uuid,
            'document_type'  => $doc->document_type,
            'file_name'      => $doc->file_name,
            'file_size'      => $doc->file_size,
            'file_size_fmt'  => $doc->fileSizeFormatted(),
            'mime_type'      => $doc->mime_type,
            'notes'          => $doc->notes,
            'uploaded_by'    => $doc->uploadedBy?->name,
            'created_at'     => $doc->created_at,
        ]));
    }

    public function store(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $request->validate([
            'document_type' => ['required', 'string', 'in:' . implode(',', self::ALLOWED_TYPES)],
            'file'          => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
            'notes'         => ['nullable', 'string', 'max:500'],
        ]);

        $file     = $request->file('file');
        $path     = $file->store("tenants/{$customer->tenant_id}/customers/{$customer->uuid}/documents", 'local');
        $document = CustomerDocument::create([
            'tenant_id'     => $customer->tenant_id,
            'customer_id'   => $customer->uuid,
            'document_type' => $request->document_type,
            'file_name'     => $file->getClientOriginalName(),
            'file_path'     => $path,
            'file_size'     => $file->getSize(),
            'mime_type'     => $file->getMimeType(),
            'notes'         => $request->notes,
            'uploaded_by'   => $request->user()->uuid,
        ]);

        return $this->created([
            'uuid'          => $document->uuid,
            'document_type' => $document->document_type,
            'file_name'     => $document->file_name,
            'file_size_fmt' => $document->fileSizeFormatted(),
        ]);
    }

    public function destroy(Customer $customer, CustomerDocument $document): JsonResponse
    {
        $this->authorize('update', $customer);

        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        return $this->noContent();
    }
}
