<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FiscalDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'             => $this->uuid,
            'store_id'         => $this->store_id,
            'sale_id'          => $this->sale_id,
            'type'             => $this->type->value,
            'type_label'       => $this->type->label(),
            'status'           => $this->status->value,
            'status_label'     => $this->status->label(),
            'provider'         => $this->provider->value,
            'access_key'       => $this->access_key,
            'protocol'         => $this->protocol,
            'xml_path'         => $this->xml_path,
            'pdf_path'         => $this->pdf_path,
            'issued_at'        => $this->issued_at?->toISOString(),
            'cancelled_at'     => $this->cancelled_at?->toISOString(),
            'contingency_mode' => $this->contingency_mode,
            'error_message'    => $this->error_message,
            'can_cancel'       => $this->status->canCancel(),
            'can_retry'        => $this->status->canRetry(),
            'created_at'       => $this->created_at?->toISOString(),
            'updated_at'       => $this->updated_at?->toISOString(),
        ];
    }
}
