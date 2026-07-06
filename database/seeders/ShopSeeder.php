<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\ShopProduct;
use App\Models\ShopShippingRule;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ShopSeeder extends Seeder
{
    public function run(): void
    {
        Setting::create([
            'title'              => 'shopshop.la',
            'facebook_cover_url' => null,
            'landing_page_url'   => 'https://landing.shopshop.la/en',
        ]);

        $this->seedBabyBright();
        $this->seedMuanson();
        $this->seedGadzila();
    }

    private function seedBabyBright(): void
    {
        Tenant::create([
            'id'                           => 'babybright',
            'name'                         => 'Baby Bright',
            'status'                       => 'active',
            'enable_shop'                  => true,
            'order_invoice_webhook_url'    => 'https://hkdk.events/emq86x05607vus',
            'site_logo_url'                => 'https://assets.shopshop.la/shopshop/images/site-logo.png',
            'facebook_name'                => null,
            'facebook_url'                 => null,
            'facebook_cover_url'           => 'https://assets.shopshop.la/shopshop/images/facebook-cover.png',
            'delivery_contact_phone'       => ['+8562095337188'],
            'support_contact_phone'        => '+8562088551216',
            'otp_site_name'                => 'Shop Shop - Baby Bright',
            'footer_more_info_text'        => 'ເບິ່ງສິນຄ້າທັງໝົດຂອງ MEK',
            'footer_more_info_link'        => 'https://l.ead.me/mek-shopshop',
            'homepage_banners'             => [
                'https://assets.shopshop.la/shopshop/images/banner-1.png',
            ],
            'popup_banners'                => [
                'https://assets.shopshop.la/shopshop/images/promotion-banner-popup-06062025.jpeg',
            ],
            'maintenance_mode'             => null,
            'allow_province_ids'           => ['VT', 'AT', 'BK', 'BL', 'CH', 'HO', 'KH', 'LM', 'LP', 'OU', 'PH', 'SL', 'SV', 'VI', 'XA', 'XS', 'XE', 'XI'],
            'shipping_channels'            => ['hal'],
            'no_shipping_instruction_text' => null,
            'no_shipping_order_paid_text'  => null,
            'latitude'                     => null,
            'longitude'                    => null,
        ])->domains()->create(['domain' => 'babybright.shopshop.test']);

        ShopProduct::create([
            'tenant_id'         => 'babybright',
            'name'              => '2 IN 1 TINY & SLIM LINER 0.1G BABY BRIGHT',
            'images'            => [
                ['filename' => 'https://assets.shopshop.la/shopshop/images/products/product-1.png', 'is_cover' => true],
            ],
            'normal_price'      => 200000,
            'price'             => 100000,
            'short_description' => null,
            'long_description'  => 'lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'sku'               => 'product-1',
            'sort_no'           => 1,
            'status'            => 'active',
            'remark'            => null,
        ])->updateProductAvailableQuantity(1000, 'SET', 'Initial stock');

        ShopProduct::create([
            'tenant_id'         => 'babybright',
            'name'              => '5 OILS VEGAN LIP TREATMENT 2.5G BABY BRIGHT',
            'images'            => [
                ['filename' => 'https://assets.shopshop.la/shopshop/images/products/product-2.png', 'is_cover' => true],
            ],
            'normal_price'      => null,
            'price'             => 1,
            'short_description' => null,
            'long_description'  => 'lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'sku'               => 'product-2',
            'sort_no'           => 2,
            'status'            => 'active',
            'remark'            => null,
        ])->updateProductAvailableQuantity(1000, 'SET', 'Initial stock');

        ShopShippingRule::create([
            'tenant_id'          => 'babybright',
            'status'             => 'active',
            'started_at'         => '2025-01-01 00:00:00',
            'ended_at'           => '2025-07-03 23:59:59',
            'minimum_amount'     => 0,
            'shipping_fee_type'  => 'cod',
            'shipping_days_text' => 'ການຈັດສົ່ງຈະໃຊ້ເວລາປະມານ 7-10 ວັນລັດຖະການ',
            'remark'             => 'ລູກຄ້າຈ່າຍປາຍທາງ',
        ]);

        ShopShippingRule::create([
            'tenant_id'          => 'babybright',
            'status'             => 'active',
            'started_at'         => '2025-07-04 00:00:00',
            'ended_at'           => '2025-07-06 23:59:59',
            'minimum_amount'     => 100000,
            'shipping_fee_type'  => 'free',
            'shipping_days_text' => 'ການຈັດສົ່ງຈະໃຊ້ເວລາປະມານ 7-10 ວັນລັດຖະການ',
            'remark'             => 'ສົ່ງຟຣີຕັ້ງແຕ່ 2025-07-04 ຫາ 2025-08-04, ຂັ້ນຕ່ຳ 100,000 ກີບ',
        ]);

        ShopShippingRule::create([
            'tenant_id'          => 'babybright',
            'status'             => 'active',
            'started_at'         => '2025-07-07 00:00:00',
            'ended_at'           => '2025-07-07 23:59:59',
            'minimum_amount'     => 0,
            'shipping_fee_type'  => 'free',
            'shipping_days_text' => 'ການຈັດສົ່ງຈະໃຊ້ເວລາປະມານ 7-10 ວັນລັດຖະການ',
            'remark'             => 'ສົ່ງຟຣີຕັ້ງແຕ່ 2025-07-04 ຫາ 2025-08-04, ຂັ້ນຕ່ຳ 0 ກີບ',
        ]);

        ShopShippingRule::create([
            'tenant_id'          => 'babybright',
            'status'             => 'active',
            'started_at'         => '2025-07-08 00:00:00',
            'ended_at'           => '2025-08-04 23:59:59',
            'minimum_amount'     => 100000,
            'shipping_fee_type'  => 'free',
            'shipping_days_text' => 'ການຈັດສົ່ງຈະໃຊ້ເວລາປະມານ 7-10 ວັນລັດຖະການ',
            'remark'             => 'ສົ່ງຟຣີຕັ້ງແຕ່ 2025-07-04 ຫາ 2025-08-04, ຂັ້ນຕ່ຳ 100,000 ກີບ',
        ]);

        ShopShippingRule::create([
            'tenant_id'          => 'babybright',
            'status'             => 'active',
            'started_at'         => '2025-08-05 00:00:00',
            'ended_at'           => '3000-01-01 23:59:59',
            'minimum_amount'     => 0,
            'shipping_fee_type'  => 'cod',
            'shipping_days_text' => 'ການຈັດສົ່ງຈະໃຊ້ເວລາປະມານ 7-10 ວັນລັດຖະການ',
            'remark'             => 'ລູກຄ້າຈ່າຍປາຍທາງ',
        ]);
    }

    private function seedMuanson(): void
    {
        Tenant::create([
            'id'                           => 'muanson',
            'name'                         => 'Muanson',
            'status'                       => 'active',
            'enable_shop'                  => true,
            'order_invoice_webhook_url'    => 'https://hkdk.events/emq86x05607vus',
            'site_logo_url'                => 'https://assets.shopshop.la/shopshop/images/site-logo.png',
            'facebook_name'                => null,
            'facebook_url'                 => null,
            'facebook_cover_url'           => 'https://assets.shopshop.la/shopshop/images/facebook-cover.png',
            'delivery_contact_phone'       => ['+8562095337188'],
            'support_contact_phone'        => '+8562088551216',
            'otp_site_name'                => 'Shop Shop - Muanson',
            'footer_more_info_text'        => null,
            'footer_more_info_link'        => null,
            'homepage_banners'             => [
                'https://assets.shopshop.la/shopshop/images/banner-1.png',
            ],
            'popup_banners'                => [],
            'maintenance_mode'             => null,
            'allow_province_ids'           => ['VT'],
            'shipping_channels'            => ['no_shipping'],
            'no_shipping_instruction_text' => "*ຫຼັງຈາກສັ່ງຊື້ສຳເລັດແລ້ວ<br>ທ່ານສາມາດເຂົ້າໄປຮັບບໍລິການໄດ້ທີ່ສູນບໍລິການມວນຊົນ<br>ຈັນ–ສຸກ: 8:00–17:30 | ເສົາ: 8:00–17:00<br>ເບີໂທ +8562055575787",
            'no_shipping_order_paid_text'  => 'ທ່ານສາມາດເຂົ້າໄປຮັບບໍລິການໄດ້ທີ່ສູນບໍລິການມວນຊົນ',
            'latitude'                     => '17.962166',
            'longitude'                    => '102.6256462',
        ])->domains()->create(['domain' => 'muanson.shopshop.test']);

        ShopProduct::create([
            'tenant_id'         => 'muanson',
            'name'              => 'MICHELIN Defender LTX Platinum',
            'images'            => [
                ['filename' => 'https://assets.shopshop.la/shopshop/images/products/product-1.png', 'is_cover' => true],
            ],
            'normal_price'      => 2,
            'price'             => 1,
            'short_description' => null,
            'long_description'  => 'lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'sku'               => 'product-1',
            'sort_no'           => 1,
            'status'            => 'active',
            'remark'            => null,
        ])->updateProductAvailableQuantity(1000, 'SET', 'Initial stock');
    }

    private function seedGadzila(): void
    {
        Tenant::create([
            'id'                           => 'gadzila',
            'name'                         => 'Gadzila',
            'status'                       => 'active',
            'enable_shop'                  => true,
            'order_invoice_webhook_url'    => 'https://hkdk.events/emq86x05607vus',
            'site_logo_url'                => 'https://assets.shopshop.la/gadzila/images/site-logo.png',
            'facebook_name'                => null,
            'facebook_url'                 => null,
            'facebook_cover_url'           => 'https://assets.shopshop.la/shopshop/images/facebook-cover.png',
            'delivery_contact_phone'       => ['+8562095337188'],
            'support_contact_phone'        => '+8562088551216',
            'otp_site_name'                => 'Shop Shop - Gadzila',
            'footer_more_info_text'        => null,
            'footer_more_info_link'        => null,
            'homepage_banners'             => [
                'https://assets.shopshop.la/gadzila/images/banners/LUZpFoQfyfhFQuL7SxMOEkcZ3D7U8kqZ6dMeHgWR.png',
            ],
            'popup_banners'                => [],
            'maintenance_mode'             => null,
            'allow_province_ids'           => ['VT'],
            'shipping_channels'            => ['seller'],
            'no_shipping_instruction_text' => null,
            'no_shipping_order_paid_text'  => null,
            'latitude'                     => null,
            'longitude'                    => null,
        ])->domains()->create(['domain' => 'gadzila.shopshop.test']);

        ShopProduct::create([
            'tenant_id'         => 'gadzila',
            'name'              => 'Samsung Galaxy S26 Ultra',
            'images'            => [
                ['filename' => 'https://assets.shopshop.la/gadzila/images/products/mdyVBWgxkktkXhcjURzhYMFmZcE0IMXd92iK4c78.jpg', 'is_cover' => true],
            ],
            'normal_price'      => 2,
            'price'             => 1,
            'short_description' => null,
            'long_description'  => 'lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'sku'               => 'product-1',
            'sort_no'           => 1,
            'status'            => 'active',
            'remark'            => null,
        ])->updateProductAvailableQuantity(1000, 'SET', 'Initial stock');
    }
}
