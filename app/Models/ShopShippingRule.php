<?php

namespace App\Models;

use App\Models\Concerns\SerializesDatesToAppTimezone;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ShopShippingRule extends Model
{
    use SerializesDatesToAppTimezone, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'status',
        'started_at',
        'ended_at',
        'minimum_amount',
        'shipping_fee_type',
        'shipping_days_text',
        'remark',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at'   => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
