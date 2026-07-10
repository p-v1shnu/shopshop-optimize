<?php

namespace App\Utils;

use App\Exceptions\ShopValidationException;
use App\Models\ShopProduct;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Uri;

class ShopUtil
{
    public const CHINESE_NEW_YEAR_2025_CAMPAIGN_CODE = 'chinese_new_year_2025';

    public const LAO_NEW_YEAR_2025_CAMPAIGN_CODE = 'lao_new_year_2025';

    public const SIX_JUNE_2025_CAMPAIGN_CODE = '06_06_2025';

    public static function isShopClosed(): bool
    {
        if (tenant('enable_shop') === false) {
            return true;
        }

        $shopClosedAt = tenant('shop_closed_at');

        if (blank($shopClosedAt)) {
            return false;
        }

        return CarbonImmutable::now() >= CarbonImmutable::parse($shopClosedAt);
    }

    /**
     * @throws ShopValidationException
     */
    public static function addToCart(ShopProduct $product, int $quantity = 1): void
    {
        $cartProducts = session('cartProducts', []);

        // Update product quantity
        foreach ($cartProducts as $index => $cartProduct) {
            if ($cartProducts[$index]['product']['id'] === $product->id) {
                // Validate available quantity before updating
                $newQuantity = $cartProducts[$index]['quantity'] + $quantity;
                if ($newQuantity > $product->available_quantity) {
                    Log::warning('Cannot add to cart: exceeds available stock', [
                        'product_id' => $product->id,
                        'requested_quantity' => $newQuantity,
                        'available_quantity' => $product->available_quantity
                    ]);
                    throw new ShopValidationException(self::getProductNotEnoughMessage($product->available_quantity));
                }

                $cartProducts[$index]['quantity'] += $quantity;
                session(['cartProducts' => $cartProducts]);
                Log::info('Update product quantity', $cartProducts);
                return;
            }
        }

        // Validate available quantity before adding new item
        if ($quantity > $product->available_quantity) {
            Log::warning('Cannot add to cart: exceeds available stock', [
                'product_id' => $product->id,
                'requested_quantity' => $quantity,
                'available_quantity' => $product->available_quantity
            ]);
            throw new ShopValidationException(self::getProductNotEnoughMessage($product->available_quantity));
        }

        // Add to cart
        $cartProducts[] = [
            'product'  => $product,
            'quantity' => $quantity,
        ];

        session(['cartProducts' => $cartProducts]);
        Log::info('Add to Cart', $cartProducts);
    }

    /**
     * @throws ShopValidationException
     */
    public static function increaseQuantity(ShopProduct $product, int $quantity = 1): void
    {
        $cartProducts = session('cartProducts', []);

        foreach ($cartProducts as $index => $cartProduct) {
            if ($cartProducts[$index]['product']['id'] === $product->id) {
                // Validate available quantity before increasing
                $newQuantity = $cartProducts[$index]['quantity'] + $quantity;
                if ($newQuantity > $product->available_quantity) {
                    Log::warning('Cannot increase quantity: exceeds available stock', [
                        'product_id' => $product->id,
                        'requested_quantity' => $newQuantity,
                        'available_quantity' => $product->available_quantity
                    ]);
                    throw new ShopValidationException(self::getProductNotEnoughMessage($product->available_quantity));
                }

                $cartProducts[$index]['quantity'] += $quantity;
                session(['cartProducts' => $cartProducts]);
                Log::info('Update product quantity', $cartProducts);
                break;
            }
        }
    }

    public static function decreaseQuantity(ShopProduct $product, int $quantity = 1): void
    {
        $cartProducts = session('cartProducts', []);

        foreach ($cartProducts as $index => $cartProduct) {
            if ($cartProducts[$index]['product']['id'] === $product->id) {

                if ($cartProducts[$index]['quantity'] <= 1) {
                    $cartProducts[$index]['quantity'] = 1;
                } else {
                    $cartProducts[$index]['quantity']--;
                }

                session(['cartProducts' => $cartProducts]);
                Log::info('Update product quantity', $cartProducts);
                break;
            }
        }
    }

    public static function removeFromCart(ShopProduct $product): void
    {
        $cartProducts = session('cartProducts', []);

        foreach ($cartProducts as $index => $cartProduct) {
            if ($cartProducts[$index]['product']['id'] === $product->id) {
                unset($cartProducts[$index]);
                session(['cartProducts' => $cartProducts]);
                break;
            }
        }
    }

    public static function getCart(): array
    {
        $cart = session('cart');

        if (!$cart) {
            $cart = [
                'products' => []
            ];
        }

        return $cart;
    }

    public static function getCartQuantity(): int
    {
        $cartProducts = session('cartProducts', []);

        return array_reduce($cartProducts, function($carry, array $item) {
            return $carry + $item['quantity'];
        }, 0);
    }

    public static function getCartAmount(): float
    {
        $cartProducts = session('cartProducts', []);

        $total = 0;
        foreach ($cartProducts as $cartProduct) {
            $total += $cartProduct['product']['price'] * $cartProduct['quantity'];
        }
        return $total;
    }

    public static function clearCart(): void
    {
        session()->forget('cartProducts');
        session()->forget('cartCoupon');
    }

    public static function clearShopSession(): void
    {
        session()->forget('shopCode');
        self::clearCart();
    }

    public static function getCampaignCode(CarbonImmutable $date): string | null
    {
        if (config('custom.shop_campaign_code') !== '') {
            return config('custom.shop_campaign_code');
        }

        $campaignCode = tenant('campaign_code');
        $campaignStartsAt = tenant('campaign_starts_at');
        $campaignEndsAt = tenant('campaign_ends_at');

        if (blank($campaignCode) || blank($campaignStartsAt) || blank($campaignEndsAt)) {
            return null;
        }

        return $date->between(
            CarbonImmutable::parse($campaignStartsAt),
            CarbonImmutable::parse($campaignEndsAt)
        ) ? $campaignCode : null;
    }

    public static function getHomeUrl(array $parameters = []): string
    {
        $route = route('home');

        $url = Uri::of($route)
            ->withQuery($parameters)
            ->value();

        return str_ends_with($url, '?')
            ? substr($url, 0, -1)
            : $url;
    }

    /**
     * @throws ShopValidationException
     */
    public static function shopValidation(): void
    {
        // Shop Closed Check
        if (ShopUtil::isShopClosed()) {
            throw new ShopValidationException('ຂໍອະໄພ ຂະນະນີ້ປິດການຂາຍຊົ່ວຄາວ');
        }

        // Allow Province Check
        $allowProvinceIds = tenant('allow_province_ids');
        if ($allowProvinceIds !== null && !in_array(auth()->user()->province, $allowProvinceIds)) {
            $allowedProvinceNames = array_map(fn($provinceId): string => FormUtil::getProvinceName($provinceId), $allowProvinceIds);
            $provinceList = implode(', ', $allowedProvinceNames);
            throw new ShopValidationException('ຕອນນີ້ລະບົບຮອງຮັບການຊື້ສິນຄ້າສະເພາະ ' . $provinceList);
        }
    }

    public static function getProductNotEnoughMessage(int $availableQuantity): string
    {
        return $availableQuantity === 0
            ? 'ສິນຄ້າໝົດແລ້ວ'
            : 'ສິນຄ້າຍັງເຫຼືອ ' . number_format($availableQuantity) . ' ລາຍການເທົ່ານັ້ນ';
    }
}
