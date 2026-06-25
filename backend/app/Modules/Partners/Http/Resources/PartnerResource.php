<?php

declare(strict_types=1);

namespace App\Modules\Partners\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PartnerResource extends JsonResource
{
    private static array $TYPE_LABELS = [
        'MASON'    => 'Pedreiro',
        'FOREMAN'  => 'Mestre de Obras',
        'ARCHITECT'=> 'Arquiteto',
        'ENGINEER' => 'Engenheiro',
        'DESIGNER' => 'Designer',
        'OTHER'    => 'Outro',
    ];

    public function toArray(Request $request): array
    {
        return [
            'uuid'       => $this->uuid,
            'code'       => $this->code,
            'type'       => $this->type,
            'type_label' => self::$TYPE_LABELS[$this->type] ?? $this->type,
            'name'       => $this->name,
            'document'   => $this->document,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'whatsapp'   => $this->whatsapp,
            'notes'      => $this->notes,
            'is_active'  => $this->is_active,
            'contacts'   => PartnerContactResource::collection($this->whenLoaded('contacts')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
