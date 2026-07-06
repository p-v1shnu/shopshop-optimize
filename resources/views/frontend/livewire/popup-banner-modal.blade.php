<div class="relative space-y-2 pb-4">

  @php
    $images = tenant('popup_banners') ?? [];
  @endphp

  <div class="popup-banner-carousel w-full rounded-lg max-w-2xl overflow-hidden" wire:ignore>
    @foreach ($images as $index => $image)
      <img
        src="{{ $image }}"
        class="w-full"
        wire:key="popup-banner-carousel-image-{{ $index }}" />
    @endforeach
  </div>

  <!-- Dot indicators -->
  @if (count($images) > 1)
    <div class="flex justify-center space-x-2 py-2">
      @foreach ($images as $index => $image)
        <button
          type="button"
          class="dot-indicator size-2.5 rounded-full transition-colors duration-200 outline-none {{ $index === 0 ? 'bg-white' : 'bg-gray-500' }}"
          data-slide="{{ $index }}"
          wire:key="dot-{{ $index }}">
        </button>
      @endforeach
    </div>
  @endif

  <button type="button" class="mx-auto w-max btn btn-primary focus:outline-none focus:ring-0" wire:click="$dispatch('closeModal')">ກົດເພື່ອປິດ</button>

</div>

@script
  <script>
    (function() {
      // Wait for the element to be visible in the DOM
      function initCarousel() {
        const carouselElement = document.querySelector('.popup-banner-carousel');

        // Check if element exists and is visible
        if (!carouselElement || carouselElement.offsetParent === null) {
          requestAnimationFrame(initCarousel);
          return;
        }

        const popupBannerCarousel = new Siema({
          selector: '.popup-banner-carousel',
          duration: 400,
          easing: 'ease',
          perPage: 1,
          loop: true,
          draggable: true,
          onChange: function() {
            updateDots(this.currentSlide);
          }
        })

        // Update dot indicators
        function updateDots(currentIndex) {
          const dots = document.querySelectorAll('.dot-indicator')
          dots.forEach(function (dot, index) {
            if (index === currentIndex) {
              dot.classList.add('bg-white')
              dot.classList.remove('bg-gray-500')
            } else {
              dot.classList.remove('bg-white')
              dot.classList.add('bg-gray-500')
            }
          })
        }

        // Add click handlers to dots
        document.querySelectorAll('.dot-indicator').forEach(function (dot, index) {
          dot.addEventListener('click', function () {
            popupBannerCarousel.goTo(index)
          })
        })

        setInterval(function () {
          popupBannerCarousel.next()
        }, 4000)
      }

      // Start initialization
      requestAnimationFrame(initCarousel);
    })();
  </script>
@endscript
