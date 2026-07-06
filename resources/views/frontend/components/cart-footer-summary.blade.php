<div class="m-4 space-y-1">
  <div class="flex items-center text-sm space-x-4">
    <div class="flex-1">ຍອດລວມ ({{ number_format($quantity) }} ຈຳນວນ):</div>
    <div>{{ number_format($amount) }} ₭</div>
  </div>

  @if (in_array('hal', tenant('shipping_channels')) || in_array('seller', tenant('shipping_channels')))
    <div class="flex items-center text-sm space-x-4">
      <div class="flex-1">ຄ່າຂົນສົ່ງ:</div>

      @if ($shippingFeeType === 'cod')
        <div>ລູກຄ້າຈ່າຍປາຍທາງ</div>
      @elseif ($shippingFeeType === 'free')
        <div class="text-green-600">ສົ່ງຟຣີ</div>
      @elseif ($shippingFeeType === 'prepaid')
        <div>{{ number_format($shippingFee) }} ກີບ</div>
      @else
        <div class="text-red-600">ບໍ່ພົບເງື່ອນໄຂການຂົນສົ່ງ</div>
      @endif

    </div>
  @endunless

  @if ($discountAmount > 0)
    <div class="flex items-center text-sm space-x-4">
      <div class="flex-1">ສ່ວນຫຼຸດ:</div>
      <div class="text-amber-600">-{{ number_format($discountAmount) }} ₭</div>
    </div>
  @endif

  <div class="flex items-center font-bold text-red-600 space-x-4">
    <div class="flex-1">ຍອດລວມສຸດທິ:</div>
    <div>{{ number_format($netAmount) }} ₭</div>
  </div>
</div>
