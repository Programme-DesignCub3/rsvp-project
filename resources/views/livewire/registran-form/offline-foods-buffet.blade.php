@if (count($this->offline_foods) === 1)
    <input class="w-full border border-black p-2 font-extrabold disabled:bg-gray-500" id="food" type="text"
        wire:model="food" wire:init='food = "{{ $this->offline_foods[0]['food'] }}"' readonly />
@else
    @foreach ($this->offline_foods as $key => $item)
        <div class="flex w-full items-center gap-x-4 border border-black p-2 font-extrabold disabled:bg-gray-500">
            <input id="food-{{ $key }}" type="checkbox" value="{{ $item['food'] }}" wire:model="food" />
            <label class="flex-grow" for="food-{{ $key }}">{{ $item['food'] }}</label>
        </div>
    @endforeach
@endif
