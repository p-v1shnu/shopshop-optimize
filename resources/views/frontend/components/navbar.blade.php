<nav class="navbar">

  <div class="h-14 flex items-center justify-between overflow-hidden">
    @if (optional(request()->route())->getName() !== 'shop.home')
      <a href="#" class="block mr-6" onclick="window.history.go(-1); return false;">
        <img src="{{ \App\Utils\AppUtil::asset('resources/images/icon-navbar-back.svg') }}" width="12" height="24">
      </a>
    @endif

    <a href="{{ route('home') }}" class="block mr-6">
      <img src="{{ \App\Utils\AppUtil::asset('resources/images/icon-navbar-home.svg') }}" width="24" height="24">
    </a>

    <a href="{{ route('home') }}" class="block absolute left-0 right-0 w-max mx-auto">
      <img src="{{ tenant('site_logo_url') }}" width="80" height="38">
    </a>

    <livewire:frontend.shop-cart-icon/>

    <a href="{{ route('profile') }}" class="block ml-6">
      <img src="{{ \App\Utils\AppUtil::asset('resources/images/icon-navbar-profile.svg') }}" width="24" height="24">
    </a>
  </div>

  @if (config('custom.enable_search') === true)
    @if (optional(request()->route())->getName() === 'shop.home')
      <a href="{{ route('shop.search', ['focus' => true]) }}" class="hover:cursor-pointer">
        <div class="pointer-events-none">
          @include('frontend.components.search-box')
        </div>
      </a>
    @endif

    <search-box></search-box>
  @endif

</nav>
