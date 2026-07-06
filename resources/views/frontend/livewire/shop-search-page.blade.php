<div>

  @teleport('search-box')
    <form action="" method="GET" class="flex border border-zinc-300 rounded-xl overflow-hidden bg-zinc-100 mb-4">
      <input type="text" id="search-input" name="search" placeholder="ຄົ້ນຫາສິນຄ້າທີ່ຕ້ອງການເລີຍ" class="border-none flex-1 placeholder:text-zinc-400 focus:ring-0" wire:model="search">
      <button type="submit" class="size-12 flex items-center justify-center">
        <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path fill-rule="evenodd" clip-rule="evenodd" d="M9.62499 1.8335C8.38253 1.8336 7.15811 2.13083 6.05389 2.70038C4.94967 3.26993 3.99766 4.09529 3.27729 5.1076C2.55692 6.11991 2.08908 7.28981 1.9128 8.5197C1.73653 9.74959 1.85693 11.0038 2.26395 12.1777C2.67098 13.3516 3.35283 14.4111 4.25262 15.2679C5.15241 16.1247 6.24405 16.7539 7.43646 17.103C8.62887 17.4521 9.88747 17.5109 11.1073 17.2747C12.327 17.0384 13.4726 16.5139 14.4485 15.7448L17.7962 19.0925C17.969 19.2595 18.2006 19.3519 18.4409 19.3498C18.6813 19.3477 18.9112 19.2513 19.0812 19.0813C19.2511 18.9114 19.3475 18.6815 19.3496 18.4411C19.3517 18.2008 19.2593 17.9692 19.0923 17.7963L15.7447 14.4487C16.6503 13.2997 17.2142 11.919 17.3718 10.4646C17.5294 9.01013 17.2744 7.54068 16.6359 6.22442C15.9973 4.90815 15.0011 3.79824 13.7613 3.02171C12.5214 2.24518 11.088 1.8334 9.62499 1.8335ZM3.66666 9.62516C3.66666 8.04491 4.29441 6.52939 5.41181 5.41198C6.52921 4.29458 8.04474 3.66683 9.62499 3.66683C11.2052 3.66683 12.7208 4.29458 13.8382 5.41198C14.9556 6.52939 15.5833 8.04491 15.5833 9.62516C15.5833 11.2054 14.9556 12.7209 13.8382 13.8383C12.7208 14.9557 11.2052 15.5835 9.62499 15.5835C8.04474 15.5835 6.52921 14.9557 5.41181 13.8383C4.29441 12.7209 3.66666 11.2054 3.66666 9.62516Z" fill="#A1A1AA"/>
        </svg>
      </button>
    </form>
  @endteleport

  @if ($search !== '')

    {{--================================================================================--}}

    @if ($products !== null && count($products) !== 0)
      <livewire:frontend.shop-product-list :products="$products"/>
    @else
      <div class="text-center text-zinc-600 text-sm font-medium m-4 mt-8">ຍັງບໍ່ພົບລາຍການທີ່ຄົ້ນຫາ, ເຮົາມີສິນຄ້າອື່ນແນະນຳສຳລັບທ່ານ</div>
      <livewire:frontend.shop-product-list :products="$recommendProducts"/>
    @endif

    {{--================================================================================--}}

  @else

    <main class="space-y-8">

      {{--================================================================================--}}

      @if ($userSearches !== null && count($userSearches) > 0)
        <section class="m-4 space-y-2">

          <form class="flex space-x-4 items-center" wire:submit.prevent="clearUserSearches">
            <div class="font-semibold text-zinc-950 flex-1">ປະຫວັດການຄົ້ນຫາ</div>
            <button type="submit" class="flex space-x-1 items-center justify-center">
              <span class="text-zinc-500">ລ້າງຂໍ້ມູນການຄົ້ນຫາ</span>
              <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M14.25 3H11.625L10.875 2.25H7.125L6.375 3H3.75V4.5H14.25M4.5 14.25C4.5 14.6478 4.65804 15.0294 4.93934 15.3107C5.22064 15.592 5.60218 15.75 6 15.75H12C12.3978 15.75 12.7794 15.592 13.0607 15.3107C13.342 15.0294 13.5 14.6478 13.5 14.25V5.25H4.5V14.25Z" fill="#71717A"/>
              </svg>
            </button>
          </form>

          <div class="gap-2 flex flex-wrap">
            @foreach ($userSearches as $search)
              <a
                href="{{ route('shop.search', ['search' => $search->search_term]) }}"
                class="bg-zinc-100 rounded-md py-1 px-2 text-sm font-light truncate max-w-40"
                wire:key="user-search-{{ $search->id }}">
                {{ $search->search_term }}
              </a>
            @endforeach
          </div>
        </section>
      @endif

      {{--================================================================================--}}

      @if ($popularProducts !== null && count($popularProducts) > 0)
        <section class="m-4 space-y-2">
          <div class="font-semibold text-zinc-950">ສິນຄ້າຄົ້ນຫາຍອດນິຍົມ</div>
          <div class="popular-products-carousel -mx-2" wire:ignore>
            @foreach ($popularProducts as $product)
              <a
                href="{{ route('shop.product', ['productId' => $product->id]) }}"
                class="relative flex flex-col rounded-lg bg-white text-center mx-2"
                wire:key="popular-product-carousel-{{ $product->id }}">
                <img src="{{ $product->cover_image }}" class="w-full rounded-t-lg">
                <div class="p-2 text-zinc-600">{{ $product->name }}</div>
              </a>
            @endforeach
          </div>
        </section>

        @script
        <script>
          // Also try immediate initialization
          initSiemaCarousel()

          function initSiemaCarousel() {
            const carouselElement = document.querySelector('.popular-products-carousel')

            // Only initialize if element exists and has children
            if (carouselElement && carouselElement.children.length > 0) {
              try {
                new Siema({
                  selector: '.popular-products-carousel',
                  duration: 400,
                  easing: 'ease',
                  perPage: 2,
                  loop: true,
                  draggable: true,
                });
              } catch (error) {
                console.warn('Carousel initialization failed:', error)
              }
            } else {
              // Retry after a short delay if element not found
              setTimeout(initSiemaCarousel, 100)
            }
          }
        </script>
        @endscript
      @endif

    </main>

    {{--================================================================================--}}

  @endif

</div>

@if ($focus === true)
  @script
    <script>
      $nextTick(function() {
        const searchInput = document.getElementById('search-input')
        if (searchInput) {
          searchInput.focus()
        }
      })
    </script>
  @endscript
@endif
