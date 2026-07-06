<div class="flex flex-1 flex-col overflow-hidden">

  <div class="h-full overflow-y-scroll p-4 space-y-4">
    <div>
      <span class="font-bold">ກະຕ່າສິນຄ້າ</span>
      <span class="text-zinc-600">(ຈໍານວນສິນຄ້າ {{ number_format($this->cartQuantity) }})</span>
    </div>

    @if (count($cartProducts) > 0)

      <div class="mb-4 space-y-4">
        @foreach ($cartProducts as $cartProduct)

          @php
            $product = $cartProduct['product'];
            $quantity = $cartProduct['quantity'];
            $readOnly = false;
          @endphp

          <div
            class="rounded-lg border border-zinc-300 bg-white pb-4"
            wire:key="shop-cart-product-{{ $product->id }}">

            <div class="flex p-4 pb-0 space-x-4">
              <img src="{{ $product->cover_image }}" class="rounded object-contain size-20">

              <div class="flex-1 space-y-1">
                <a href="{{ route('shop.product', ['productId' => $product->id]) }}">{{ $product->name }}</a>

                @if ($product->normal_price !== null && $product->normal_price !== $product->price)
                  <div>
                    <div class="font-bold text-red-500">{{ number_format($product->price) }} ₭</div>

                    @if ($product->normal_price)
                      <div class="text-xs text-zinc-600 line-through">{{ number_format($product->normal_price) }} ₭</div>
                    @endif
                  </div>
                @else
                  <div class="font-bold text-zinc-950">{{ number_format($product->price) }} ₭</div>
                @endif
              </div>

              @unless ($readOnly)
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 17 20" class="ml-auto cursor-pointer" wire:click="removeFromCart({{ $product->id }})">
                  <path id="Icon_ionic-md-trash" data-name="Icon ionic-md-trash" d="M8.45,22.283A2.249,2.249,0,0,0,10.717,24.5h9.067a2.249,2.249,0,0,0,2.267-2.217V9.5H8.45ZM23.75,6.167H19.5L18.077,4.5H12.423L11,6.167H6.75V7.833h17Z" transform="translate(-6.75 -4.5)" fill="#a4a4a4"/>
                </svg>
              @endunless
            </div>

            @if ($readOnly)
              <div class="mx-4 -mt-4 ml-auto w-max text-sm">ຈຳນວນ x{{ number_format($cartProduct['quantity']) }}</div>
            @else
              <div class="mx-4 -mt-4 ml-auto flex w-max items-center text-zinc-950">
                <button type="button" class="flex items-center justify-center rounded bg-zinc-200 size-6" wire:click="decreaseQuantity({{ $product->id }}, 1)">-</button>
                <div class="flex h-6 w-8 items-center justify-center">{{ number_format($cartProduct['quantity']) }}</div>
                <button type="button" class="flex items-center justify-center rounded bg-zinc-200 size-6" wire:click="increaseQuantity({{ $product->id }}, 1)">+</button>
              </div>
            @endif
          </div>

        @endforeach
      </div>

    @else

      <div class="rounded-2xl border border-zinc-300 bg-white p-4 py-16 text-center font-bold">ບໍ່ມີສິນຄ້າໃນກະຕ່າ</div>

      <a href="{{ \App\Utils\ShopUtil::getHomeUrl() }}" class="btn btn-primary w-full !flex items-center justify-center space-x-4">
        <img src="{{ \App\Utils\AppUtil::asset('resources/images/icon-cart-white.svg') }}" class="size-5">
        <div>ຊື້ສິນຄ້າ</div>
      </a>

    @endif

  </div>

  @if (count($cartProducts) !== 0)
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
        <button type="button" class="flex w-full items-center justify-center rounded-2xl p-4 shadow-lg space-x-4 btn btn-primary btn-shadow-lg" wire:click="goToShippingPage">ໄປໜ້າກວດສອບຊຳລະ</button>
      </div>

    </footer>
  @endif

</div>
