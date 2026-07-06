<?php

namespace App\Models;

use App\Models\Concerns\SerializesDatesToAppTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ShopOrderDetail extends Model
{
    use SerializesDatesToAppTimezone, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'shop_order_id',
        'shop_product_id',
        'quantity',
        'price',
        'name',
        'images',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'images'     => 'json',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ShopOrder::class, 'shop_order_id', 'id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id', 'id');
    }
}
