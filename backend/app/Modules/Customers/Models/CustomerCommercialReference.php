<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use App\Shared\Models\BaseModel;

final class CustomerCommercialReference extends BaseModel
{
    protected $table = 'customer_commercial_references';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'company_name',
        'contact_person',
        'phone',
        'notes',
    ];
}
