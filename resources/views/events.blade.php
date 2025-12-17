@extends('layouts.app')

@php
    // Meta Desc (clean_description) limit text upto 160 characters
    $metaDesc =
        'Official Website BNI Magnitude. We are a thriving and dynamic community of professionals and business owners, united by a shared commitment to excellence, collaboration, and trust. Through the power of strategic connections and mutual support, we work together to deliver impactful solutions and help each other grow.';
@endphp

@section('head', 'All Events')
@section('description', $metaDesc)
@section('image', asset('img/banner/webbanner.jpg'))

@section('page')

    <div class="relative h-auto w-full">
        <video class="h-auto w-full object-cover" src="{{ asset('videos/home-header-vid-1.mp4') }}" autoplay loop
            muted></video>

        <img class="mx-auto -mt-[clamp(2rem,20vw,8rem)] w-full max-w-[min(100%,640px)]"
            src="{{ asset('img/LogoChapter.png') }}" alt="Logo Chapter">
    </div>

    <section class="flex min-h-dvh flex-row space-x-5 overflow-clip bg-landing bg-bottom bg-no-repeat">
        <div class="mx-auto w-full max-w-none px-4 lg:max-w-5xl lg:px-0">

            <x-event-list-title>
                UPCOMING EVENTS
            </x-event-list-title>

            {{-- desktop slider --}}
            <x-event-list.desktop :events="$events" />

            {{-- mobile grid --}}
            <x-event-list.mobile :events="$events" />

            @include('components.magnitude-home-pitch')

            <x-home-why-bni />

            @if (!$showcases->isEmpty())
                <x-activities :showcases="$showcases" />
            @endif

            <x-event-list-title>
                LATEST UPDATES
            </x-event-list-title>

            <!-- Place <div> tag where you want the feed to appear -->
            <div class="mx-8 md:mx-16">
                <div id="curator-feed-default-feed-layout"><a class="crt-logo crt-tag" href="https://curator.io"
                        target="_blank">Powered by Curator.io</a></div>
            </div>

            <x-event-list-title>
                Past events
            </x-event-list-title>

            {{-- past events --}}
            {{-- desktop slider --}}
            <x-past-event-list.slider :events="$past_events" />

        </div>
    </section>
@endsection

@push('after-scipts')
    @vite('resources/js/landing.js')

    <!-- The Javascript can be moved to the end of the html page before the </body> tag -->
    <script type="text/javascript">
        /* curator-feed-default-feed-layout */
        (function() {
            var i, e, d = document,
                s = "script";
            i = d.createElement("script");
            i.async = 1;
            i.charset = "UTF-8";
            i.src = "https://cdn.curator.io/published/11955bd7-94c0-4ee3-81fc-ab2366ac7161.js";
            e = d.getElementsByTagName(s)[0];
            e.parentNode.insertBefore(i, e);
        })();
    </script>
@endpush
