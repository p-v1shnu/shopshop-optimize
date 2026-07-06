<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  @php
    $pageTitle = request()->routeIs('shop.home') && tenant('title')
      ? tenant('title')
      : ($title ?? '');
  @endphp

  <title>{{ $pageTitle }}</title>

  <!-- Open Graph / Facebook -->
  <meta name="title" content="{{ $pageTitle }}">
  <meta property="og:type" content="website">
  <meta property="og:url" content="{{ request()->fullUrl() }}">
  <meta property="og:title" content="{{ $pageTitle }}">
  <meta property="og:image" content="{{ tenant('facebook_cover_url') }}">

  <link rel="stylesheet" href="/fonts/noto-sans-lao/stylesheet.css">
  <link rel="stylesheet" href="/fonts/noto-sans/stylesheet.css">

  @livewireStyles
  @vite(['resources/css/app.scss'])

  <script src="https://unpkg.com/siema@1.5.1/dist/siema.min.js"></script>

  @include('frontend.components.head_before_close_html')
</head>
<body>

  @include('frontend.components.body_after_open_html')

  <div id="app" class="app-container flex flex-col" v-cloak>

    @if ($showNavbar === true)
      @include('frontend.components.navbar')
    @endif

    {{ $slot }}

    @if ($showFooter === true)
      @include('frontend.components.footer')
    @endif

  </div>

  @livewireScripts
  @livewire('wire-elements-modal')
  @vite(['resources/js/app.js'])

  @if (isset($footerJs))
    @vite($footerJs)
  @endif

  @if (auth()->user() === null && request()->get('action') === 'login')
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        Livewire.dispatch('openModal', { component: 'frontend.otp-login-modal' })
      })
    </script>
  @endif

</body>
</html>
