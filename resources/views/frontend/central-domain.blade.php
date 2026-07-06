<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>{{ setting('title', 'shopshop.la') }}</title>

  <!-- Open Graph / Facebook -->
  <meta name="title" content="{{ setting('title', 'shopshop.la') }}">
  <meta property="og:type" content="website">
  <meta property="og:url" content="{{ request()->fullUrl() }}">
  <meta property="og:title" content="{{ setting('title', 'shopshop.la') }}">
  <meta property="og:image" content="{{ setting('facebook_cover_url', '') }}">

  @vite(['resources/css/app.scss'])

  @include('frontend.components.head_before_close_html')
</head>
<body>

  @include('frontend.components.body_after_open_html')

  <iframe src="{{ setting('landing_page_url', 'https://landing.shopshop.la/en') }}" frameborder="0" class="size-full"></iframe>

</body>
</html>
