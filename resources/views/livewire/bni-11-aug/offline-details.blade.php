<div>
    <div class="block border-b border-dashed border-black pb-2 text-xl lg:border-b-0 lg:pb-0">
        <div class="flex w-36 items-center space-x-4">
            <label for="">
                <img class="max-w-10" src="{{ asset('img/icons/pinpoint.png') }}" alt="">
            </label>
            <label class="text-lg font-bold leading-none text-black">
                OFFLINE MEETING LOCATION
            </label>
        </div>

        <div class="my-2 text-base font-semibold">
            {!! $this->event->detail->offline_address !!}
        </div>
    </div>
    @if (count($this->offline_foods))
        @include('livewire.registran-form.offline-foods')
    @endif

    <div wire:key="offline-invoice-upload-{{ $this->event->id }}">
        @if ($this->event->detail->show_invoice_upload && ($this->userShouldUploadInvoice || $this->paymentAmountLabel))
            @include('livewire.registran-form.invoice-upload')
        @endif
    </div>
</div>
