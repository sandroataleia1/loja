<?php

declare(strict_types=1);

namespace App\Modules\Conditional\Jobs;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Conditional\Enums\ConditionalStatusEnum;
use App\Modules\Conditional\Events\ConditionalOverdue;
use App\Modules\Conditional\Models\Conditional;
use App\Modules\Conditional\Models\ConditionalStatusHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class MarkOverdueConditionalsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $overdueStatuses = [
            ConditionalStatusEnum::Open->value,
            ConditionalStatusEnum::PartiallyReturned->value,
            ConditionalStatusEnum::PartiallyConverted->value,
        ];

        // Varredura cross-tenant intencional: lê sem escopo e estabelece o
        // contexto de cada linha antes das escritas escopadas (update/history).
        Conditional::withoutTenantScope()
            ->whereIn('status', $overdueStatuses)
            ->where('expires_at', '<', now())
            ->each(function (Conditional $conditional): void {
                TenantContext::set($conditional->tenant_id);

                try {
                    $previousStatus = $conditional->status;

                    $conditional->update(['status' => ConditionalStatusEnum::Overdue]);

                    ConditionalStatusHistory::create([
                        'conditional_id'  => $conditional->uuid,
                        'previous_status' => $previousStatus->value,
                        'current_status'  => ConditionalStatusEnum::Overdue->value,
                        'changed_by'      => null,
                        'changed_at'      => now(),
                    ]);

                    ConditionalOverdue::dispatch($conditional->tenant_id, $conditional->uuid, null);
                } finally {
                    TenantContext::clear();
                }
            });
    }
}
