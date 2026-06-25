<?php

declare(strict_types=1);

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ImportLog extends BaseModel
{
    protected $table = 'import_logs';

    protected $fillable = [
        'tenant_id',
        'imported_by',
        'import_type',
        'file_name',
        'total_rows',
        'success_count',
        'error_count',
        'status',
        'errors',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'errors'      => 'array',
            'started_at'  => 'datetime',
            'finished_at' => 'datetime',
        ]);
    }

    public function markCompleted(int $success, int $errors, array $errorDetails): void
    {
        $this->update([
            'success_count' => $success,
            'error_count'   => $errors,
            'total_rows'    => $success + $errors,
            'status'        => $errors > 0 && $success === 0 ? 'failed' : 'completed',
            'errors'        => $errorDetails ?: null,
            'finished_at'   => now(),
        ]);
    }
}
