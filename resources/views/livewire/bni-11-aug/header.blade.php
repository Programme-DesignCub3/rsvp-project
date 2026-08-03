<div>
    <img class="mb-6 max-w-48 lg:max-w-[300px]" src="{{ asset('img/logo_bni.png') }}" alt="">

    <div>
        <div>
            <p class="flex items-center space-x-1 text-2xl font-medium leading-none lg:text-[42px]">
                <img class="w-10 lg:w-16" src="{{ asset('img/logo_bni.svg') }}" alt="">
                <span>
                    @if ($this->event->slug == 'bni-networking-meeting-20-may-2025')
                        ONSITE WEEKLY MEETING
                    @elseif ($this->event->slug == 'bni-magnitude-1st-anniversary')
                        ANNIVERSARY DINNER
                    @else
                        NETWORKING MEETING
                    @endif
                </span>
            </p>

            <h1 class="mb-2 text-[40px] font-bold leading-none lg:text-[78px]">REGISTRATION</h1>

            <span class="rounded-lg bg-black p-1 text-xl font-bold uppercase text-white">
                {{ $this->event->start_date_full_formatted }}
            </span>
        </div>
    </div>
</div>
