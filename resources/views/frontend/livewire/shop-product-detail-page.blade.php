<div class="flex flex-1 flex-col overflow-hidden">

  <div class="h-full overflow-y-scroll">

    <section class="space-y-2">

      <div class="bg-white">
        @php
          $images = collect($product->images)
            ->map(fn($image) => $image['filename'])
            ->take(1)
            ->all();
        @endphp

        @foreach ($images as $index => $image)
          <img src="{{ $image }}" class="w-full" wire:key="product-{{ $product->id }}-image-{{ $index }}">
        @endforeach
      </div>

      <div class="bg-white p-4 space-y-4">
        <div class="text-lg font-medium text-zinc-950">{{ $product->name }}</div>
        <div class="flex items-center justify-between space-x-4">

          @if ($product->normal_price !== null && $product->normal_price !== $product->price)
            <div>
              <div class="text-lg font-bold text-red-500">{{ number_format($product->price) }} ₭</div>

              @if ($product->normal_price)
                <div class="text-sm text-zinc-600 line-through">{{ number_format($product->normal_price) }} ₭</div>
              @endif
            </div>
          @else
            <div class="text-lg font-bold text-zinc-950">{{ number_format($product->price) }} ₭</div>
          @endif

          <form
            class="ml-auto w-max"
            wire:key="product-{{ $product->id }}"
            wire:submit="addToCart({{ $product->id }}, 1)">
            <button type="submit" class="btn bg-[#DFEEED] w-max !px-4 space-x-2 !flex items-center">
              <img src="{{ \App\Utils\AppUtil::asset('resources/images/icon-plus.svg') }}" class="size-5">
              <span class="text-zinc-600">ເພີ່ມເຂົ້າກະຕ່າ</span>
            </button>
          </form>

        </div>
      </div>

      @if ($product->long_description)
        <div class="bg-white p-4 text-sm text-zinc-600 prose prose-sm prose-zinc-600">
          {!! $product->long_description !!}
        </div>
      @endif

    </section>

    @if ($recommendProducts->isNotEmpty())
      <section wire:key="product-id-{{ $product->id }}-recommend-shop-product-list">
        <div class="m-4 mb-0 font-bold">ສິນຄ້າແນະນຳສຳລັບທ່ານ</div>
        <livewire:frontend.shop-product-list :products="$recommendProducts"/>
      </section>
    @endif

  </div>

  <footer
    class="bg-white p-4 shadow-t-lg"
    wire:key="product-id-{{ $product->id }}-shop-cart-summary-button">
    <livewire:frontend.shop-cart-summary-button/>
  </footer>

</div>
