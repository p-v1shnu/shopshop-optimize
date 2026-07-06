<div class="flex flex-1 flex-col overflow-y-scroll">

  <main class="h-full overflow-y-scroll p-4 space-y-4">

    <div>
      <div class="font-bold text-zinc-950">ຈ່າຍເງິນ</div>
      <div class="text-sm text-zinc-600">ກະລຸນາຈ່າຍເງິນຂອງລາຍການສິນຄ້າຜ່ານ QR Code ດ້ານລຸ່ມນີ້</div>
    </div>

    <div class="border border-gray-300 p-4 text-center text-sm font-bold space-y-4 bg-white">
      <div>{{ config('custom.jdb_qr_mc_name') }}</div>
      <div class="mx-auto w-max rounded-lg bg-white p-2 shadow drop-shadow-lg relative">
        <img src="{{ \App\Utils\AppUtil::asset('resources/images/icon-laoqr.png') }}" width="24" height="24" class="mx-auto shadow rounded bg-white p-0.5 absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
        <img src="data:image/png;base64,{{ \Milon\Barcode\Facades\DNS2DFacade::getBarcodePNG($order->generate_qr_response['data']['emv'], 'QRCODE', 140, 140) }}" width="140" height="140">
      </div>
      <div>{{ $order->id }}</div>
    </div>

    @include('frontend.components.shop-shipping-detail', ['order' => $order])

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

  <footer class="right-0 bottom-0 left-0 bg-white shadow-t-lg">

    @include('frontend.components.cart-footer-summary', [
      'quantity'        => $order->order_quantity,
      'amount'          => $order->order_amount,
      'shippingFeeType' => $order->shipping_fee_type,
      'shippingFee'     => $order->shipping_amount,
      'discountAmount'  => $order->coupon_amount,
      'netAmount'       => $order->payment_amount,
    ])

    <div class="m-4">
      <button type="button" class="flex w-full items-center justify-center rounded-2xl border border-gray-300 bg-gray-50 p-2 space-x-4" onclick="window.open('https://jdbbank.com.la/yespay/{{ $order->generate_qr_response['data']['emv'] }}', '_blank')">
        <img src="{{ \App\Utils\AppUtil::asset('resources/images/icon-jdbyes.png') }}" class="rounded shadow size-10">
        <span class="font-semibold">ຈ່າຍເງິນຜ່ານ YesPay</span>
      </button>
    </div>

  </footer>

  @include('frontend.components.purchase-datalayer')

</div>
