<div class="flex w-full max-w-full flex-col space-y-4 px-4 py-4 lg:max-w-screen-md lg:px-2">
    <div>
        <img class="mb-6 max-w-48 lg:max-w-[300px]" src="{{ asset('img/logo-bni.png') }}" alt="">

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
    </div>

    <div class="grid grid-cols-1">
        <div class="space-y-4 border border-black px-4 py-4 lg:px-6">
            <h4 class="text-xl font-bold lg:text-2xl">
                {{ $this->event->detail->offline_time_no_seconds }} WIB
            </h4>

            <div>
                <h5 class="text-lg font-bold text-gray-800">LOCATION:</h5>
                {!! $this->event->detail->offline_address !!}
            </div>

            <a class="btn bg-red-bni" href="{{ $this->event->detail->offline_location }}" target="blank">
                GOOGLE MAP
            </a>

            <div>
                <h5 class="text-lg font-bold text-gray-800">ORDER ID:</h5>
                <h4 class="text-xl font-bold lg:text-2xl">
                    #{{ $this->visitor->order_id }}
                </h4>
            </div>
        </div>
    </div>
</div>
