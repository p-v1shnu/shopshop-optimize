<div>
  @isset($jsPath)
    <script>{!! file_get_contents($jsPath) !!}</script>
  @endisset
  @isset($cssPath)
    <style>{!! file_get_contents($cssPath) !!}</style>
  @endisset

  <div
    x-data="LivewireUIModal()"
    x-on:close.stop="setShowPropertyTo(false)"
    x-on:keydown.escape.window="closeModalOnEscape()"
    x-show="show"
    class="fixed inset-0 z-[9999] overflow-y-auto"
    style="display: none;"
  >
    <div class="flex items-center justify-center min-h-dvh px-4 pt-4 pb-10 text-center">
      <div
        x-show="show"
        x-on:click="closeModalOnClickAway()"
        x-transition:enter="ease-out duration-100"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 transition-all transform">
        <div class="absolute inset-0 bg-black/80"></div>
      </div>

      <span class="hidden" aria-hidden="true">&#8203;</span>

      <div
        x-show="show && showActiveComponent"
        x-transition:enter="ease-out duration-100"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        x-bind:class="modalWidth"
        class="inline-block mx-auto w-[400px] align-bottom text-left overflow-hidden transform transition-all"
        id="modal-container"
        x-trap.noscroll.inert="show && showActiveComponent"
        aria-modal="true">
        @forelse($components as $id => $component)
          <div x-show.immediate="activeComponent == '{{ $id }}'" x-ref="{{ $id }}" wire:key="{{ $id }}">
            @livewire($component['name'], $component['arguments'], key($id))
          </div>
        @empty
        @endforelse
      </div>
    </div>
  </div>
</div>
