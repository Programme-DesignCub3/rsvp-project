<div class="space-y-4 border border-black px-4 py-4 lg:px-6">
    <h4 class="text-xl font-bold lg:text-2xl">
        OFFLINE MEETING {{ $this->event->detail->offline_time_no_seconds }} WIB
    </h4>

    <div>
        <h5 class="text-lg font-bold text-gray-800">LOCATION:</h5>
        {!! $this->event->detail->offline_address !!}
    </div>

    <a class="btn bg-red-bni" href="{{ $this->event->detail->offline_location }}" target="blank">
        GOOGLE MAP
    </a>

    <div>
        <div>
            <h5 class="text-lg font-bold text-gray-800">PAKET MAKANAN + MINUMAN</h5>
            <h4 class="text-xl font-bold lg:text-2xl">
                {{ $this->visitor->package }}
            </h4>
        </div>

        <div>
            <h5 class="text-lg font-bold text-gray-800">ORDER ID:</h5>
            <h4 class="text-xl font-bold lg:text-2xl">
                #{{ $this->visitor->order_id }}
            </h4>
        </div>

        <div class="mt-6">
            <h5 class="mb-2 text-lg font-bold">WHAT TO PREPARE</h5>
            <ul class="list-inside list-disc">
                @if ($this->event->slug != 'fun-bay-networking')
                    <li class="text-lg font-medium">Wear Business Attire</li>
                @endif

                <li class="text-lg font-medium">Bring lots of Namecards</li>
                <li class="text-lg font-medium">Prepare Your Business Introduction</li>
                <li class="text-lg font-medium">Please be on-time</li>
            </ul>
        </div>
    </div>
</div>
