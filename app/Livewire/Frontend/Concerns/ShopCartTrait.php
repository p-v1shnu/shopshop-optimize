<?php

namespace App\Livewire\Frontend\Concerns;

use App\Models\ShopCoupon;
use App\Models\ShopProduct;
use App\Models\ShopShippingRule;
use App\Utils\ShopUtil;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

trait ShopCartTrait
{
    #[Locked]
    public array $cartProducts = [];

    #[Locked]
    public ?array $cartCoupon = null;

    #[Computed]
    public function cartQuantity(): int
    {
        return ShopUtil::getCartQuantity();
    }

    #[Computed]
    public function cartAmount(): float
    {
        $total = 0;
        foreach ($this->cartProducts as $cartProduct) {
            $total += $cartProduct['product']['price'] * $cartProduct['quantity'];
        }
        return $total;
    }

    #[Computed]
    public function cartTotalShippingItem(): int
    {
        $totalShipping = 0;
        foreach ($this->cartProducts as $cartProduct) {
            $totalShipping += $cartProduct['quantity'];
        }
        return $totalShipping;
    }

    #[Computed]
    public function cartShippingRule(): ?ShopShippingRule
    {
        $currentDate = CarbonImmutable::now();

        // Get active shipping rules for current date
        $rule = ShopShippingRule::query()
            ->select(['shipping_fee_type', 'shipping_days_text'])
            ->where('status', '=', 'active')
            ->where('started_at', '<=', $currentDate)
            ->where('ended_at', '>=', $currentDate)
            ->where('minimum_amount', '<=', $this->cartAmount)
            ->orderBy('minimum_amount', 'desc') // Get the rule with the highest minimum amount that cart qualifies for
            ->first();

        ray()->text('Shipping Rule')->blue()->large();
        ray()->model($rule);

        return $rule;
    }

    #[Computed]
    public function cartShippingFeeType(): ?string
    {
        $channels = tenant('shipping_channels');
        if (in_array('hal', $channels) || in_array('seller', $channels)) {
            $rule = $this->cartShippingRule();

            if ($rule) {
                return $rule->shipping_fee_type;
            }

            // Default fallback if no rules apply
            return 'cod';
        }

        return null; // No shipping
    }

    #[Computed]
    public function cartShippingAmount(): float
    {
        $type = $this->cartShippingFeeType();

        if ($type === 'cod') {
            return 0;
        } elseif ($type === 'free') {
            return 0;
        } elseif ($type === 'prepaid') {
            return 99999999;
        } else {
            return 0;
        }
    }

    #[Computed]
    public function cartCouponAmount(): float
    {
        if (!$this->cartCoupon) {
            return 0;
        }

        $couponAmount = $this->cartCoupon['amount'] ?? 0;

        // Fixed discount type - discount is a fixed amount
        if ($this->cartCoupon['type'] === 'fixed') {
            // Ensure discount doesn't exceed cart amount
            return min($couponAmount, $this->cartAmount);
        }

        // Percentage discount type - discount is a percentage (0-100)
        if ($this->cartCoupon['type'] === 'percentage') {
            // Validate percentage is within valid range
            $percentage = max(0, min(100, $couponAmount));

            // Calculate discount and ensure it doesn't exceed cart amount
            $discount = ($this->cartAmount * $percentage) / 100;
            return min($discount, $this->cartAmount);
        }

        return 0;
    }

    #[Computed]
    public function cartDiscountAmount(): float
    {
        return $this->cartCouponAmount();
    }

    #[Computed]
    public function cartNetAmount(): float
    {
        return max(0, $this->cartAmount
            + $this->cartShippingAmount
            - $this->cartCouponAmount());
    }

    public function mount(): void
    {
        $this->updateCart();
    }

    #[On('shop.refreshCart')]
    public function updateCart(): void
    {
        $this->restoreProductsFromSession();
        $this->restoreCouponFromSession();
    }

    private function restoreProductsFromSession(): void
    {
        // Get products from database instead of using session data
        $sessionCartProducts = session('cartProducts', []);

        if (empty($sessionCartProducts)) {
            $this->cartProducts = [];
            return;
        }

        // Extract product IDs from session
        $productIds = collect($sessionCartProducts)
            ->pluck('product.id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($productIds)) {
            $this->cartProducts = [];
            session()->forget('cartProducts');
            return;
        }

        // Fetch fresh products from DB (only active ones)
        $freshProducts = ShopProduct::query()
            ->select(['id', 'images', 'name', 'normal_price', 'price'])
            ->whereIn('id', $productIds)
            ->where('status', '=', 'active')
            ->get()
            ->keyBy('id');

        // Rebuild cart with fresh product data
        $updatedCartProducts = [];
        foreach ($sessionCartProducts as $cartProduct) {
            $productId = $cartProduct['product']['id'] ?? null;
            $quantity = $cartProduct['quantity'] ?? 1;

            // Skip if product no longer exists or is inactive
            if (!$productId || !$freshProducts->has($productId)) {
                continue;
            }

            $freshProduct = $freshProducts->get($productId);

            // Add to updated cart with fresh product data
            $updatedCartProducts[] = [
                'product'  => $freshProduct,
                'quantity' => $quantity,
            ];
        }

        // Update session and component property with fresh data
        session()->put('cartProducts', $updatedCartProducts);
        $this->cartProducts = $updatedCartProducts;
    }

    private function restoreCouponFromSession(): void
    {
        if (tenant('enable_coupon') !== true) {
            session()->forget('cartCoupon');
            $this->cartCoupon = null;
            return;
        }

        // Validate coupon from database instead of using session data
        $sessionCoupon = session('cartCoupon');

        if ($sessionCoupon) {
            $coupon = ShopCoupon::query()
                ->where('id', '=', $sessionCoupon['id'])
                ->where('code', '=', $sessionCoupon['code'])
                ->where('status', '=', 'active')
                ->where('started_at', '<=', now())
                ->where('ended_at', '>=', now())
                ->where('available_quantity', '>', 0)
                ->first();

            if ($coupon) {
                $freshCouponData = [
                    'id'                   => $coupon->id,
                    'code'                 => $coupon->code,
                    'type'                 => $coupon->type,
                    'amount'               => $coupon->amount,
                    'minimum_order_amount' => $coupon->minimum_order_amount,
                    'user_daily_limit'     => $coupon->user_daily_limit,
                    'started_at'           => $coupon->started_at,
                    'ended_at'             => $coupon->ended_at,
                ];

                session()->put('cartCoupon', $freshCouponData);
                $this->cartCoupon = $freshCouponData;
            } else {
                session()->forget('cartCoupon');
                $this->cartCoupon = null;
            }
        } else {
            $this->cartCoupon = null;
        }
    }
}
