@extends('layouts.app')


@section('page')
    <div class="min-h-screen">
        <div>
            {{-- @if ($event->slug == 'visitor-day-11-aug-2026')
                @livewire('bni-11-aug', ['slug' => $slug, 'event' => $event])
            @else
                @livewire('registran-form-component', ['slug' => $slug, 'event' => $event])
                @endif --}}


            @livewire('registran-form-component', ['slug' => $slug, 'event' => $event])
        </div>
    </div>
@endsection

@push('after-scipts')
    @filepondScripts

    <script>
        Livewire.hook('commit', ({
            succeed
        }) => {
            succeed(() => {
                setTimeout(() => {
                    const firstErrorMessage = document.querySelector('.error-form-message')

                    if (firstErrorMessage !== null) {
                        firstErrorMessage.scrollIntoView({
                            block: 'center',
                            inline: 'center'
                        })
                    }
                }, 0)
            })
        })
    </script>
@endpush
