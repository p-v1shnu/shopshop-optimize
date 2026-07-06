@php
  $images = tenant('homepage_banners') ?? [];
@endphp

<div class="header-banner-carousel w-full max-w-2xl overflow-hidden" wire:ignore>
  @foreach ($images as $index => $image)
    <img
      src="{{ $image }}"
      class="w-full flex-shrink-0 object-cover"
      wire:key="header-banner-carousel-image-{{ $index }}" />
  @endforeach
</div>

<script>
  const headerBannerCarousel = new Siema({
    selector: '.header-banner-carousel',
    duration: 400,
    easing: 'ease',
    perPage: 1,
    loop: true,
    draggable: true,
  })

  setInterval(function () {
    headerBannerCarousel.next()
  }, 4000)
</script>
