<div class="flex flex-col gap-y-4 lg:py-4">
    <div class="form-group">
        <label class="form-label text-black" for="type">REGISTER AS:</label>

        <select id="type" name="type" wire:model.change="type" @class(['w-full border border-black p-2'])>
            <option disabled selected value=''> -- select an option -- </option>

            @foreach ($this->visitor_type as $type_item)
                <option value="{{ $type_item->value }}" wire:key="type-{{ $type_item->value }}" @class([])>
                    {{ $type_item->getLabel() }}
                </option>
            @endforeach
        </select>

        <div>
            @error('type')
                <span class="error-form-message">{{ $message }}</span>
            @enderror
        </div>
    </div>

    @if ($this->isVisitorTypeMagnitude)
        <x-magnitude-form />
    @else
        <x-visitor-form />
    @endif
</div>
