<?php

namespace App\Livewire\Frontend;

use App\Exceptions\ShopValidationException;
use App\Livewire\Frontend\Concerns\ShopCartTrait;
use App\Models\ShippingLog;
use App\Models\ShopCoupon;
use App\Models\ShopOrder;
use App\Models\ShopOrderCoupon;
use App\Models\ShopProduct;
use App\Models\ShopProductStock;
use App\Utils\BCELUtil;
use App\Utils\HalUtil;
use App\Utils\JDBUtil;
use App\Utils\ShopUtil;
use Carbon\CarbonImmutable;
use Exception;
use GuzzleHttp\TransferStats;
use Hidehalo\Nanoid\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class ShopShippingPage extends Component
{
    use ShopCartTrait;

    #[Locked]
    public array $cartProducts = [];

    #[Locked]
    public string $paymentChannel = '';

    #[Locked]
    public string | null $shippingChannel = null;

    #[Locked]
    public string | null $halProvinceId = null;

    #[Locked]
    public string | null $halDistrictId = null;

    #[Locked]
    public string | null $halBranchId = null;

    #[Locked]
    public string | null $sellerShippingProvinceCode = null;

    #[Locked]
    public string | null $sellerShippingDistrict = null;

    #[Locked]
    public string | null $sellerShippingVillage = null;

    #[Computed]
    public function isSubmitButtonEnabled(): bool
    {
        if ($this->paymentChannel === '') {
            return false;
        }

        if ($this->shippingChannel === 'hal') {
            return $this->halProvinceId !== null
                && $this->halDistrictId !== null
                && $this->halBranchId !== null;
        }

        if ($this->shippingChannel === 'seller') {
            return $this->sellerShippingProvinceCode !== null
                && $this->sellerShippingDistrict !== null
                && $this->sellerShippingVillage !== null
                && $this->sellerShippingVillage !== '';
        }

        return true;
    }

    public function mount(): void
    {
        // Auto select shipping channel
        $channels = tenant('shipping_channels');
        if (in_array('hal', $channels)) {
            $this->shippingChannel = 'hal';
        } elseif (in_array('seller', $channels)) {
            $this->shippingChannel = 'seller';
        }

        // Refresh cart products
        $this->cartProducts = array_map(function ($cartProduct) {
            $product = ShopProduct::query()
                ->select(['id', 'images', 'name', 'normal_price', 'price', 'available_quantity'])
                ->where('id', '=', $cartProduct['product']->id)
                ->where('status', '=', 'active')
                ->first();

            if (!$product) {
                return null; // Product not found, remove from cart
            }

            // Update product quantity in cart
            $cartProduct['product'] = $product;
            return $cartProduct;
        }, session('cartProducts', []));

        if ($this->cartProducts === []) {
            $this->redirect(route('shop.home'));
        }

        // ================================================================================

        // Restore coupon when back to this page
        $this->restoreCouponFromSession();

        // Restore payment channel when back to this page
        $this->paymentChannel = session('paymentChannel', '');

        // Restore seller address when back to this page
        if ($this->shippingChannel === 'seller') {
            $sellerShippingAddress = session('sellerShippingAddress', []);
            $this->sellerShippingProvinceCode = $sellerShippingAddress['provinceCode'] ?? null;
            $this->sellerShippingDistrict = $sellerShippingAddress['district'] ?? null;
            $this->sellerShippingVillage = $sellerShippingAddress['village'] ?? null;
        }
    }

    public function render()
    {
        return view('frontend.livewire.shop-shipping')
            ->layout('frontend.livewire.layout', [
                'showNavbar' => true,
                'showFooter' => false,
            ])
            ->title('ກວດສອບຊຳລະ');
    }

    public function updatePaymentChannel(string $paymentChannel): void
    {
        $this->paymentChannel = $paymentChannel;
        session()->put('paymentChannel', $paymentChannel);
    }

    #[On('halBranchSelected')]
    public function onHalBranchSelected(string $provinceId, string $districtId, string $branchId): void
    {
        $this->halProvinceId = $provinceId === '' ? null : $provinceId;
        $this->halDistrictId = $districtId === '' ? null : $districtId;
        $this->halBranchId = $branchId === '' ? null : $branchId;
    }

    #[On('sellerShippingAddressSelected')]
    public function onSellerShippingAddressSelected(string $provinceCode, string $district, string $village): void
    {
        $this->sellerShippingProvinceCode = $provinceCode === '' ? null : $provinceCode;
        $this->sellerShippingDistrict = $district === '' ? null : $district;
        $this->sellerShippingVillage = $village === '' ? null : trim($village);

        session()->put('sellerShippingAddress', [
            'provinceCode' => $this->sellerShippingProvinceCode,
            'district'     => $this->sellerShippingDistrict,
            'village'      => $this->sellerShippingVillage,
        ]);
    }

    public function createOrder(): void
    {
        try {
            ShopUtil::shopValidation();
        } catch (ShopValidationException $e) {
            $this->dispatch('openModal', 'alert-modal', [
                'type'    => 'info',
                'message' => $e->getMessage(),
            ]);
            return;
        }

        if ($this->paymentChannel === '') {
            $this->dispatch('openModal', 'alert-modal', [
                'type'    => 'error',
                'message' => 'ກະລຸນາເລືອກວິທີຈ່າຍເງິນ',
            ]);
            return;
        }

        // ================================================================================
        // PREPARE REQUIRED INFO
        // ================================================================================

        $user = auth()->user();

        $shippingBranchProvince = null;
        $shippingBranchDistrict = null;
        $shippingBranchName = null;
        $shippingTrackingNumber = null;
        $shippingDetail = null;
        $shippingRemark = null;

        // ================================================================================
        // SHIPPING CHANEL: hal
        // ================================================================================

        if ($this->shippingChannel === 'hal') {

            if (!$this->halProvinceId) {
                $this->dispatch('openModal', 'alert-modal', [
                    'type'    => 'error',
                    'message' => 'ກະລຸນາເລືອກແຂວງ',
                ]);
                return;
            }

            if (!$this->halDistrictId) {
                $this->dispatch('openModal', 'alert-modal', [
                    'type'    => 'error',
                    'message' => 'ກະລຸນາເລືອກເມືອງ',
                ]);
                return;
            }

            if (!$this->halBranchId) {
                $this->dispatch('openModal', 'alert-modal', [
                    'type'    => 'error',
                    'message' => 'ກະລຸນາເລືອກສາຂາ',
                ]);
                return;
            }

            $senderBranchId = 263; // Hal Center Live
            $receiverBranchId = $this->halBranchId;

            // CALCULATE FREIGHT
            $freightResponseData = null;
            $calculateFreightLog = null;

            try {
                $requestInfo = [
                    'url'    => 'https://hal.hal-logistics.la/api/v1/calculate/freight/branches',
                    'method' => 'GET',
                    'data'   => [
                        'width'            => 1,
                        'height'           => 1,
                        'length'           => 1,
                        'dimension_length' => 3,
                        'weight'           => 1,
                        'start_branch_id'  => $senderBranchId,
                        'end_branch_id'    => $receiverBranchId,
                        'calc_type'        => 'parcel',
                        'freight_type'     => 'origin',
                    ]
                ];

                Log::info('HAL Calculate freight request', $requestInfo);

                $calculateFreightLog = ShippingLog::create([
                    'provider' => 'hal',
                    'type'     => 'calculate_freight',
                    'data'     => [
                        'request'  => $requestInfo,
                        'response' => null,
                    ]
                ]);

                $responseTime = 0;

                $freightResponse = Http::withOptions([
                    'http_errors' => false,
                    'on_stats'    => function (TransferStats $stats) use (&$responseTime) {
                        $responseTime = $stats->getTransferTime();
                    }
                ])
                ->acceptJson()
                ->get($requestInfo['url'], $requestInfo['data']);

                $freightResponseData = $freightResponse->json();
                $freightResponseLog = [
                    'statusCode' => $freightResponse->status(),
                    'data'       => $freightResponseData,
                ];

                Log::info('HAL Calculate freight response', $freightResponseLog);

                $calculateFreightLog->update([
                    'data->response' => $freightResponseLog,
                    'response_time'  => $responseTime,
                ]);

                if (!isset($freightResponseData['freight'])) {
                    $this->dispatch('openModal', 'alert-modal', [
                        'type'    => 'error',
                        'message' => 'ຄ່າຂົນສົ່ງບໍ່ຖືກຕ້ອງ',
                    ]);
                    return;
                }

            } catch (Exception $e) {

                Log::error('Calculate freight error', [
                    'message' => $e->getMessage(),
                    'stack'   => $e->getTraceAsString(),
                ]);

                $this->dispatch('openModal', 'alert-modal', [
                    'type'    => 'error',
                    'message' => 'ບໍ່ສາມາດຄິດໄລ່ຄ່າຂົນສົ່ງໄດ້',
                ]);
                return;

            }

            // ================================================================================

            // CREATE PRE-ORDER
            $preOrderResponseData = null;

            try {

                $requestInfo = [
                    'url'    => 'https://hal.hal-logistics.la/api/v1/auth/users/me/shipments/orders/store',
                    'method' => 'POST',
                    'headers' => [
                        'Authorization' => 'Bearer ' . HalUtil::getToken(),
                    ],
                    'data'   => [
                        "sender"            => [
                            "full_name"    => 'Shop Shop',
                            "phone_number" => '00000000',
                            "zone"         => 'lo'
                        ],
                        "receiver"          => [
                            "full_name"    => $user->name,
                            "phone_number" => substr($user->phone, 2),
                            "location"     => 'N/A'
                        ],
                        "sender_branch_id"  => $senderBranchId,
                        "receive_branch_id" => $receiverBranchId,
                        "shipment_pay_type" => 'origin_freight_fees',
                        "payment_gateway"   => 'BCEL_ONE',
                        "shipment_type"     => 'express',
                        "parcel_type"       => 'parcel',
                        "pieces"            => 1,
                        "parcels"           => [
                            [
                                "name"             => 'N/A',
                                "category_id"      => 1,
                                "dimension_length" => 3,
                                "weight"           => 1,
                                "freight"          => $freightResponseData['freight'],
                            ]
                        ]
                    ]
                ];

                Log::info('HAL Create pre-order request', $requestInfo);

                $shippingLog = ShippingLog::create([
                    'provider' => 'hal',
                    'type'     => 'create_pre_order',
                    'data'     => [
                        'request'  => $requestInfo,
                        'response' => null,
                    ]
                ]);

                $responseTime = 0;

                $preOrderResponse = Http::withOptions([
                    'http_errors' => false,
                    'on_stats'    => function (TransferStats $stats) use (&$responseTime) {
                        $responseTime = $stats->getTransferTime();
                    }
                ])
                ->withHeaders($requestInfo['headers'])
                ->post($requestInfo['url'], $requestInfo['data']);

                Log::info('HAL Create pre-order response Raw', [
                    'body' => $preOrderResponse->body(),
                ]);

                $preOrderResponseData = $preOrderResponse->json();

                if ($preOrderResponseData === null) {
                    throw new Exception('HAL Create pre-order response JSON is null');
                }

                Log::info('HAL Create pre-order response', $preOrderResponseData);

                $shippingLog->update([
                    'provider_reference' => $preOrderResponseData['shipment_number'] ?? null,
                    'data->response'     => $preOrderResponseData,
                    'response_time'      => $responseTime,
                ]);

                if (empty($preOrderResponseData['shipment_number'])) {
                    $this->dispatch('openModal', 'alert-modal', [
                        'type'    => 'error',
                        'message' => 'ບໍ່ສາມາດສ້າງລາຍການຂົນສົ່ງໄດ້ (ບໍ່ມີເລກທີຕິດຕາມ)',
                    ]);
                    return;
                }

            } catch (Exception $e) {

                Log::error('Calculate freight error', [
                    'message' => $e->getMessage(),
                    'stack'   => $e->getTraceAsString(),
                ]);

                $this->dispatch('openModal', 'alert-modal', [
                    'type'    => 'error',
                    'message' => 'ບໍ່ສາມາດສ້າງລາຍການຂົນສົ່ງໄດ້',
                ]);
                return;

            }

            // ================================================================================

            // Update shipment number to track log
            if ($calculateFreightLog) {
                $calculateFreightLog->update([
                    'provider_reference' => $preOrderResponseData['shipment_number'],
                ]);

                $shippingBranchProvince = $preOrderResponseData['end_branch']['village']['district']['province']['name'];
                $shippingBranchDistrict = $preOrderResponseData['end_branch']['village']['district']['name'];
                $shippingBranchName = $preOrderResponseData['end_branch']['name'];
                $shippingTrackingNumber = $preOrderResponseData['shipment_number'];
            }

            // ================================================================================

            $rule = $this->cartShippingRule();

            if (!$rule) {
                $this->dispatch('openModal', 'alert-modal', [
                    'type'    => 'error',
                    'message' => 'ບໍ່ພົບເງື່ອນໄຂການຂົນສົ່ງ. ກະລຸນາຕິດຕໍ່ທີມງານ',
                ]);
                DB::rollBack();
                return;
            }

            $shippingRemark = $rule->shipping_days_text;
            $shippingDetail = [
                'rule'      => $rule,
                'pre_order' => $preOrderResponseData,
            ];
        }

        // ================================================================================
        // SHIPPING CHANNEL: seller
        // ================================================================================

        if ($this->shippingChannel === 'seller') {

            if (!$this->sellerShippingProvinceCode) {
                $this->dispatch('openModal', 'alert-modal', [
                    'type'    => 'error',
                    'message' => 'ກະລຸນາເລືອກແຂວງ',
                ]);
                return;
            }

            if (!$this->sellerShippingDistrict) {
                $this->dispatch('openModal', 'alert-modal', [
                    'type'    => 'error',
                    'message' => 'ກະລຸນາເລືອກເມືອງ',
                ]);
                return;
            }

            if (!$this->sellerShippingVillage || trim($this->sellerShippingVillage) === '') {
                $this->dispatch('openModal', 'alert-modal', [
                    'type'    => 'error',
                    'message' => 'ກະລຸນາປ້ອນຊື່ບ້ານ',
                ]);
                return;
            }

            $rule = $this->cartShippingRule();

            if ($rule) {
                $shippingRemark = $rule->shipping_days_text;
            }
        }

        // ================================================================================
        // BEGIN ORDER
        // ================================================================================

        DB::beginTransaction();
        try {
            // Lock and validate all products first
            $lockedProducts = [];
            foreach ($this->cartProducts as $cartProduct) {

                // Check product with row lock to prevent concurrent modifications
                $product = ShopProduct::query()
                    ->where('id', '=', $cartProduct['product']->id)
                    ->where('status', '=', 'active')
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    $this->dispatch('openModal', 'alert-modal', [
                        'type'    => 'error',
                        'message' => 'ສິນຄ້ານີ້ບໍ່ພ້ອມຂາຍ: ' . $cartProduct['product']->name,
                    ]);
                    DB::rollBack();
                    return;
                }

                // Validate cart quantity
                if ($cartProduct['quantity'] <= 0) {
                    $this->dispatch('openModal', 'alert-modal', [
                        'type'    => 'error',
                        'message' => 'ຈຳນວນສິນຄ້າບໍ່ຖືກຕ້ອງ: ' . $product->name,
                    ]);
                    DB::rollBack();
                    return;
                }

                // Check available quantity
                if ($product->available_quantity < $cartProduct['quantity']) {
                    $this->dispatch('openModal', 'alert-modal', [
                        'type'    => 'error',
                        'message' => 'ສິນຄ້ານີ້ບໍ່ພຽງພໍ: ' . $product->name,
                    ]);
                    DB::rollBack();
                    return;
                }

                $lockedProducts[] = [
                    'product' => $product,
                    'quantity' => $cartProduct['quantity']
                ];
            }

            // ================================================================================
            // Validate Coupon
            // ================================================================================

            $coupon = null;
            $discountAmount = 0;

            if ($this->cartCoupon) {
                // Re-check if coupon is still valid and available with row locking to prevent race condition
                $coupon = ShopCoupon::query()
                    ->where('id', '=', $this->cartCoupon['id'])
                    ->where('code', '=', $this->cartCoupon['code'])
                    ->where('status', '=', 'active')
                    ->where('started_at', '<=', now())
                    ->where('ended_at', '>=', now())
                    ->where('available_quantity', '>', 0)
                    ->lockForUpdate()
                    ->first();

                if (!$coupon) {
                    $this->dispatch('openModal', 'alert-modal', [
                        'type'    => 'error',
                        'message' => 'ຄູປອງບໍ່ຖືກຕ້ອງ, ໝົດອາຍຸ ຫຼື ຖືກນຳໃຊ້ແລ້ວ ກະລຸນາກວດສອບອີກຄັ້ງ',
                    ]);
                    DB::rollBack();
                    return;
                }

                // Additional check: if coupon is private (has user_id), verify it belongs to this user
                if ($coupon->user_id !== null && $coupon->user_id !== $user->id) {
                    $this->dispatch('openModal', 'alert-modal', [
                        'type'    => 'error',
                        'message' => 'ທ່ານບໍ່ສາມາດນຳໃຊ້ຄູປອງນີ້ໄດ້',
                    ]);
                    DB::rollBack();
                    return;
                }

                // Validate coupon type
                if (!in_array($coupon->type, ['fixed', 'percentage'])) {
                    $this->dispatch('openModal', 'alert-modal', [
                        'type'    => 'error',
                        'message' => 'ປະເພດຄູປອງບໍ່ຖືກຕ້ອງ',
                    ]);
                    DB::rollBack();
                    return;
                }

                // Validate coupon amount based on type
                if ($coupon->type === 'fixed' && $coupon->amount <= 0) {
                    $this->dispatch('openModal', 'alert-modal', [
                        'type'    => 'error',
                        'message' => 'ຈຳນວນສ່ວນຫຼຸດບໍ່ຖືກຕ້ອງ',
                    ]);
                    DB::rollBack();
                    return;
                }

                if ($coupon->type === 'percentage' && ($coupon->amount <= 0 || $coupon->amount > 100)) {
                    $this->dispatch('openModal', 'alert-modal', [
                        'type'    => 'error',
                        'message' => 'ອັດຕາສ່ວນຫຼຸດບໍ່ຖືກຕ້ອງ',
                    ]);
                    DB::rollBack();
                    return;
                }

                // Check minimum cart amount
                if ($this->cartAmount() < $coupon->minimum_order_amount) {
                    $this->dispatch('openModal', 'alert-modal', [
                        'type'    => 'error',
                        'message' => 'ຍອດລວມຕ້ອງເຖິງຂັ້ນຕ່ຳ ' . number_format($coupon->minimum_order_amount) . ' ກີບ ຈຶ່ງສາມາດນຳໃຊ້ຄູປອງນີ້ໄດ້ ກະລຸນາກວດສອບອີກຄັ້ງ',
                    ]);
                    DB::rollBack();
                    return;
                }

                // Check user daily limit with row locking to prevent race condition
                if ($coupon->user_daily_limit > 0) {
                    $todayStart = CarbonImmutable::now()->startOfDay();
                    $todayEnd = CarbonImmutable::now()->endOfDay();

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
                            $expiresAt = CarbonImmutable::parse($latestPendingOrder->payment_expired_at);

                            if ($expiresAt->isFuture()) {
                                $minutesLeft = now()->diffInMinutes($expiresAt) + 1;
                                $message = 'ຄຳສັ່ງຊື້ກ່ອນໜ້າຂອງທ່ານຍັງບໍ່ທັນສຳເລັດ ກະລຸນາລໍຖ້າ ' . number_format($minutesLeft) . ' ນາທີ ແລ້ວນຳໃຊ້ຄູປອງອີກຄັ້ງ ຫຼື ເອົາຄູປອງອອກ';
                            } else {
                                $message = 'ຄຳສັ່ງຊື້ກ່ອນໜ້າຂອງທ່ານຍັງບໍ່ທັນສຳເລັດ ກະລຸນາລໍຖ້າ 1 ນາທີ ແລ້ວນຳໃຊ້ຄູປອງອີກຄັ້ງ ຫຼື ເອົາຄູປອງອອກ';
                            }
                        } else {
                            $message = 'ທ່ານສາມາດນຳໃຊ້ຄູປອງນີ້ໄດ້ ' . number_format($coupon->user_daily_limit) . ' ຄັ້ງຕໍ່ມື້ ກະລຸນານຳໃຊ້ຄູປອງອື່ນ ຫຼື ເອົາຄູປອງອອກ';
                        }

                        $this->dispatch('openModal', 'alert-modal', [
                            'type'    => 'error',
                            'message' => $message,
                        ]);
                        DB::rollBack();
                        return;
                    }
                }

                // Calculate discount using validated coupon
                if ($coupon->type === 'fixed') {
                    $discountAmount = min($coupon->amount, $this->cartAmount());
                } elseif ($coupon->type === 'percentage') {
                    $percentage = max(0, min(100, $coupon->amount));
                    $discountAmount = min(($this->cartAmount() * $percentage) / 100, $this->cartAmount());
                }
            }

            // ================================================================================
            // Create Order
            // ================================================================================

            $now = CarbonImmutable::now();
            $campaignCode = ShopUtil::getCampaignCode($now);

            $client = new Client();
            $orderId = $client->formattedId('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', 16);

            $orderParams = [
                'id'                       => $orderId,
                'user_id'                  => $user->id,
                'order_amount'             => $this->cartAmount(),
                'shipping_amount'          => $this->cartShippingAmount(),
                'coupon_amount'            => $discountAmount,
                'payment_amount'           => $this->cartAmount() + $this->cartShippingAmount() - $discountAmount - ($this->shippingChannel === 'hal' ? config('custom.shop_discount_hal', 0) : 0),
                'payment_uuid'             => config('custom.payment_uuid_prefix') . $orderId,
                'payment_status'           => 'pending',
                'payment_expired_at'       => $now->addMinutes(5),
                'payment_channel'          => null,
                'shipping_fee_type'        => $this->cartShippingFeeType(),
                'shipping_channel'         => $this->shippingChannel,
                'shipping_name'            => $user->name,
                'shipping_phone'           => $user->phone,
                'shipping_province'        => $this->shippingChannel === 'seller' ? $this->sellerShippingProvinceCode : $user->province,
                'shipping_district'        => $this->shippingChannel === 'seller' ? $this->sellerShippingDistrict : $user->district,
                'shipping_village'         => $this->shippingChannel === 'seller' ? trim($this->sellerShippingVillage) : $user->village,
                'shipping_remark'          => $shippingRemark,
                'shipping_branch_province' => $shippingBranchProvince,
                'shipping_branch_district' => $shippingBranchDistrict,
                'shipping_branch_name'     => $shippingBranchName,
                'shipping_tracking_number' => $shippingTrackingNumber,
                'shipping_status'          => $this->shippingChannel === 'seller' ? 'completed' : 'pending',
                'order_code'               => ShopOrder::generateOrderCode(),
                'shipping_detail'          => $shippingDetail,
                'campaign_code'            => $campaignCode,
            ];

            Log::info('Order params', $orderParams);

            // Validate payment amount
            if ($orderParams['payment_amount'] <= 0) {
                $this->dispatch('openModal', 'alert-modal', [
                    'type'    => 'error',
                    'message' => 'ຈຳນວນເງິນທີ່ຕ້ອງຊຳລະບໍ່ຖືກຕ້ອງ',
                ]);

                DB::rollBack();
                return ;
            }

            // Create order
            $order = ShopOrder::create($orderParams);

            // ================================================================================

            // Decrease product stock and create stock records
            foreach ($lockedProducts as $lockedProduct) {
                // Decrease available quantity
                $lockedProduct['product']->decrement('available_quantity', $lockedProduct['quantity']);

                // Create stock record
                ShopProductStock::create([
                    'shop_order_id'   => $order->id,
                    'shop_product_id' => $lockedProduct['product']->id,
                    'quantity'        => - $lockedProduct['quantity'],
                    'remark'          => 'Order created',
                    'xref'            => 'order_created_' . $order->id . '_' . $lockedProduct['product']->id,
                ]);
            }

            // ================================================================================

            // Create order coupon if coupon was applied
            if ($coupon) {
                ShopOrderCoupon::create([
                    'shop_order_id'          => $order->id,
                    'shop_coupon_id'         => $coupon->id,
                    'user_id'                => $user->id,
                    'coupon_code'            => $coupon->code,
                    'coupon_type'            => $coupon->type,
                    'coupon_amount'          => $coupon->amount,
                    'discount_amount'        => $discountAmount,
                    'before_discount_amount' => $this->cartAmount() + $this->cartShippingAmount(),
                    'minimum_order_amount'   => $coupon->minimum_order_amount,
                    'user_daily_limit'       => $coupon->user_daily_limit,
                    'started_at'             => $coupon->started_at,
                    'ended_at'               => $coupon->ended_at,
                    'created_at'             => $now,
                ]);

                // Decrement available quantity atomically to prevent race condition
                ShopCoupon::query()
                    ->where('id', '=', $coupon->id)
                    ->where('available_quantity', '>', 0)
                    ->decrement('available_quantity');
            }

            // ================================================================================

            // Create order details
            $order->details()->createMany(array_map(function ($cartProduct) {
                return [
                    'shop_product_id' => $cartProduct['product']->id,
                    'quantity'        => $cartProduct['quantity'],
                    'price'           => $cartProduct['product']->price,
                    'name'            => $cartProduct['product']->name,
                    'images'          => $cartProduct['product']->images,
                ];
            }, $this->cartProducts));

            // ================================================================================

            // Update total summary
            $totalProductQuantity = $order->details()->sum('quantity');
            $totalShippingQuantity = 0;

            $order->update([
                'total_product_quantity'  => $totalProductQuantity,
                'total_shipping_quantity' => $totalShippingQuantity,
            ]);

            // ================================================================================

            if ($this->paymentChannel === 'bcel') {
                // GENERATE BCEL QR
                $generateQrPrams = [
                    '00' => '01',
                    '01' => '12',
                    '33' => [
                        '00' => 'BCEL',
                        '01' => 'ONEPAY',
                        '02' => config('custom.bcel_qr_mc_id'),
                        '03' => $order->payment_expired_at->format('YmdHis'),
                    ],
                    '52' => config('custom.bcel_qr_mcc'),
                    '53' => '418',
                    '54' => $order->payment_amount,
                    '58' => 'LA',
                    '60' => 'VTE',
                    '62' => [
                        '01' => $order->id,
                        '05' => $order->payment_uuid,
                        '08' => $order->payment_uuid,
                    ],
                ];

                $generateQrRes = [
                    'data' => [
                        'emv' => BCELUtil::generateQr($generateQrPrams),
                    ],
                ];

                $order->update([
                    'payment_channel'      => 'bcel',
                    'generate_qr_request'  => $generateQrPrams,
                    'generate_qr_response' => $generateQrRes,
                ]);
            }
            else if ($this->paymentChannel === 'jdb') {

                $generateQrRes = JDBUtil::JDBQRGenerateQr($order->payment_uuid, $order->payment_amount);

                if ($generateQrRes['success'] === false) {
                    $this->dispatch('openModal', 'alert-modal', [
                        'type'    => 'error',
                        'message' => 'ບໍ່ສາມາດ Generate QR ໄດ້ (' . $generateQrRes['message'] . ')',
                    ]);

                    DB::rollBack();
                    return;
                }

                $order->update([
                    'payment_channel'      => 'jdb',
                    'generate_qr_request'  => null,
                    'generate_qr_response' => $generateQrRes,
                ]);
            }
            else if ($this->paymentChannel === 'laoqr') {

                $generateQrRes = JDBUtil::JDBQRGenerateQr($order->payment_uuid, $order->payment_amount);

                if ($generateQrRes['success'] === false) {
                    $this->dispatch('openModal', 'alert-modal', [
                        'type'    => 'error',
                        'message' => 'ບໍ່ສາມາດ Generate QR ໄດ້ (' . $generateQrRes['message'] . ')',
                    ]);

                    DB::rollBack();
                    return;
                }

                $order->update([
                    'payment_channel'      => 'laoqr',
                    'generate_qr_request'  => null,
                    'generate_qr_response' => $generateQrRes,
                ]);
            }
            else {
                $this->dispatch('openModal', 'alert-modal', [
                    'type'    => 'error',
                    'message' => 'ບໍ່ພົບຊ່ອງທາງການຈ່າຍ',
                ]);

                DB::rollBack();
                return;
            }

            // ================================================================================

            DB::commit();

            Log::info('Create order', [
                'order' => $order->toArray(),
            ]);

            $this->redirect(route('shop.checkout', ['orderId' => $order->id]));

        } catch (Exception $e) {

            DB::rollBack();

            Log::error('Submit order error', [
                'message' => $e->getMessage(),
                'stack'   => $e->getTraceAsString(),
            ]);

            $this->dispatch('openModal', 'alert-modal', [
                'type'    => 'error',
                'message' => 'ກະລຸນາລອງໃໝ່ພາຍຫຼັງ',
            ]);

        }
    }
}
