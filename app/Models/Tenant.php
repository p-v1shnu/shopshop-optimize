<?php

namespace App\Models;

use App\Models\Concerns\SerializesDatesToAppTimezone;
use Stancl\Tenancy\Database\Concerns\MaintenanceMode;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use SerializesDatesToAppTimezone, HasDatabase, HasDomains, MaintenanceMode;

    public $keyType = 'string';

    public $primaryKey = 'id';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'status',
        'enable_shop',
        'enable_coupon',
        'order_invoice_webhook_url',
        'site_logo_url',
        'facebook_name',
        'facebook_url',
        'facebook_cover_url',
        'delivery_contact_phone',
        'support_contact_phone',
        'otp_site_name',
        'contact_url',
        'footer_more_info_text',
        'footer_more_info_link',
        'homepage_banners',
        'popup_banners',
        'head_html',
        'google_tag_manager_id',
        'google_analytics_id',
        'maintenance_mode',
        'allow_province_ids',
        'shipping_channels',
        'no_shipping_instruction_text',
        'no_shipping_paid_text',
        'latitude',
        'longitude',
        'title',
        'shop_closed_at',
        'campaign_code',
        'campaign_starts_at',
        'campaign_ends_at',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'delivery_contact_phone' => 'json',
            'enable_shop'            => 'boolean',
            'enable_coupon'          => 'boolean',
            'homepage_banners'       => 'array',
            'popup_banners'          => 'array',
            'maintenance_mode'       => 'boolean',
            'allow_province_ids'     => 'array',
            'shipping_channels'      => 'array',
            'shop_closed_at'         => 'datetime',
            'campaign_starts_at'     => 'datetime',
            'campaign_ends_at'       => 'datetime',
            'created_at'             => 'datetime',
            'updated_at'             => 'datetime',
        ];
    }

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'status',
            'enable_shop',
            'enable_coupon',
            'order_invoice_webhook_url',
            'site_logo_url',
            'facebook_name',
            'facebook_url',
            'facebook_cover_url',
            'delivery_contact_phone',
            'support_contact_phone',
            'otp_site_name',
            'contact_url',
            'footer_more_info_text',
            'footer_more_info_link',
            'homepage_banners',
            'popup_banners',
            'head_html',
            'google_tag_manager_id',
            'google_analytics_id',
            'maintenance_mode',
            'allow_province_ids',
            'shipping_channels',
            'no_shipping_instruction_text',
            'no_shipping_paid_text',
            'latitude',
            'longitude',
            'title',
        ];
    }
}
