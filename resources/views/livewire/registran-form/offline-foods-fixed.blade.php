@if ($fixed_food = $this->selectedFixedMenu)
    @if (! empty($fixed_food['food']))
        <input class="w-full border border-black p-2 font-extrabold disabled:bg-gray-500" id="food" type="text"
            value="{{ $fixed_food['food'] }}" readonly />
    @endif

    @if (! empty($fixed_food['drink']))
        <input class="w-full border border-black p-2 font-extrabold disabled:bg-gray-500" id="drink" type="text"
            value="{{ $fixed_food['drink'] }}" readonly />
    @endif

    @if (! empty($fixed_food['price']))
        <p class="relative rounded border border-bni-gold-dark bg-bni-gold px-4 py-3">
            price: {{ $fixed_food['price'] }} <br>
        </p>
    @endif

    @if (! empty($fixed_food['custom']))
        <div class="rounded-md bg-blue-50 p-4">
            <div class="flex gap-2">
                <div class="shrink-0">
                    <svg class="size-5 text-blue-400" data-slot="icon" viewBox="0 0 20 20" fill="currentColor"
                        aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <p class="text-sm text-blue-700">
                    {{ $fixed_food['custom'] }}</p>
            </div>
        </div>
    @endif
@elseif (blank($type))
    <div class="relative rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700" role="alert">
        <strong class="font-bold">Please select visitor type first</strong>
    </div>
@else
    <div class="relative rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700" role="alert">
        <strong class="font-bold">No package configured for this visitor type</strong>
    </div>
@endif
