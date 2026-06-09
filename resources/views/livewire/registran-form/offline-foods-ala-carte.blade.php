@if (count($this->offline_foods[0]['food']))
    @if (count($this->offline_foods[0]['food']) === 1)
        <input class="w-full border border-black p-2 font-extrabold disabled:bg-gray-500" id="food" type="text"
            wire:model="food.food" wire:init='food.food = "{{ $this->offline_foods[0]['food'][0] }}"' readonly />
    @else
        <select id="food" name="food" wire:model="food.food">
            <option required value="">- PLEASE SELECT FOOD -</option>

            @foreach ($this->offline_foods[0]['food'] as $food)
                <option value="{{ $food }}">
                    {{ $food }}
                </option>
            @endforeach
        </select>
    @endif
@endif

@if (count($this->offline_foods[0]['drink']))
    @if (count($this->offline_foods[0]['drink']) === 1)
        <input class="w-full border border-black p-2 font-extrabold disabled:bg-gray-500" id="drink" type="text"
            wire:model="food.drink" wire:init='food.drink = "{{ $this->offline_foods[0]['drink'][0] }}"' readonly />
    @else
        <select id="drink" required name="drink" wire:model="food.drink">
            <option value="">- PLEASE SELECT DRINK -</option>
            @foreach ($this->offline_foods[0]['drink'] as $drink)
                <option value="{{ $drink }}">
                    {{ $drink }}
                </option>
            @endforeach
        </select>
    @endif
@endif
