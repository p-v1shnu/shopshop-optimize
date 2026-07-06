<main class="p-4 space-y-4">

  @if ($showPaidBanner === true)

    @if ($order->shipping_channel === 'hal')
      <div class="flex items-center rounded-2xl border border-zinc-300 bg-lime-50 p-4 space-x-4">
        <img src="{{ \App\Utils\AppUtil::asset('resources/images/icon-alert-success.svg') }}" width="60" height="60">
        <div>
          <div class="font-bold text-zinc-950">ການສັ່ງຊື້ສຳເລັດ</div>
          <div class="text-zinc-600 text-sm">ຮ້ານຄ້າຈະກະກຽມສິນຄ້າເພື່ອຈັດສົ່ງໃຫ້ທ່ານໄວໆນີ້</div>
        </div>
      </div>
    @endif

    @if ($order->shipping_channel === 'seller')
      <div class="flex items-center rounded-2xl border border-zinc-300 bg-lime-50 p-4 space-x-4">
        <img src="{{ \App\Utils\AppUtil::asset('resources/images/icon-alert-success.svg') }}" width="60" height="60">
        <div>
          <div class="font-bold text-zinc-950">ການສັ່ງຊື້ສຳເລັດ</div>
          <div class="text-zinc-600 text-sm">ຮ້ານຄ້າຈະກະກຽມສິນຄ້າເພື່ອຈັດສົ່ງໃຫ້ທ່ານໄວໆນີ້</div>
        </div>
      </div>
    @endif

    @if ($order->shipping_channel === null)
      <div class="flex items-center rounded-2xl border border-zinc-300 bg-lime-50 p-4 space-x-4">
        <img src="{{ \App\Utils\AppUtil::asset('resources/images/icon-alert-success.svg') }}" width="60" height="60">
        <div>
          <div class="font-bold text-zinc-950">ການສັ່ງຊື້ສຳເລັດ</div>
          <div class="text-zinc-600 text-sm">{{ tenant('no_shipping_order_paid_text') }}</div>
        </div>
      </div>
    @endif

  @endif

  <div class="font-bold text-zinc-950">ລາຍລະອຽດຄຳສັ່ງຊື້</div>

  @if ($order->payment_status !== 'paid')
    <div class="alert alert-danger text-lg font-bold !text-red-600">
      ຄຳສັ່ງຊື້ຍັງບໍ່ໄດ້ຊຳລະເງິນ
    </div>
  @endif

  <section class="rounded-2xl border border-zinc-300 bg-white p-4 space-y-1">
    <div class="flex items-center space-x-4">
      <div class="font-semibold">ໝາຍເລກຄຳສັ່ງຊື້</div>
      <div class="flex-1 text-right">{{ $order->id }}-{{ $order->order_code }}</div>
    </div>

    <div class="flex items-center space-x-4">
      <div>ສັ່ງຊື້ວັນທີ</div>
      <div class="flex-1 text-right">{{ $order->created_at->locale('lo')->isoFormat('DD MMM YYYY - HH:mm:ss') }}</div>
    </div>

    <div class="flex items-center space-x-4">
      <div>ຊ່ອງທາງການຊຳລະເງິນ</div>
      <div class="flex-1 text-right uppercase">{{ strtoupper($order->payment_channel) }}</div>
    </div>

    <div class="flex items-center space-x-4">
      <div>ເລກບິນ</div>
      <div class="flex-1 text-right">{{ $order->payment ? $order->payment->ref : 'ຍັງບໍ່ໄດ້ຊຳລະເງິນ' }}</div>
    </div>

    <div class="flex items-center space-x-4">
      <div>ເລກອ້າງອີງ</div>
      <div class="flex-1 text-right">{{ $order->payment_uuid }}</div>
    </div>
  </section>

  {{------------------------------------------------------------------------------------------------}}

  @if ($order->shipping_channel === 'hal')
    <section class="border border-zinc-300 bg-white rounded-2xl space-y-4 p-4">

      <div class="text-gray-600 space-x-2">
        <span>ຜູ້ຮັບເຄື່ອງ:</span>
        <span class="text-gray-900 font-semibold">{{ $order->shipping_name }}</span>
        <span>{{ $order->shipping_phone }}</span>
      </div>

      <div class="flex items-center space-x-2">
        <img src="{{ \App\Utils\AppUtil::asset('resources/images/hal-logo.png') }}" class="size-8"/>
        <span class="text-gray-900 font-semibold">ຈັດສົ່ງໂດຍ ຮຸ່ງອາລຸນ ຂົນສົ່ງດ່ວນ</span>
      </div>

      <div class="flex items-center justify-center space-x-4 px-4 text-zinc-950 text-sm">
        <div>{{ $order->shipping_detail['pre_order']['start_branch']['name'] }}</div>
        <svg width="16" height="16" viewBox="0 0 18 16" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-8">
          <g clip-path="url(#clip0_808_4899)"><path d="M1.62402 8.00001H16.375M9.92402 1.54601L16.378 8.00001L9.92402 14.453" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></g>
          <defs>
            <clipPath id="clip0_808_4899">
              <rect width="16.751" height="15.736" fill="white" transform="translate(0.624023 0.132004)"/>
            </clipPath>
          </defs>
        </svg>
        <div>{{ $order->shipping_detail['pre_order']['end_branch']['name'] }}</div>
      </div>

      <div class="text-center space-y-1 border-y border-zinc-200 pt-4 pb-2">
        <img src="data:image/png;base64,{{ \Milon\Barcode\Facades\DNS1DFacade::getBarcodePNG($order->shipping_tracking_number, 'C39', 2, 64) }}" class="w-full">
        <div>{{ $order->shipping_tracking_number }}</div>
      </div>

      <livewire:frontend.hal-shipment-tracking
        wire:key="hal-shipment-tracking"
        lazy="on-load"
        :order="$order"/>

    </section>
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

  {{------------------------------------------------------------------------------------------------}}

  <section class="rounded-2xl border border-zinc-300 bg-white p-4 space-y-1">
    <div class="flex items-center space-x-4">
      <div>ຍອດລວມ ({{ $order->details->sum('quantity') }} ຈຳນວນ)</div>
      <div class="flex-1 text-right">{{ number_format($order->order_amount) }} ₭</div>
    </div>

    @if ($order->shipping_channel === 'self')
      <div class="flex items-center space-x-4">
        <div>ຄ່າຂົນສົ່ງ</div>

        @if ((float) $order->shipping_amount === 0.0)
          <div class="flex-1 text-right text-green-600">ຟຣີ</div>
        @else
          <div class="flex-1 text-right">{{ number_format($order->shipping_amount) }} ₭</div>
        @endif
      </div>
    @endif

    @unless($order->shipping_channel === null)
      <div class="flex items-center space-x-4">
        <div class="flex-1">ຄ່າຂົນສົ່ງ:</div>

        @if ($order->shipping_fee_type === 'cod')
          <div>ລູກຄ້າຈ່າຍປາຍທາງ</div>
        @elseif ($order->shipping_fee_type === 'free')
          <div class="text-green-600">ສົ່ງຟຣີ</div>
        @elseif ($order->shipping_fee_type === 'prepaid')
          <div>{{ number_format($order->shipping_amount) }} ກີບ</div>
        @else
          <div class="text-red-600">ບໍ່ພົບເງື່ອນໄຂການຂົນສົ່ງ</div>
        @endif
      </div>
    @endunless

    @if ($order->coupon_amount > 0)
      <div class="flex items-center space-x-4">
        <div class="flex-1">ສ່ວນຫຼຸດຄູປອງ:</div>
        <div class="text-amber-600">-{{ number_format($order->coupon_amount) }} ₭</div>
      </div>
    @endif

    <div class="flex items-center text-base font-bold text-red-600 space-x-4">
      <div>ຍອດລວມສຸດທິ</div>
      <div class="flex-1 text-right">{{ number_format($order->payment_amount) }} ₭</div>
    </div>
  </section>

  {{------------------------------------------------------------------------------------------------}}

  @if (tenant('latitude') && tenant('longitude'))
    <section class="rounded-2xl border border-zinc-300 bg-white p-4 space-y-4">
      <div class="font-bold text-zinc-950">ສະຖານທີ່</div>
      <div class="w-full h-48 rounded-2xl border border-zinc-300 overflow-hidden">
        <iframe
          width="100%"
          height="100%"
          frameborder="0"
          class="border-0"
          referrerpolicy="no-referrer-when-downgrade"
          src="https://www.google.com/maps/embed/v1/place?key={{ config('custom.google_map_api_key') }}&q={{ tenant('latitude') }},{{ tenant('longitude') }}&zoom=15">
        </iframe>
      </div>
      <a href="https://maps.google.com/?q={{ tenant('latitude') }},{{ tenant('longitude') }}" class="text-[#F04251] border border-[#F04251] flex items-center justify-center w-full space-x-4 rounded-lg p-2 min-h-12" target="_blank" rel="noopener noreferrer">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
          <g clip-path="url(#clip0_6031_277)">
            <g clip-path="url(#clip1_6031_277)">
              <path d="M15.8984 9.41406C13.6371 9.41406 11.7969 11.2543 11.7969 13.5156C11.7969 14.3791 12.063 15.2054 12.5734 15.9143L15.4298 19.7654C15.5402 19.913 15.7142 20 15.8984 20H15.899C16.0833 20 16.2572 19.9125 16.3677 19.7648L19.3397 15.7449C19.7717 15.0812 20 14.3104 20 13.5156C20 11.2543 18.1598 9.41406 15.8984 9.41406ZM15.8984 15.2734C14.9291 15.2734 14.1406 14.4849 14.1406 13.5156C14.1406 12.5463 14.9291 11.7578 15.8984 11.7578C16.8677 11.7578 17.6562 12.5463 17.6562 13.5156C17.6562 14.4849 16.8677 15.2734 15.8984 15.2734Z" fill="#F04251"/>
              <path d="M0.8875 0.0833769C0.706094 -0.0247872 0.48125 -0.0281856 0.296992 0.075955C0.113281 0.180057 0 0.374627 0 0.58576V12.3436C0 12.5495 0.108164 12.7401 0.284375 12.846L5.89844 16.1909V3.06662L0.8875 0.0833769Z" fill="#F04251"/>
              <path d="M18.5437 3.5991L12.9688 0.253906V9.1348C13.8075 8.57211 14.8148 8.24211 15.8984 8.24211C16.9821 8.24211 17.9894 8.57211 18.8281 9.1348V4.10148C18.8281 3.89551 18.72 3.70496 18.5437 3.5991Z" fill="#F04251"/>
              <path d="M11.7969 0.253906L7.07031 3.06664V16.191L10.6673 14.0563C10.6488 13.8767 10.625 13.6979 10.625 13.5155C10.625 12.2727 11.075 11.1441 11.7969 10.2418V0.253906Z" fill="#F04251"/>
            </g>
          </g>
          <defs>
            <clipPath id="clip0_6031_277">
              <rect width="20" height="20" fill="white" transform="matrix(1 0 0 -1 0 20)"/>
            </clipPath>
            <clipPath id="clip1_6031_277">
              <rect width="20" height="20" fill="white"/>
            </clipPath>
          </defs>
        </svg>
        <span>ເປີດແຜນທີ່ນຳທາງ</span>
      </a>
    </section>
  @endif

  {{------------------------------------------------------------------------------------------------}}

  <div class="space-y-4">
    @foreach($order->details as $orderDetail)
      <div
        class="rounded-2xl border border-zinc-300 bg-white"
        wire:key="shop-orders-{{ $order->id }}-detail-{{ $orderDetail->id }}">
        @include('frontend.components.shop-order-detail-product', [
          'orderDetail' => $orderDetail,
        ])
      </div>
    @endforeach
  </div>

</main>
