<form class="flex flex-1 flex-col overflow-hidden" wire:submit.prevent="createOrder">

  <div class="flex-1 overflow-y-scroll p-4 space-y-4">

    <div>
      <span class="font-bold text-zinc-950">ກວດສອບຊຳລະ</span>
      <span class="text-zinc-600">(ຈໍານວນສິນຄ້າ {{ number_format($this->cartQuantity) }})</span>
    </div>

    @if ($this->cartShippingRule)
      <div class="alert alert-danger">
        <div>{{ $this->cartShippingRule->shipping_days_text }}</div>
      </div>
    @endif

    <div class="rounded-2xl border border-zinc-300 bg-white p-4 space-y-2">
      <div class="font-semibold">ເລືອກວິທີການຈ່າຍເງິນ</div>
      <div class="rounded-lg px-4 bg-mineral-gray divide-y divide-[#E5E7EB]">

        <button type="button" class="flex w-full items-center py-4 space-x-4" wire:click="updatePaymentChannel('bcel')">
          <div class="flex flex-1 items-center space-x-4">
            <img src="{{ \App\Utils\AppUtil::asset('resources/images/icon-bcelone.svg') }}" class="rounded shadow size-8">
            <span class="text-sm font-medium text-zinc-600">ຈ່າຍເງິນຜ່ານ OnePay</span>
          </div>

          @if ($paymentChannel === 'bcel')
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-primary">
              <circle cx="10" cy="10" r="9.5" fill="white" stroke="currentColor"/>
              <circle cx="10" cy="10" r="4.5" fill="currentColor" stroke="currentColor"/>
            </svg>
          @else
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="10" cy="10" r="9.5" fill="white" stroke="#D6D3D1"/>
            </svg>
          @endif
        </button>

        <button type="button" class="flex w-full items-center py-4 space-x-4" wire:click="updatePaymentChannel('jdb')">
          <div class="flex flex-1 items-center space-x-4">
            <img src="{{ \App\Utils\AppUtil::asset('resources/images/icon-jdbyes.png') }}" class="rounded shadow size-8">
            <span class="text-sm font-medium text-zinc-600">ຈ່າຍເງິນຜ່ານ YesPay</span>
          </div>

          @if ($paymentChannel === 'jdb')
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-primary">
              <circle cx="10" cy="10" r="9.5" fill="white" stroke="currentColor"/>
              <circle cx="10" cy="10" r="4.5" fill="currentColor" stroke="currentColor"/>
            </svg>
          @else
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="10" cy="10" r="9.5" fill="white" stroke="#D6D3D1"/>
            </svg>
          @endif
        </button>

        <button type="button" class="flex w-full items-center py-4 space-x-4" wire:click="updatePaymentChannel('laoqr')">
          <div class="flex flex-1 items-center space-x-4">
            <img src="{{ \App\Utils\AppUtil::asset('resources/images/icon-laoqr.png') }}" class="rounded bg-white shadow size-8 p-0.5">
            <span class="text-sm font-medium text-zinc-600">ຈ່າຍເງິນຜ່ານ Lao QR</span>
          </div>

          @if ($paymentChannel === 'laoqr')
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-primary">
              <circle cx="10" cy="10" r="9.5" fill="white" stroke="currentColor"/>
              <circle cx="10" cy="10" r="4.5" fill="currentColor" stroke="currentColor"/>
            </svg>
          @else
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="10" cy="10" r="9.5" fill="white" stroke="#D6D3D1"/>
            </svg>
          @endif
        </button>

      </div>
    </div>

    {{------------------------------------------------------------------------------------------------}}

    @if ($shippingChannel === 'hal')
      <livewire:frontend.hal-branch-selector wire:key="hal-branch-selector" lazy="on-load"/>
    @endif

    @if ($shippingChannel === 'seller')
      <livewire:frontend.seller-address-selector wire:key="seller-address-selector" />
    @endif

    @if ($shippingChannel === null)
      <div class="rounded-2xl border border-zinc-300 bg-red-50 p-4 space-y-2 text-center text-sm text-red-400">
        {!! tenant('no_shipping_instruction_text') !!}
      </div>

      <div class="rounded-2xl border border-zinc-300 bg-white p-4 space-y-2">
        <div>
          <div>
            <span class="mr-3 font-semibold text-zinc-950">{{ auth()->user()->name }}</span>
            <span class="text-zinc-950">{{ auth()->user()->phone }}</span>
          </div>
          <div class="text-zinc-600 text-sm">ຂ. {{ \App\Utils\FormUtil::getProvinceName(auth()->user()->province) }} <span class="mx-3">ມ. {{ auth()->user()->district }}</span> ບ.{{ auth()->user()->village }}</div>
        </div>
      </div>
    @endif

    {{------------------------------------------------------------------------------------------------}}

    {{-- Coupon Input --}}
    @if (tenant('enable_coupon'))
      <livewire:frontend.shop-coupon-input wire:key="shop-coupon-input" />
    @endif

    {{------------------------------------------------------------------------------------------------}}

    <div class="space-y-4">
      @foreach($cartProducts as $index => $cartProduct)
        <div
          class="rounded-2xl border border-zinc-300 bg-white"
          wire:key="shop-orders-detail-cart-index-{{ $index }}">
          @include('frontend.components.shop-cart-product', [
            'product'  => $cartProduct['product'],
            'quantity' => $cartProduct['quantity'],
          ])
        </div>
      @endforeach
    </div>

  </div>

  <footer class="bg-white shadow-t-lg">

    @include('frontend.components.cart-footer-summary', [
      'quantity'        => $this->cartQuantity,
      'amount'          => $this->cartAmount,
      'shippingFeeType' => $this->cartShippingFeeType,
      'shippingFee'     => $this->cartShippingAmount,
      'discountAmount'  => $this->cartDiscountAmount,
      'netAmount'       => $this->cartNetAmount,
    ])

    <div class="m-4">
      <button type="submit" class="flex w-full items-center justify-center rounded-2xl p-4 shadow-lg space-x-4 btn btn-shadow-lg {{ $this->isSubmitButtonEnabled ? 'btn-primary' : 'btn-muted' }}">ໄປໜ້າຈ່າຍເງິນ</button>
    </div>

  </footer>

</form>
