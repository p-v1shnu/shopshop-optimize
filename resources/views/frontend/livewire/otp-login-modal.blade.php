<div class="relative rounded-xl shadow bg-[#F5F5F5] p-8">

  <button type="button" class="absolute top-4 right-4" tabindex="-1" wire:click="$dispatch('closeModal')">
    <svg width="28" height="28" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M16 0C7.08571 0 0 7.08571 0 16C0 24.9143 7.08571 32 16 32C24.9143 32 32 24.9143 32 16C32 7.08571 24.9143 0 16 0ZM22.1714 24L16 17.8286L9.82857 24L8 22.1714L14.1714 16L8 9.82857L9.82857 8L16 14.1714L22.1714 8L24 9.82857L17.8286 16L24 22.1714L22.1714 24Z" fill="#A1A1AA"/>
    </svg>
  </button>

  <img src="{{ tenant('site_logo_url') }}" class="mx-auto my-8 w-6/12">

  @if ($step === 'phone')
    <form wire:submit.prevent="sendOtp" class="space-y-4">

      <div class="text-center space-y-2">
        <div class="text-[#3D3D3D] text-lg font-bold">ເຂົ້າສູ່ລະບົບ</div>
        <div class="text-zinc-600">
          <div>ປ້ອນເບີໂທລະສັບຂອງທ່ານ</div>
        </div>
      </div>

      <input
        type="tel" inputmode="numeric" pattern="[0-9]*"
        class="text-center form-control"
        oninput="this.value = this.value.replace(/\D/, '')"
        placeholder="20XXXXXXXX ຫຼື 30XXXXXXX"
        autofocus
        wire:model="phone"/>

      <button type="submit" class="btn btn-gray-primary !mt-8 mx-auto">ຖັດໄປ</button>

    </form>
  @endif

  @if ($step === 'otp')
    <form wire:submit.prevent="verifyOtp" class="space-y-4">

      <div class="text-center space-y-2">
        <div class="text-[#3D3D3D] text-lg font-bold">ເຂົ້າສູ່ລະບົບ</div>
        <div class="text-zinc-600">
          <div>ປ້ອນລະຫັດ OTP ຈຳນວນ 6 ໂຕເລກ</div>
          <div>ທີ່ສົ່ງໄປເບີ {{ $phone }}</div>
        </div>
      </div>

      <input
        type="tel" inputmode="numeric" pattern="[0-9]*"
        class="text-center form-control"
        oninput="this.value = this.value.replace(/\D/, '')"
        placeholder="XXXXXX"
        autofocus
        wire:model="otp"/>

      <div type="button" class="text-center text-sm text-zinc-600">
        ບໍ່ໄດ້ຮັບລະຫັດ? <a href="#" class="underline" wire:click.prevent="backToPhoneStep">ສົ່ງລະຫັດອີກຄັ້ງ</a>
      </div>

      <button type="submit" class="btn btn-gray-primary !mt-8 mx-auto">ສຳເລັດ</button>

    </form>
  @endif

</div>
