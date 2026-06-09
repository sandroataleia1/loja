<?php

declare(strict_types=1);

namespace App\Modules\Sync\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Confirmação de recebimento de pull.
 *
 * O PDV envia este request após processar com sucesso o payload do pull.
 * Permite que o backend saiba que o checkpoint pode ser confirmado.
 * (Nesta implementação, o checkpoint já foi avançado otimisticamente;
 *  o ack serve para log de auditoria e confirmação do device.)
 */
final class SyncPullAckRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'device_uuid' => ['required', 'uuid'],
            'batch_id'    => ['required', 'string', 'max:36'],
            'pulled_at'   => ['required', 'date'],
        ];
    }
}
