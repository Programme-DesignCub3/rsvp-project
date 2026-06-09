<div>
    <div class="flex justify-center">
        @if (!$isSubmitted)
            <div class="flex w-full max-w-full flex-col space-y-4 px-4 py-4 lg:max-w-screen-md lg:px-2">
                <form wire:submit="save">
                    @include('livewire.bni-golf12-feb2026.offline-banner')
                    @include('livewire.bni-golf12-feb2026.header')
                    @include('livewire.bni-golf12-feb2026.registration-fields')

                    @if ($requiresPaymentProof)
                        @include('livewire.bni-golf12-feb2026.invoice-upload')
                    @endif

                    @include('livewire.bni-golf12-feb2026.submit-button')
                </form>
            </div>
        @else
            @include('livewire.bni-golf12-feb2026.success')
        @endif
    </div>
</div>
