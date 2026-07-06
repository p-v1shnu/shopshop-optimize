<?php

namespace App\Livewire\Frontend;

use App\Livewire\Frontend\Concerns\ShopCartTrait;
use App\Models\ShopCoupon;
use App\Models\ShopOrderCoupon;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ShopCouponInput extends Component
{
    use ShopCartTrait;

    #[Locked]
    public string $step = 'initial'; // initial, input, applied

    #[Locked]
    public string $couponErrorMessage = '';

    public string $couponCode = '';

    public function mount(): void
    {
        $this->updateCart();

        if ($this->cartCoupon) {
            $this->step = 'applied';
        }
    }

    public function render()
    {
        return view('frontend.livewire.shop-coupon-input');
    }

    public function showCouponInput(): void
    {
        $this->couponCode = '';
        $this->couponErrorMessage = '';
        $this->step = 'input';
    }

    public function applyCoupon(): void
    {
        try {

            $user = auth()->user();
            $this->couponErrorMessage = '';
            $this->couponCode = trim($this->couponCode);

            if (tenant('enable_coupon') !== true) {
                $this->couponErrorMessage = 'ຄູປອງບໍ່ຖືກຕ້ອງ, ໝົດອາຍຸ ຫຼື ຖືກນຳໃຊ້ແລ້ວ ກະລຸນາກວດສອບອີກຄັ້ງ';
                return;
            }

            // Convert coupon to uppercase
            $this->couponCode = strtoupper($this->couponCode);

            Log::info('Apply coupon attempt', [
                'code' => $this->couponCode,
                'user' => $user->toArray(),
            ]);

            if ($this->couponCode === '') {
                $this->couponErrorMessage = 'ກະລຸນາປ້ອນລະຫັດຄູປອງ';
                return;
            }

            // Check if coupon is usable (without locking to avoid blocking other users)
            $coupon = ShopCoupon::query()
                ->where('code', '=', $this->couponCode)
                ->where('status', '=', 'active')
                ->where('started_at', '<=', now())
                ->where('ended_at', '>=', now())
                ->where('available_quantity', '>', 0)
                ->first();

            if (!$coupon) {
                $this->couponErrorMessage = 'ຄູປອງບໍ່ຖືກຕ້ອງ, ໝົດອາຍຸ ຫຼື ຖືກນຳໃຊ້ແລ້ວ ກະລຸນາກວດສອບອີກຄັ້ງ';
                return;
            }

            // Additional check: if coupon is private (has user_id), verify it belongs to this user
            if ($coupon->user_id !== null && $coupon->user_id !== $user->id) {
                $this->couponErrorMessage = 'ທ່ານບໍ່ສາມາດນຳໃຊ້ຄູປອງນີ້ໄດ້';
                return;
            }

            // Validate coupon type
            if (!in_array($coupon->type, ['fixed', 'percentage'])) {
                $this->couponErrorMessage = 'ປະເພດຄູປອງບໍ່ຖືກຕ້ອງ';
                return;
            }

            // Validate coupon amount based on type
            if ($coupon->type === 'fixed' && $coupon->amount <= 0) {
                $this->couponErrorMessage = 'ຈຳນວນສ່ວນຫຼຸດບໍ່ຖືກຕ້ອງ';
                return;
            }

            if ($coupon->type === 'percentage' && ($coupon->amount <= 0 || $coupon->amount > 100)) {
                $this->couponErrorMessage = 'ອັດຕາສ່ວນຫຼຸດບໍ່ຖືກຕ້ອງ';
                return;
            }

            // Check minimum cart amount
            if ($this->cartAmount() < $coupon->minimum_order_amount) {
                $this->couponErrorMessage = 'ຍອດລວມຕ້ອງເຖິງຂັ້ນຕ່ຳ ' . number_format($coupon->minimum_order_amount) . ' ກີບ ຈຶ່ງສາມາດນຳໃຊ້ຄູປອງນີ້ໄດ້ ກະລຸນາກວດສອບອີກຄັ້ງ';
                return;
            }

            // Check user daily limit with locking
            if ($coupon->user_daily_limit > 0) {
                $todayStart = now()->startOfDay();
                $todayEnd = now()->endOfDay();

                // Lock only the user's usage records to prevent race condition on daily limit
                $usageCount = ShopOrderCoupon::query()
                    ->select('shop_order_coupons.*')
                    ->join('shop_orders', 'shop_order_coupons.shop_order_id', '=', 'shop_orders.id')
                    ->where('shop_order_coupons.shop_coupon_id', '=', $coupon->id)
                    ->where('shop_order_coupons.user_id', '=', $user->id)
                    ->whereBetween('shop_order_coupons.created_at', [$todayStart, $todayEnd])
                    ->whereIn('shop_orders.payment_status', ['pending', 'paid'])
                    ->lockForUpdate()
                    ->count();

                if ($usageCount >= $coupon->user_daily_limit) {
                    // Get the latest pending order with payment_expired_at
                    $latestPendingOrder = ShopOrderCoupon::query()
                        ->select('shop_orders.payment_expired_at')
                        ->join('shop_orders', 'shop_order_coupons.shop_order_id', '=', 'shop_orders.id')
                        ->where('shop_order_coupons.shop_coupon_id', '=', $coupon->id)
                        ->where('shop_order_coupons.user_id', '=', $user->id)
                        ->whereBetween('shop_order_coupons.created_at', [$todayStart, $todayEnd])
                        ->where('shop_orders.payment_status', '=', 'pending')
                        ->orderByDesc('shop_orders.payment_expired_at')
                        ->first();

                    if ($latestPendingOrder && $latestPendingOrder->payment_expired_at) {
                        $now = now();
                        $expiresAt = CarbonImmutable::parse($latestPendingOrder->payment_expired_at);

                        if ($expiresAt->isFuture()) {
                            $minutesLeft = $now->diffInMinutes($expiresAt) + 1;
                            $this->couponErrorMessage = 'ຄຳສັ່ງຊື້ກ່ອນໜ້າຂອງທ່ານຍັງບໍ່ທັນສຳເລັດ ກະລຸນາລໍຖ້າ ' . number_format($minutesLeft) .' ນາທີ ແລ້ວນຳໃຊ້ຄູປອງອີກຄັ້ງ';
                        } else {
                            $this->couponErrorMessage = 'ຄຳສັ່ງຊື້ກ່ອນໜ້າຂອງທ່ານຍັງບໍ່ທັນສຳເລັດ ກະລຸນາລໍຖ້າ 1 ນາທີ. ແລ້ວນຳໃຊ້ຄູປອງອີກຄັ້ງ';
                        }
                    } else {
                        $this->couponErrorMessage = 'ທ່ານສາມາດນຳໃຊ້ຄູປອງນີ້ໄດ້ ' . number_format($coupon->user_daily_limit) . ' ຄັ້ງຕໍ່ມື້';
                    }
                    return;
                }
            }

            // Apply coupon to cart session
            session()->put('cartCoupon', [
                'id'                   => $coupon->id,
                'code'                 => $coupon->code,
                'type'                 => $coupon->type,
                'amount'               => $coupon->amount,
                'minimum_order_amount' => $coupon->minimum_order_amount,
                'user_daily_limit'     => $coupon->user_daily_limit,
                'started_at'           => $coupon->started_at,
                'ended_at'             => $coupon->ended_at,
            ]);

            $this->step = 'applied';
            $this->cartCoupon = session('cartCoupon');
            $this->dispatch('shop.refreshCart');

            Log::info('Coupon applied successfully', [
                'coupon' => $coupon->toArray(),
                'user'   => $user->toArray(),
            ]);

        } catch (Exception $e) {

            Log::info('Apply coupon exception', [
                'message' => $e->getMessage(),
                'stack'   => $e->getTraceAsString(),
            ]);

            $this->couponErrorMessage = 'ບໍ່ສາມາດນຳໃຊ້ຄູປອງໄດ້ ກະລຸນາລອງໃໝ່';

        }
    }

    public function removeCoupon(): void
    {
        $user = auth()->user();
        $removedCoupon = $this->cartCoupon;

        session()->forget('cartCoupon');
        $this->cartCoupon = null;
        $this->step = 'initial';
        $this->couponCode = '';
        $this->couponErrorMessage = '';
        $this->dispatch('shop.refreshCart');

        Log::info('Coupon removed from cart', [
            'coupon' => $removedCoupon,
            'user'   => $user->toArray(),
        ]);
    }
}
