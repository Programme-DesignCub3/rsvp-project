<div>
    <div class="flex justify-center">
        @if (!$isSubmitted)
            <div class="flex w-full max-w-full flex-col space-y-4 px-4 py-4 lg:max-w-screen-md lg:px-2">
                <form wire:submit="save">
                    @include('livewire.registran-form.offline-banner')
                    @include('livewire.registran-form.header')
                    @include('livewire.registran-form.registration-fields')

                    @if ($this->event->checkable && $this->event->checkable_one)
                        @include('components.visitor-status-checkable-one')
                    @else
                        @include('components.visitor-status')
                    @endif

                    @if ($this->isOfflineSelected === true)
                        @include('livewire.registran-form.offline-details')
                    @endif

                    @include('livewire.registran-form.submit-button')
                </form>
            </div>
        @else
            @include('livewire.registran-form.success')
        @endif
    </div>
</div>
