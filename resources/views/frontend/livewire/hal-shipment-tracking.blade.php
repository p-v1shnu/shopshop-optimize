<div>
  <div class="font-bold text-zinc-950">ການເຄື່ອນໄຫວຂອງພັດສະດຸ</div>

  @if (isset($trackingResponseData['tracking_events']) && count($trackingResponseData['tracking_events']) > 0)
    <div class="divide-y divide-zinc-200 -mb-4">

      @foreach($trackingResponseData['tracking_events'] ?? [] as $event)
        <div class="py-4">
          <div class="text-xs text-zinc-600">{{ \Carbon\CarbonImmutable::parse($event['date'])->format('d/m/Y H:i:s') }}</div>
          <div class="text-sm text-zinc-950 font-medium">{{ $event['place'] }}</div>
          <div class="text-sm text-zinc-600">{{ $event['message'] }}</div>
        </div>
      @endforeach

    </div>
  @endif

  @if (isset($trackingResponseData['error']) && $trackingResponseData['error'] === true)
    <div class="alert alert-danger mt-4">{{ $trackingResponseData['message'] }}</div>
  @endif

</div>
