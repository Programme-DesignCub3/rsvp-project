@props(['showcase'])
@php
    $url = $showcase->link
        ? (Str::startsWith($showcase->link, ['http://', 'https://'])
            ? $showcase->link
            : 'https://' . $showcase->link)
        : null;
@endphp

@if ($url)
    <a href="{{ $url }}" target="_blank">
@endif

@if ($showcase->has_mobile_asset)
    <picture class="object-contain">
        <source class="object-contain" srcset="{{ $showcase->getFirstMediaUrl('banner_desktop') }}"
            media="(min-width: 1024px)" />

        <img class="object-contain" src="{{ $showcase->getFirstMediaUrl('banner_mobile') }}"
            alt="{{ $showcase->title ? $showcase->title : $showcase->getFirstMedia('banner_mobile')->name }}" />
    </picture>
@else
    <img class="mx-auto block h-full w-full object-contain" src="{{ $showcase->getFirstMediaUrl('banner_desktop') }}"
        alt="{{ $showcase->title ? $showcase->title : $showcase->getFirstMedia('banner_desktop')->name }}" />
@endif

@if ($url)
    </a>
@endif

@php
    unset($url);
@endphp
