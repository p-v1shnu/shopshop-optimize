<footer class="p-4 space-y-4 text-center bg-[#FBFBFB] mt-auto text-[#7E7E7E]">

  @if (tenant('facebook_name') !== null && tenant('facebook_url') !== null)
    <div class="space-y-2">
      <div class="font-semibold">ຕິດຕາມພວກເຮົາ</div>
      <div class="flex items-center justify-center space-x-4">
        <a href="{{ tenant('facebook_url') }}" target="_blank" rel="nofollow" class="flex items-center justify-center space-x-2">
          <img src="{{ \App\Utils\AppUtil::asset('resources/images/icon-facebook.svg') }}" width="20" height="20">
          <span>{{ tenant('facebook_name') }}</span>
        </a>
      </div>
    </div>
  @endif

  @if (tenant('footer_more_info_text') !== null && tenant('footer_more_info_link') !== null)
    <a href="{{ tenant('footer_more_info_link') }}" target="_blank" rel="nofollow" class="flex items-center justify-center space-x-2">
      <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path fill-rule="evenodd" clip-rule="evenodd" d="M5.31266 5.25V4.875C5.31266 3.97989 5.64849 3.12145 6.24626 2.48851C6.84403 1.85558 7.65479 1.5 8.50016 1.5C9.34554 1.5 10.1563 1.85558 10.7541 2.48851C11.3518 3.12145 11.6877 3.97989 11.6877 4.875V5.25H13.4585C13.8495 5.25 14.1668 5.58675 14.1668 6.00525V15.006C14.1668 15.831 13.5329 16.5 12.7544 16.5H4.24591C3.87156 16.5 3.51252 16.3426 3.24768 16.0625C2.98285 15.7824 2.83387 15.4024 2.8335 15.006V6.006C2.8335 5.5875 3.1487 5.25 3.54183 5.25H5.31266ZM6.37516 5.25H10.6252V4.875C10.6252 4.27826 10.4013 3.70597 10.0028 3.28401C9.60425 2.86205 9.06375 2.625 8.50016 2.625C7.93658 2.625 7.39608 2.86205 6.99756 3.28401C6.59905 3.70597 6.37516 4.27826 6.37516 4.875V5.25ZM5.31266 5.25V8.25H6.37516V5.25H5.31266ZM10.6252 5.25V8.25H11.6877V5.25H10.6252Z" fill="#52525B"/>
      </svg>
      <div class="text-sm font-bold text-zinc-600 underline">{{ tenant('footer_more_info_text') }}</div>
    </a>
  @endif

  <div class="space-y-2">
    <div class="mx-auto flex w-max items-center text-sm space-x-2">
      <img src="{{ \App\Utils\AppUtil::asset('resources/images/icon-phone.svg') }}" width="16" height="16">
      <a href="https://wa.me/{{ tenant('support_contact_phone') }}" target="_blank" rel="nofollow">ສອບຖາມຂໍ້ມູນເພີ່ມຕື່ມ: {{ tenant('support_contact_phone') }}</a>
    </div>
  </div>

  <a href="#" class="block text-sm underline" onclick="Livewire.dispatch('openModal', { component: 'frontend.accept-term-modal', arguments: { isAccept: true } })">ເງື່ອນໄຂ ແລະ ຂໍ້ກຳນົດການໃຊ້ງານ</a>

  <div class="text-center text-xs">Powered by <span class="font-semibold">shopshop.la</span></div>

  <div class="text-center text-xs">Copyright © 2017-{{ date('Y') }} BIZGITAL Co., Ltd. All rights reserved.</div>

</footer>
