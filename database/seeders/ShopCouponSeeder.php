<?php

namespace Database\Seeders;

use App\Models\ShopCoupon;
use Illuminate\Database\Seeder;

class ShopCouponSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            'gadzila'    => 'GZ',
            'babybright' => 'BB',
        ];

        foreach ($tenants as $tenantId => $prefix) {
            ShopCoupon::create([
                'tenant_id'            => $tenantId,
                'status'               => 'active',
                'code'                 => $prefix . 'AM10',
                'type'                 => 'fixed',
                'amount'               => 1.00, // 1 LAK
                'started_at'           => '2026-04-01 00:00:00',
                'ended_at'             => '2026-12-31 23:59:59',
                'total_quantity'       => 1000,
                'available_quantity'   => 1000,
                'user_daily_limit'     => 100,
                'minimum_order_amount' => 1.00, // 1 LAK
                'remark'               => 'Test coupon - 1 LAK discount',
            ]);

            ShopCoupon::create([
                'tenant_id'            => $tenantId,
                'status'               => 'active',
                'code'                 => $prefix . 'PE10',
                'type'                 => 'percentage',
                'amount'               => 10.00, // 10%
                'started_at'           => '2026-04-01 00:00:00',
                'ended_at'             => '2026-12-31 23:59:59',
                'total_quantity'       => 1000,
                'available_quantity'   => 1000,
                'user_daily_limit'     => 100,
                'minimum_order_amount' => 1.00, // 1 LAK
                'remark'               => 'Test coupon - 10% discount',
            ]);
        }
    }
}
