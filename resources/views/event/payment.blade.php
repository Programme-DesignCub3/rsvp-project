@extends('layouts.app')

@section('page')
    <div class="min-h-screen">
        <div class="mx-auto w-full max-w-screen-md px-4 py-8 lg:px-2">
            @livewire('payment-page', ['event' => $event, 'visitor' => $visitor])
        </div>
    </div>
@endsection
