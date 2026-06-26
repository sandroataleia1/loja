<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class CustomerPurchaseReference extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'customer_purchase_references';

    protected $primaryKey = 'uuid';

    public $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'customer_id',
        'person_type',
        'company_name',
        'phone',
        'monthly_limit',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'uuid');
    }
}
