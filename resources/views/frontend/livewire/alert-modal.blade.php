<div class="rounded-xl bg-white p-8 space-y-4">

  <div class="mx-auto w-max">
    @if ($type === 'success')
      <img src="{{ \App\Utils\AppUtil::asset('resources/images/icon-alert-success.svg') }}" width="150" height="150">
    @endif

    @if ($type === 'error')
      <img src="{{ \App\Utils\AppUtil::asset('resources/images/icon-alert-error.svg') }}" width="150" height="150">
    @endif

    @if ($type === 'info')
      <img src="{{ \App\Utils\AppUtil::asset('resources/images/icon-alert-info.svg') }}" width="150" height="150">
    @endif

    @if ($type === 'loading')
      <div class="-mb-2 animate-spin rounded-full border-8 border-t-transparent size-16 border-slate-950"></div>
    @endif
  </div>

  <div class="bg-white p-4 text-center space-y-4">
    <div class="space-y-2">
      <div class="text-xl font-bold text-zinc-950">{!! $message !!}</div>

      @if ($description)
        <div class="text-center text-sm">{{ $description }}</div>
      @endif
    </div>
  </div>

  @if ($type !== 'loading')
    @if ($buttonLink !== null)
      <a href="{{ $buttonLink }}" class="mx-auto block w-10/12 btn btn-gray-primary">{{ $buttonText }}</a>
    @else
      <button type="button" class="btn btn-gray-primary block mx-auto w-10/12 !mt-8 focus:none" tabindex="-1" wire:click="$dispatch('closeModal')">{{ $buttonText }}</button>
    @endif
  @endif

</div>
