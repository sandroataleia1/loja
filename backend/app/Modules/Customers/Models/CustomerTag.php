<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use App\Shared\Traits\BelongsToTenant;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class CustomerTag extends Model
{
    use BelongsToTenant;
    use HasUuid;
    use SoftDeletes;

    protected $table = 'customer_tags';

    protected $primaryKey = 'uuid';

    public $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'name',
        'color',
    ];

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(
            Customer::class,
            'customer_tag_assignments',
            'tag_id',
            'customer_id',
            'uuid',
            'uuid',
        );
    }
}
