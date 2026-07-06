<div class="flex flex-1 flex-col overflow-hidden">

  <div class="h-full overflow-y-scroll">

    @include('frontend.components.header-banner')

    <header class="m-4 text-center space-y-4">
      <div class="text-lg font-semibold text-zinc-950">ຊື້ຜະລິດຕະພັນ {{ tenant('name') }}</div>

      @auth
        <div class="m-4 flex items-center justify-center text-center space-x-2">
          <img src="{{ \App\Utils\AppUtil::asset('resources/images/icon-avatar.svg') }}" width="24" height="24">
          <div class="text-zinc-950">ສະບາຍດີ {{ auth()->user()->name }}</div>
        </div>
      @endauth

      @guest
        <div class="m-4 space-y-2">
          <div class="text-center text-zinc-600">ທ່ານຍັງບໍ່ທັນເຂົ້າສູ່ລະບົບ</div>
          <button
            type="button"
            class="w-full btn btn-green-primary btn-outline"
            onclick="Livewire.dispatch('openModal', {
              component: 'frontend.otp-login-modal',
              arguments: { backUrl: window.location.href }
            })">
            ເຂົ້າສູ່ລະບົບ
          </button>
        </div>
      @endguest
    </header>

    <livewire:frontend.shop-product-list :products="$products"/>

    @include('frontend.components.footer')

  </div>

  <footer class="bg-white p-4 shadow-t-lg">
    <livewire:frontend.shop-cart-summary-button/>
  </footer>

  <livewire:frontend.popup-banner/>

</div>
