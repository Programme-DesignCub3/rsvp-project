<div class="flex flex-col gap-y-4 pt-2" wire:key="offline-foods-{{ $this->event->id }}-{{ $this->event->detail->food_type->value }}">
    <div class="my-2 flex flex-col gap-y-1">
        <div wire:key="offline-food-controls-{{ $this->event->id }}-{{ $this->event->detail->food_type->value }}">
            @if ($this->event->detail->food_type === App\Enums\FoodType::BUFFET)
                @include('livewire.registran-form.offline-foods-buffet')
            @elseif ($this->event->detail->food_type === App\Enums\FoodType::ALA_CARTE)
                @include('livewire.registran-form.offline-foods-ala-carte')
            @elseif ($this->event->detail->food_type === App\Enums\FoodType::FIXED)
                @include('livewire.registran-form.offline-foods-fixed')
            @endif
        </div>

        <div wire:key="offline-food-errors-{{ $this->event->id }}">
            @error('food')
                <span class="error-form-message">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>
