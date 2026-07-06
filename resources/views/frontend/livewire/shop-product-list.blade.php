<div class="grid grid-cols-2 gap-4 p-4">
  @foreach($products as $product)
    <form
      class="relative flex flex-col rounded-lg bg-white text-center"
      wire:key="product-{{ $product->id }}"
      wire:submit="addToCart({{ $product->id }}, 1)">

{{--      <img src="{{ \App\Utils\AppUtil::asset('resources/images/product-pro-badge.png') }}" class="absolute top-0 left-0 h-6 w-16">--}}
      <a href="{{ route('shop.product', ['productId' => $product->id]) }}" class="block space-y-2">
        <img src="{{ $product->cover_image }}" class="w-full rounded-t-lg">
        <div class="px-2 text-zinc-600">{{ $product->name }}</div>
      </a>

      @if ($product->normal_price !== null && $product->normal_price !== $product->price)
        <div class="!mt-auto !mb-2 space-x-1">
          <span class="font-bold text-red-500">{{ number_format($product->price) }} ₭</span>

          @if ($product->normal_price)
            <span class="text-xs text-zinc-600 line-through">({{ number_format($product->normal_price) }} ₭)</span>
          @endif
        </div>
      @else
        <div class="font-bold text-zinc-950 !mt-auto !mb-2">{{ number_format($product->price) }} ₭</div>
      @endif

      <button type="submit" class="btn btn-primary bg-[#DFEEED] text-zinc-600 w-full !px-0 rounded-t-none">ເພີ່ມເຂົ້າກະຕ່າ</button>

    </form>
  @endforeach
</div>
