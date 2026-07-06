<main class="p-4 space-y-4">

  <div class="font-bold text-zinc-950">ລາຍການຄຳສັ່ງຊື້ຂອງຂ້ອຍ</div>

  @if (in_array('hal', tenant('shipping_channels')))
    <div class="p-3 rounded-xl border border-zinc-300 bg-white grid grid-cols-3 gap-3">
      <button type="button" class="text-center border rounded-lg py-2 {{ $tab === 'pending' ? 'bg-primary text-white' : 'border-zinc-300 bg-gray-50' }}" wire:click="changeTab('pending')">ໄດ້ຮັບອໍເດີ້</button>
      <button type="button" class="text-center border rounded-lg py-2 {{ $tab === 'shipping' ? 'bg-primary text-white' : 'border-zinc-300 bg-gray-50' }}" wire:click="changeTab('shipping')">ກຳລັງຂົນສົ່ງ</button>
      <button type="button" class="text-center border rounded-lg py-2 {{ $tab === 'completed' ? 'bg-primary text-white' : 'border-zinc-300 bg-gray-50' }}" wire:click="changeTab('completed')">ຂົນສົ່ງສຳເລັດ</button>
    </div>
  @endif

  @if (count($orders) === 0)
    <div class="border border-zinc-300 bg-white p-4 py-16 text-center font-bold rounded-2xl">ບໍ່ພົບຄຳສັ່ງຊື້</div>
  @endif

  <div class="space-y-4">
    @foreach($orders as $order)
      <a
        href="{{ route('shop.orderDetail', ['orderId' => $order->id]) }}"
        class="block rounded-2xl border border-zinc-300 bg-white space-y-1"
        wire:key="shop-orders-{{ $order->id }}">

        <div class="flex items-center border-b border-zinc-300 p-2 space-x-4">
          <div class="flex-1">ສັ່ງຊື້ສຳເລັດໃນວັນທີ: {{ $order->created_at->locale('lo')->isoFormat('DD MMM YYYY - HH:mm:ss') }}</div>
          <svg xmlns="http://www.w3.org/2000/svg" width="8.198" height="14.338" viewBox="0 0 8.198 14.338">
            <path id="Icon_ionic-ios-arrow-forward" data-name="Icon ionic-ios-arrow-forward" d="M16.973,13.363,11.547,7.941a1.02,1.02,0,0,1,0-1.447,1.033,1.033,0,0,1,1.451,0l6.147,6.143a1.023,1.023,0,0,1,.03,1.413L13,20.236a1.025,1.025,0,1,1-1.451-1.447Z" transform="translate(-11.246 -6.196)" fill="#373737"/>
          </svg>
        </div>

        <div class="divide-y divide-gray-300">
          @foreach($order->details as $orderDetail)
            <div wire:key="shop-orders-{{ $order->id }}-detail-{{ $orderDetail->id }}">
              @include('frontend.components.shop-order-detail-product', [
                'orderDetail' => $orderDetail,
              ])
            </div>
          @endforeach
        </div>

      </a>
    @endforeach
  </div>

</main>
