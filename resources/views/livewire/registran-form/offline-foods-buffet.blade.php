@if (count($this->offline_foods) === 1)
    <input class="w-full border border-black p-2 font-extrabold disabled:bg-gray-500" id="food" type="text"
        value="{{ $this->foodOptionLabel($this->offline_foods[0]['food'] ?? null, $this->offline_foods[0]['price'] ?? null) }}"
        readonly />
@else
    @foreach ($this->offline_foods as $key => $item)
        <div class="flex w-full items-center gap-x-4 border border-black p-2 font-extrabold disabled:bg-gray-500">
            <input id="food-{{ $key }}" type="checkbox" value="{{ $item['food'] }}" wire:model.live="food" />
            <label class="flex-grow"
                for="food-{{ $key }}">{{ $this->foodOptionLabel($item['food'] ?? null, $item['price'] ?? null) }}</label>
        </div>
    @endforeach
@endif
