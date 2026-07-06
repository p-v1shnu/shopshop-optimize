<div>
  @if ($show)
    @script
      <script>
        setTimeout(() => {
          Livewire.dispatch('openModal', {
            component: 'frontend.popup-banner-modal',
            arguments: {
              images: @json($images),
            }
          })
        }, 1000)
      </script>
    @endscript
  @endif
</div>
