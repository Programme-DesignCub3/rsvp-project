<div class="flex flex-col gap-y-4 pt-2">
    <div class="my-2 flex flex-col gap-y-1">
        @if ($this->event->detail->food_type === App\Enums\FoodType::BUFFET)
            @include('livewire.registran-form.offline-foods-buffet')
        @elseif ($this->event->detail->food_type === App\Enums\FoodType::ALA_CARTE)
            @include('livewire.registran-form.offline-foods-ala-carte')
        @elseif ($this->event->detail->food_type === App\Enums\FoodType::FIXED)
            @include('livewire.registran-form.offline-foods-fixed')
        @endif

        <div>
            @error('food')
                <span class="error-form-message">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>
