@php
  /** @var \App\Models\ShopOrder $order */
@endphp

@if ($order->shipping_channel === 'hal')
  <div class="rounded-2xl border border-zinc-300 bg-white p-4 space-y-2">
    <div>
      <span class="text-zinc-600">ຜູ້ຮັບເຄື່ອງ:</span>
      <span class="mr-3 font-semibold text-zinc-950">{{ $order->shipping_name }}</span>
      <span class="text-zinc-600">{{ $order->shipping_phone }}</span>
    </div>
    <div class="flex space-x-3">
      <img src="{{ \App\Utils\AppUtil::asset('resources/images/hal-logo.png') }}" class="size-8">
      <div>
        <div class="font-bold">ຈັດສົ່ງໂດຍ ຮຸ່ງອາລຸນ ຂົນສົ່ງດ່ວນ</div>
        <div class="text-zinc-600 text-sm">ແຂວງ: {{ $order->shipping_branch_province }} <span class="mx-3">ເມືອງ: {{ $order->shipping_branch_district }}</span> ສາຂາ: {{ $order->shipping_branch_name }}</div>
      </div>
    </div>
  </div>
@endif

@if ($order->shipping_channel === 'seller')
  <div class="flex items-center rounded-2xl border border-zinc-300 bg-white p-4 space-x-4">
    <svg xmlns="http://www.w3.org/2000/svg" width="27" height="36" viewBox="0 0 27 36">
      <path id="Icon_awesome-map-marker-alt" data-name="Icon awesome-map-marker-alt" d="M12.113,35.274C1.9,20.463,0,18.943,0,13.5a13.5,13.5,0,0,1,27,0c0,5.443-1.9,6.963-12.113,21.774a1.688,1.688,0,0,1-2.775,0ZM13.5,19.125A5.625,5.625,0,1,0,7.875,13.5,5.625,5.625,0,0,0,13.5,19.125Z" fill="#ccc"/>
    </svg>
    <div class="text-sm font-light">
      <div><span class="mr-3 font-bold">{{ $order->shipping_name }}</span> {{ $order->shipping_phone }}</div>
      <div>ຂ. {{ \App\Utils\FormUtil::getProvinceName($order->shipping_province) }} <span class="mx-3">ມ. {{ $order->shipping_district }}</span> ບ.{{ $order->shipping_village }}</div>
    </div>
  </div>
@endif

@if ($order->shipping_channel === null)
  <div class="rounded-2xl border border-zinc-300 bg-red-50 p-4 space-y-2 text-center text-sm text-red-400">
    {!! tenant('no_shipping_instruction_text') !!}
  </div>

  <div class="rounded-2xl border border-zinc-300 bg-white p-4 space-y-2">
    <div>
      <div>
        <span class="mr-3 font-semibold text-zinc-950">{{ $order->shipping_name }}</span>
        <span class="text-zinc-950">{{ $order->shipping_phone }}</span>
      </div>
      <div class="text-zinc-600 text-sm">ຂ. {{ \App\Utils\FormUtil::getProvinceName($order->shipping_province) }} <span class="mx-3">ມ. {{ $order->shipping_district }}</span> ບ. {{ $order->shipping_village }}</div>
    </div>
  </div>
@endif
