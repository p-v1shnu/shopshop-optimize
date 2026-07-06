<div class="rounded-xl bg-white overflow-hidden relative h-[90dvh] flex flex-col">

  <button type="button" class="absolute top-4 right-4 bg-white p-1 rounded-full" tabindex="-1" wire:click="$dispatch('closeModal')">
    <svg width="28" height="28" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M16 0C7.08571 0 0 7.08571 0 16C0 24.9143 7.08571 32 16 32C24.9143 32 32 24.9143 32 16C32 7.08571 24.9143 0 16 0ZM22.1714 24L16 17.8286L9.82857 24L8 22.1714L14.1714 16L8 9.82857L9.82857 8L16 14.1714L22.1714 8L24 9.82857L17.8286 16L24 22.1714L22.1714 24Z" fill="#A1A1AA"/>
    </svg>
  </button>

  <div class="m-4 flex-1 overflow-y-scroll">
    @include('frontend.components.term')
  </div>

  <div class="m-4 mt-0">
    <button type="button" class="btn btn-primary mx-auto focus:none" tabindex="-1" wire:click="handleAcceptTerm">ຍອມຮັບ</button>
  </div>

</div>
