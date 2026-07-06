<?php

namespace App\Models;

use App\Models\Concerns\SerializesDatesToAppTimezone;
use Hidehalo\Nanoid\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ShopOrder extends Model
{
    use SerializesDatesToAppTimezone, BelongsToTenant;

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'id',
        'user_id',
        'order_amount',
        'shipping_amount',
        'coupon_amount',
        'payment_amount',
        'payment_uuid',
        'payment_status',
        'payment_expired_at',
        'payment_reconciled_at',
        'payment_channel',
        'shipping_fee_type',
        'shipping_channel',
        'shipping_channel_name',
        'shipping_name',
        'shipping_phone',
        'shipping_province',
        'shipping_district',
        'shipping_village',
        'shipping_remark',
        'shipping_branch_province',
        'shipping_branch_district',
        'shipping_branch_name',
        'shipping_detail',
        'shipping_tracking_number',
        'shipping_status',
        'generate_qr_request',
        'generate_qr_response',
        'order_code',
        'campaign_code',
        'total_product_quantity',
        'total_shipping_quantity',
        'notified_invoice_api_at',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'payment_expired_at'      => 'datetime',
            'payment_reconciled_at'   => 'datetime',
            'generate_qr_request'     => 'json',
            'generate_qr_response'    => 'json',
            'shipping_detail'         => 'json',
            'notified_invoice_api_at' => 'datetime',
            'created_at'              => 'datetime',
            'updated_at'              => 'datetime',
        ];
    }

    public function details(): HasMany
    {
        return $this->hasMany(ShopOrderDetail::class)
            ->orderBy('id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(ShopOrderCoupon::class);
    }

    public function payment(): HasOne
    {
        return $this
            ->hasOne(ShopOrderPayment::class)
            ->where('type', '=', 'payment');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ShopOrderLog::class);
    }

    public function getShippingDiscountAttribute(): int
    {
        return 0;
    }

    public function getOrderQuantityAttribute(): int
    {
        return $this->details()->sum('quantity');
    }

    public static function generateOrderCode(): string
    {
        $client = new Client();
        return $client->formattedId('0123456789', 6);
    }
}
