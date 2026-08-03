@php
    $foodOptions = $this->alaCarteFoodOptions;
    $drinkOptions = $this->alaCarteDrinkOptions;
@endphp

@if (count($foodOptions) > 0)
    <div class="w-full" wire:key="ala-carte-food-control-{{ $this->event->id }}">
        @if (count($foodOptions) === 1)
            <input class="w-full border border-black p-2 font-extrabold disabled:bg-gray-500" id="food" type="text"
                value="{{ $this->foodOptionLabel($foodOptions[0]['name'], $foodOptions[0]['price']) }}" readonly />
        @else
            <select class="w-full border border-black p-2 font-extrabold" id="food" name="food" wire:model.change="food.food">
                <option required value="">- PLEASE SELECT FOOD -</option>

                @foreach ($foodOptions as $food)
                    <option value="{{ $food['name'] }}" wire:key="ala-carte-food-{{ $this->event->id }}-{{ $loop->index }}">
                        {{ $this->foodOptionLabel($food['name'], $food['price']) }}
                    </option>
                @endforeach
            </select>
        @endif
    </div>
@endif

@if (count($drinkOptions) > 0)
    <div class="w-full" wire:key="ala-carte-drink-control-{{ $this->event->id }}">
        @if (count($drinkOptions) === 1)
            <input class="w-full border border-black p-2 font-extrabold disabled:bg-gray-500" id="drink" type="text"
                value="{{ $this->foodOptionLabel($drinkOptions[0]['name'], $drinkOptions[0]['price']) }}" readonly />
        @else
            <select class="w-full border border-black p-2 font-extrabold" id="drink" required name="drink" wire:model.change="food.drink">
                <option value="">- PLEASE SELECT DRINK -</option>
                @foreach ($drinkOptions as $drink)
                    <option value="{{ $drink['name'] }}" wire:key="ala-carte-drink-{{ $this->event->id }}-{{ $loop->index }}">
                        {{ $this->foodOptionLabel($drink['name'], $drink['price']) }}
                    </option>
                @endforeach
            </select>
        @endif
    </div>
@endif
