<div class="flex w-full max-w-full flex-col space-y-4 px-4 py-4 lg:max-w-screen-md lg:px-2">
    <div>
        <img class="mb-6 max-w-48 lg:max-w-[300px]" src="{{ asset('img/logo-bni.png') }}" alt="">

        @if (!$this->isOnlineSelected && !$this->isOfflineSelected)
            <div class="mb-6">
                <h2 class="mb-2 text-[40px] font-bold leading-none lg:text-[78px]">
                    THANK YOU
                </h2>
                <h2 class="text-[40px] font-medium leading-none lg:text-[42px]">
                    FOR YOUR RESPONSE
                </h2>
            </div>
        @else
            <div class="mb-6">
                <h2 class="mb-2 text-[40px] font-bold leading-none lg:text-[78px]">
                    THANK YOU
                </h2>
                <h2 class="text-[40px] font-medium leading-none lg:text-[42px]">
                    FOR YOUR REGISTRATION
                </h2>
            </div>

            <h2 class="text-[24px] text-xl font-bold">
                SEE YOU ON {{ $this->event->start_date_full_formatted }}!
            </h2>
        @endif
    </div>

    <div class="grid grid-cols-1">
        @if ($this->isOnlineSelected)
            @include('livewire.registran-form.success-online')
        @endif

        @if ($this->isOfflineSelected)
            @include('livewire.registran-form.success-offline')
        @endif
    </div>
</div>
