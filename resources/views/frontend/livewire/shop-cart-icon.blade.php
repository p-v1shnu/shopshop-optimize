<a href="{{ route('shop.cart') }}" class="relative block ml-auto">
  <img src="{{ \App\Utils\AppUtil::asset('resources/images/icon-navbar-cart.svg') }}" width="26" height="26">
  <span class="absolute -right-1 -bottom-1 flex items-center justify-center rounded-full bg-red-600 text-white shadow size-4 text-[8px]">{{ $cartQuantity }}</span>
</a>
