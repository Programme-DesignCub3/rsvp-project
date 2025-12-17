@props(['showcases'])
<div class="glide" id="glide-showcase">
    <div class="mx-8 md:mx-16">
        <div class="glide__track" data-glide-el="track">
            <ul class="glide__slides items-center">
                @foreach ($showcases as $showcase)
                    <li class="glide__slide">
                        <x-showcase-slider-slide :showcase="$showcase" />
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="glide__bullets -mb-14" data-glide-el="controls[nav]">
        @foreach ($showcases as $showcase)
            <button class="glide__bullet" data-glide-dir="={{ $loop->index }}"></button>
        @endforeach
    </div>

    <div class="glide__arrows" data-glide-el="controls">
        <button class="glide__arrow glide__arrow--left -left-4 border-none shadow-none sm:left-0" data-glide-dir="<">
            <x-heroicon-o-arrow-left-circle class="size-8 text-white" />
        </button>

        <button class="glide__arrow glide__arrow--right -right-4 border-none shadow-none sm:right-0" data-glide-dir=">">
            <x-heroicon-o-arrow-right-circle class="size-8 text-white" />
        </button>
    </div>
</div>
