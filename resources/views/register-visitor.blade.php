@extends('layouts.app')


@section('page')
    <div class="min-h-screen">
        <div>

            @if ($event->slug == 'bni-golf-12-feb-2026')
                @livewire('bni-golf-12-feb-2026', ['slug' => $slug, 'event' => $event])
            @else
                @livewire('registran-form-component', ['slug' => $slug, 'event' => $event])
            @endif
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
