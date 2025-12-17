@props(['showcases'])

<section class="">
    <div
        class="m-auto flex w-dvw flex-col items-center bg-activities bg-cover bg-center bg-no-repeat px-4 py-10 text-white max-lg:-mx-4 md:px-8 md:py-20 lg:-mx-[calc((100vw-64rem)/2)]">

        <x-container>
            <x-event-list-title class="!m-auto !text-2xl font-bold uppercase text-white after:!bg-bni-gold md:!text-5xl">
                OUR EVENTS & ACTIVITIES
            </x-event-list-title>

        </x-container>
        <div class="mx-auto flex w-full max-w-none flex-col px-4 lg:max-w-5xl lg:px-0">
            <div class="mt-8 flex flex-col items-center gap-6 text-center">
                <x-showcase-slider :showcases="$showcases" />
            </div>
        </div>
    </div>

</section>
