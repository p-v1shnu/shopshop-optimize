<?php

namespace App\Models;

use App\Models\Concerns\SerializesDatesToAppTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ShopOrderCoupon extends Model
{
    use SerializesDatesToAppTimezone, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'shop_order_id',
        'shop_coupon_id',
        'user_id',
        'coupon_code',
        'coupon_type',
        'coupon_amount',
        'discount_amount',
        'before_discount_amount',
        'minimum_order_amount',
        'user_daily_limit',
        'started_at',
        'ended_at',
        'created_at',
        'updated_at',
        'remark',
    ];

    protected function casts(): array
    {
        return [
            'started_at'             => 'datetime',
            'ended_at'               => 'datetime',
            'created_at'             => 'datetime',
            'updated_at'             => 'datetime',
            'coupon_amount'          => 'decimal:2',
            'discount_amount'        => 'decimal:2',
            'before_discount_amount' => 'decimal:2',
            'minimum_order_amount'   => 'decimal:2',
        ];
    }

    public function shopOrder(): BelongsTo
    {
        return $this->belongsTo(ShopOrder::class);
    }

    public function shopCoupon(): BelongsTo
    {
        return $this->belongsTo(ShopCoupon::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
