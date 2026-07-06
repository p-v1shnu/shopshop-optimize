@php
  /** @var \App\Models\ShopProduct $product */
  /** @var int $quantity */
@endphp

<div class="flex p-2 pb-0 space-x-4">
  <img src="{{ $product->cover_image }}" class="rounded-lg object-contain size-20">

  <div class="flex-1 space-y-1">
    <div>{{ $product->name }}</div>

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
</div>

<div class="mx-4 -mt-6 mb-2 ml-auto w-max text-zinc-600">ຈຳນວນ x{{ number_format($quantity) }}</div>
