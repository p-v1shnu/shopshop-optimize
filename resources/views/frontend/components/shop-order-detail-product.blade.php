@php
  /** @var \App\Models\ShopOrderDetail $orderDetail */
@endphp

<div class="flex p-2 pb-0 space-x-4">
  <img src="{{ $orderDetail->product->cover_image }}" class="rounded-lg object-contain size-20">

  <div class="flex-1 space-y-1">
    <div>{{ $orderDetail->product->name }}</div>
    <div class="font-bold text-zinc-950">{{ number_format($orderDetail->price) }} ₭</div>
  </div>
</div>

<div class="mx-4 -mt-6 mb-2 ml-auto w-max text-zinc-600">ຈຳນວນ x{{ number_format($orderDetail->quantity) }}</div>
