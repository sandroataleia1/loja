<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use App\Core\Auth\Models\User;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class CustomerDocument extends BaseModel
{
    use SoftDeletes;

    protected $table = 'customer_documents';

    protected $fillable = [
        'tenant_id', 'customer_id', 'document_type',
        'file_name', 'file_path', 'file_size', 'mime_type', 'notes',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'file_size' => 'integer',
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'uuid');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'uuid');
    }

    public function fileSizeFormatted(): string
    {
        $kb = $this->file_size / 1024;
        if ($kb < 1024) {
            return round($kb, 1) . ' KB';
        }

        return round($kb / 1024, 2) . ' MB';
    }
}
