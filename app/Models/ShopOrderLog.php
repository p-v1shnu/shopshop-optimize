<?php

namespace App\Models;

use App\Models\Concerns\SerializesDatesToAppTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ShopOrderLog extends Model
{
    use SerializesDatesToAppTimezone, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'shop_order_id',
        'type',
        'detail',
        'response_time',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'detail'     => 'json',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ShopOrder::class, 'shop_order_id', 'id');
    }
}
