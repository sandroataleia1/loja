<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CatalogProductAttribute extends BaseModel
{
    protected $table = 'catalog_product_attributes';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'attribute_group_id',
        'attribute_id',
        'value_text',
        'value_number',
        'value_unit_id',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'value_number' => 'decimal:4',
        ]);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'uuid');
    }

    public function attributeGroup(): BelongsTo
    {
        return $this->belongsTo(AttributeGroup::class, 'attribute_group_id', 'uuid');
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'attribute_id', 'uuid');
    }

    public function valueUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'value_unit_id', 'uuid');
    }
}
