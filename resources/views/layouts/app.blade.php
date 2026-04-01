<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    @include('includes.analytics')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Winky+Sans:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet">

    @livewireStyles

    @vite('resources/css/app.css')

    <title>RSVP by Designcub3 | @yield('head', 'Home')</title>

    <meta name="title" content="RSVP by Designcub3 | @yield('head', 'Home')" />
    <meta name="description" content="@yield('description', 'Designcub3 RSVP')" />

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:title" content="RSVP by Designcub3 | @yield('head', 'Home')" />
    <meta property="og:description" content="@yield('description', 'Designcub3 RSVP')" />
    <meta property="og:image" content="@yield('image', asset('img/thumb.jpg'))" />

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image" />
    <meta property="twitter:url" content="{{ url()->current() }}" />
    <meta property="twitter:title" content="RSVP by Designcub3 | @yield('head', 'Home')" />
    <meta property="twitter:description" content="@yield('description', 'Designcub3 RSVP')" />
    <meta property="twitter:image" content="@yield('image', asset('img/thumb.jpg'))" />

    @stack('before-scripts')

</head>

<body class="relative bg-white antialiased">
    <div class="top-0 z-20 sticky bg-red-bni">

        @php
            $navItems = [
                [
                    'name' => 'Events',
                    'href' => route('event.index'),
                    'slug' => 'events',
                    'subItems' => [],
                ],
                [
                    'name' => 'Members',
                    'href' => route('members.index'),
                    'slug' => 'members',
                    'subItems' => [
                        ...\App\Models\MemberCategory::all()
                            ->append(['href'])
                            ->map(function ($category) {
                                $category->href = route('members.index', ['category' => $category->slug]);
                                return $category;
                            }),
                    ],
                ],
                [
                    'name' => 'Contact',
                    'href' => route('contact.index'),
                    'slug' => 'contact',
                    'subItems' => [],
                ],
            ];
        @endphp

        <div class="mx-auto px-4 lg:px-0 w-full max-w-none lg:max-w-5xl">
            <x-container>
                <nav class="z-10 relative w-auto" x-data="{
                    width: 0,
                    height: 0,
                    windowWidth: 0,
                    windowHeight: 0,
                    navigationMenuOpen: false,
                    navigationMenuOpenMobile: false,
                    navigationMenu: '',
                    navigationMenuCloseDelay: 200,
                    navigationMenuCloseTimeout: null,
                    isBreakpoint(width) {
                        const breakpoints = {
                            'sm': 640,
                            'md': 768,
                            'lg': 1024,
                            'xl': 1280,
                            '2xl': 1536,
                        };

                        const isNumber = typeof width === 'number'

                        const matchingBreakpoint = isNumber ?
                            width :
                            Object.keys(breakpoints).find(key => key === width)

                        if (!matchingBreakpoint) {
                            return false;
                        }

                        return isNumber ? this.windowWidth >= matchingBreakpoint : this.windowWidth >= breakpoints[matchingBreakpoint];
                    },
                    navigationMenuLeave() {
                        let that = this;
                        this.navigationMenuCloseTimeout = setTimeout(() => {
                            that.navigationMenuClose();
                        }, this.navigationMenuCloseDelay);
                    },
                    navigationMenuReposition(navElement) {
                        this.navigationMenuClearCloseTimeout();
                        // this.$refs.navigationDropdown.style.left = navElement.offsetLeft + 'px';
                        // this.$refs.navigationDropdown.style.left = 0 + 'px';
                        // this.$refs.navigationDropdown.style.marginLeft = (navElement.offsetWidth / 2) + 'px';
                    },
                    navigationMenuClearCloseTimeout() {
                        clearTimeout(this.navigationMenuCloseTimeout);
                    },
                    navigationMenuClose() {
                        this.navigationMenuOpen = false;
                        this.navigationMenu = '';
                    }
                }" x-resize="width = $width; height = $height"
                    x-resize.document="windowHeight = $height; windowWidth = $width;" x-init="width = $el.offsetWidth;
                    height = $el.offsetHeight;
                    windowHeight = window.innerHeight;
                    windowWidth = window.innerWidth;">
                    <div
                        class="relative flex justify-between items-center mx-auto px-8 xl:px-0 max-lg:py-4 w-full max-w-none lg:max-w-5xl">
                        <div class="brightness-0 invert w-full max-w-24">
                            <img src="{{ asset('img/logo-bni.png') }}" alt="">
                        </div>

                        {{-- /* ****************** Desktop Main Nav ****************** */ --}}
                        <template x-if="isBreakpoint('lg')">
                            <ul
                                class="max-lg:hidden flex flex-wrap justify-end items-center max-sm:items-end gap-[clamp(0.2rem,4vw,2.3rem)] md:mx-16 h-full font-semibold text-white uppercase">
                                @foreach ($navItems as $item)
                                    <li class="py-[clamp(0.5rem,4vw,2.3rem)] lg:py-8"
                                        @if (!empty($item['subItems'])) @mouseover="() => {
                                                if (isBreakpoint('lg')) {
                                                    navigationMenuOpen=true; navigationMenuReposition($el); navigationMenu='{{ $item['slug'] }}'
                                                }
                                            }"
                                        @mouseleave="navigationMenuLeave()" @endif>
                                        @if (!empty($item['href']) || $item['slug'] === 'members')
                                            <a href="{{ $item['href'] }}">
                                                <span>{{ $item['name'] }}</span>
                                            </a>
                                        @else
                                            <p>{{ $item['name'] }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </template>

                        <template x-if="!isBreakpoint('lg')">
                            <div class="z-50 relative w-auto h-auto" x-data="{
                                slideOverOpen: false
                            }">

                                <x-lucide-menu
                                    class="lg:hidden inline-flex justify-center items-center disabled:opacity-50 px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-neutral-200/60 focus:ring-offset-2 h-10 font-medium text-white text-sm transition-colors cursor-pointer disabled:pointer-events-none basis-10"
                                    @click="slideOverOpen=true" />

                                <template x-teleport="body">
                                    <div class="z-[99] relative" x-show="slideOverOpen"
                                        @keydown.window.escape="slideOverOpen=false">
                                        <div class="fixed inset-0 bg-black bg-opacity-10" x-show="slideOverOpen"
                                            x-transition.opacity.duration.600ms @click="slideOverOpen = false"></div>
                                        <div class="fixed inset-0 overflow-hidden">
                                            <div class="absolute inset-0 overflow-hidden">
                                                <div class="right-0 fixed inset-y-0 flex pl-10 max-w-full">
                                                    <div class="w-screen max-w-md" x-show="slideOverOpen"
                                                        @click.away="slideOverOpen = false"
                                                        x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                                                        x-transition:enter-start="translate-x-full"
                                                        x-transition:enter-end="translate-x-0"
                                                        x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                                                        x-transition:leave-start="translate-x-0"
                                                        x-transition:leave-end="translate-x-full">
                                                        <div
                                                            class="flex flex-col bg-white shadow-lg py-5 border-neutral-100/70 border-l h-full overflow-y-scroll">
                                                            <div class="px-4 sm:px-5">
                                                                <div class="flex justify-between items-start pb-1">

                                                                    <div class="w-full max-w-24">
                                                                        <img src="{{ asset('img/logo-bni.png') }}"
                                                                            alt="">
                                                                    </div>

                                                                    <div class="flex items-center ml-3 h-auto">
                                                                        <button
                                                                            class="top-0 right-0 z-30 absolute flex justify-center items-center space-x-1 hover:bg-neutral-100 mt-4 mr-5 px-3 py-2 border border-neutral-200 rounded-md font-medium text-neutral-600 text-xs uppercase"
                                                                            @click="slideOverOpen=false">
                                                                            <svg class="w-4 h-4"
                                                                                xmlns="http://www.w3.org/2000/svg"
                                                                                fill="none" viewBox="0 0 24 24"
                                                                                stroke-width="1.5"
                                                                                stroke="currentColor">
                                                                                <path stroke-linecap="round"
                                                                                    stroke-linejoin="round"
                                                                                    d="M6 18L18 6M6 6l12 12"></path>
                                                                            </svg>
                                                                            <span>Close</span>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="relative flex-1 mt-5 px-4 sm:px-5">
                                                                <ul class="flex flex-col gap-8">
                                                                    @foreach ($navItems as $item)
                                                                        <li class="flex flex-col gap-2"
                                                                            @if (!empty($item['subItems'])) x-data="{ open: false }" @endif>
                                                                            @if (!empty($item['subItems']))
                                                                                <div class="flex items-center gap-2 cursor-pointer"
                                                                                    @click="open = !open">
                                                                            @endif
                                                                            <a
                                                                                href="{{ $item['href'] }}">{{ $item['name'] }}</a>
                                                                            @if (!empty($item['subItems']))
                                                                                <div :class="open && 'rotate-90'">
                                                                                    <x-lucide-chevron-right
                                                                                        class="inline-block w-4 h-4 transition-transform duration-300" />
                                                                                </div>
                                                            </div>
                                                            @endif
                                                            @if (!empty($item['subItems']))
                                                                <ul class="flex flex-col gap-6 ml-4" x-show="open"
                                                                    x-transition>
                                                                    @foreach ($item['subItems'] as $subItem)
                                                                        <li class="flex flex-col gap-2">
                                                                            <a href="{{ $subItem['href'] ?? '#' }}">
                                                                                {{ $subItem['name'] }}
                                                                            </a>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            @endif
                                                            </li>
                                                            @endforeach
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            </div>
                        </template>
                    </div>
                    </template>
        </div>


        {{-- /* ********************** DESKTOP SUBITEMS ********************** */ --}}
        <template x-if="isBreakpoint('lg')">
            <div class="left-0 z-10 fixed w-dvw duration-200 ease-out" :style="{ 'margin-bottom': -height + 'px' }"
                x-ref="navigationDropdown" x-show="navigationMenuOpen"
                x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-90"
                x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90"
                @mouseover="navigationMenuClearCloseTimeout()" @mouseleave="navigationMenuLeave()" x-cloak>
                <div
                    class="flex justify-center bg-white shadow-sm border border-neutral-200/70 w-auto h-auto overflow-hidden">
                    @foreach ($navItems as $item)
                        @if (!empty($item['subItems']))
                            <div class="relative flex max-lg:flex-col justify-between items-start gap-x-3 mx-auto px-2 py-4 w-full max-w-none lg:max-w-5xl"
                                x-show="navigationMenu == '{{ $item['slug'] }}'">
                                <div class="flex-shrink-0 max-lg:pb-4 lg:basis-56">
                                    <x-event-list-title class="!m-auto !w-full !basis-3/4">
                                        Member By Business Categories
                                    </x-event-list-title>
                                </div>
                                <div class="gap-6 grid lg:grid-cols-2">
                                    @foreach ($item['subItems'] as $subItem)
                                        <a class="block hover:bg-neutral-100 px-3.5 py-3 rounded text-sm"
                                            href="{{ $subItem->href ?? '#' }}" @click="navigationMenuClose()">
                                            <span
                                                class="block mb-1 font-medium text-black">{{ $subItem->name }}</span>
                                            @if (!empty($subItem->description))
                                                <span
                                                    class="block opacity-50 font-light leading-5">{{ $subItem->description }}</span>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </template>
        </nav>
        </x-container>

    </div>

    </div>

    @yield('page')

    <div
        class="right-[clamp(0.5rem,5vw,2rem)] bottom-[clamp(2rem,20vw,3.5rem)] hover:bottom-[clamp(3rem,30vw,4.5rem)] z-50 fixed drop-shadow-lg hover:drop-shadow-2xl size-[clamp(2rem,12vw,4rem)] transition-all duration-700 ease-in-out">
        <a target="_blank" href="https://api.whatsapp.com/send?phone=628161306769">
            <img src="{{ asset('img/wa.png') }}" alt="whatsapp contact">
        </a>
    </div>

    <footer class="bottom-0 z-40 sticky flex justify-center items-center bg-navy px-2 py-4">
        <h4 class="inline-flex pt-[0.3rem] text-white text-sm lg:text-base">BNI Magnitude Official Website | Powered By
        </h4>
        <a class="inline-flex" href="https://designcub3.com" rel="noopener noreferrer" target="_blank">
            <img class="mt-1 ml-2 w-24 lg:w-12" src="{{ asset('img/logo.svg') }}" alt="">
            {{-- <img class="ml-2 w-24 lg:w-32" src="{{ asset('img/footer-logo.gif') }}" alt=""> --}}
        </a>
    </footer>

    @livewireScripts

    @stack('after-scipts')
</body>

</html>
