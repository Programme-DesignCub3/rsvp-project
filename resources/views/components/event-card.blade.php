@props(['event', 'date' => true])

<li @class([
    'glide__slide grid grid-cols-1 overflow-hidden rounded-b-xl bg-white shadow-[8px_6px_7px_-5px_rgba(0,_0,_0,_0.5)]',
    'grid-rows-[auto_minmax(auto,80px)_80px_auto]' => $date,
    'grid-rows-[auto_minmax(auto,80px)]' => !$date,
])>
    <div class="aspect-[2.56/1] overflow-hidden">
        <img class="w-full object-center" src="{{ $event->getFirstMediaUrl('thumbnail') }}"
            onerror="this.onerror=null;this.src='{{ $event->getFirstMediaUrl('banner') ?? asset('img/banner/webbanner.jpg') }}';"
            alt="">
    </div>

    <div class="flex flex-row-reverse justify-end font-bold">
        <div class="inline-block h-full w-full bg-gray-500/10 p-2 text-base text-black">
            <h2 class="line-clamp-3 !leading-tight max-md:text-lg">
                {{ $event->name }}</h2>
        </div>
        <div class="grid flex-grow-0 basis-[40%] items-center justify-center bg-red-bni capitalize text-white">
            <div class="px-4 text-center uppercase leading-snug">
                <span
                    class="block text-xl md:text-2xl">{{ date('d', strtotime($event->start_date_full_formatted)) }}</span>
                <span
                    class="-mt-[0.35rem] block text-lg md:text-xl">{{ date('M', strtotime($event->start_date_full_formatted)) }}</span>
            </div>
        </div>
    </div>

    @if ($date)
        @if (!$event->coming_soon)

            <div class="px-3 pb-3 pt-1">

                <div class="flex min-h-full flex-col justify-center">
                    @if ($event->is_online_event)
                        <div class="flex flex-row items-center space-x-2 text-black">
                            <x-heroicon-o-globe-alt class="h-4 w-4 text-red-bni" />
                            <div class="col-span-10 text-sm leading-none">
                                ONLINE - {{ $event->detail->online_time_no_seconds }}
                            </div>
                        </div>
                    @endif

                    @if ($event->is_offline_event)
                        <div class="flex flex-row items-center space-x-2 text-black">
                            <x-typ-location class="h-4 w-4 text-red-bni" />
                            <div class="col-span-10 text-sm leading-none">
                                OFFLINE - {{ $event->detail->offline_time_no_seconds }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            {{-- Register Button --}}
            <a class="group bg-red-bni py-4 pb-6 text-center text-xl font-semibold uppercase text-white transition duration-500 ease-in-out hover:bg-red-bni/90"
                href="{{ route('event.show', $event->slug) }}">
                {{-- <span> --}}
                {{-- Register --}}
                READ MORE
                {{-- </span> --}}
                {{-- <x-heroicon-o-arrow-right class="inline h-5 w-5 font-bold" /> --}}
            </a>
        @else
            <div
                class="row-span-3 row-start-3 flex h-[9.2rem] flex-col items-center justify-center px-3 py-4 pb-6 text-2xl uppercase tracking-wider">
                <span>coming</span>
                <span>soon</span>
            </div>
        @endif
    @endif

</li>
