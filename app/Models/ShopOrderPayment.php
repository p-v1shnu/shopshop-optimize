<?php

namespace App\Models;

use App\Models\Concerns\SerializesDatesToAppTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ShopOrderPayment extends Model
{
    use SerializesDatesToAppTimezone, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'shop_order_id',
        'channel',
        'merchant_provider',
        'merchant_id',
        'amount',
        'xref',
        'ref',
        'reconciled_at',
        'type',
        'response',
        'remark',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'reconciled_at' => 'datetime',
            'response'      => 'json',
            'created_at'    => 'datetime',
            'updated_at'    => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ShopOrder::class, 'shop_order_id', 'id');
    }
}
