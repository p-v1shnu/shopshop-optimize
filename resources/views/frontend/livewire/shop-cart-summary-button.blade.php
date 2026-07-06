<button
  type="button"
  wire:click="goToCart"
  class="flex w-full items-center justify-center rounded-2xl p-4 shadow-lg space-x-4 btn btn-primary btn-shadow-lg">
  <img src="{{ \App\Utils\AppUtil::asset('resources/images/icon-cart-white.svg') }}" class="size-5">
  <strong class="text-base font-semibold">ກະຕ່າສິນຄ້າ ({{ number_format($this->cartQuantity) }}) ມູນຄ່າ {{ number_format($this->cartAmount) }} ₭</strong>
</button>
