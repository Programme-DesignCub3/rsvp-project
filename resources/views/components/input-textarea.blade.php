@props(['label', 'name', 'id', 'placeholder', 'value' => null])

<div class="mb-4">
    <label class="block text-sm font-bold" for="{{ $id ?? $name }}">
        {{ $label }}
    </label>

    <textarea
        class="mt-1 block max-h-[32rem] min-h-28 w-full resize-none overflow-y-auto rounded-md border bg-white px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 {{ $errors->has($name) ? 'border-red-300' : 'border-gray-300' }}"
        id="{{ $id ?? $name }}" name="{{ $name }}" placeholder="{{ $placeholder ?? $label }}"
        x-data="{
            resize(element) {
                element.style.height = 'auto';
                element.style.height = element.scrollHeight + 'px';
            },
        }"
        x-init="resize($el)"
        @input="resize($event.target)"
        @resize.window="resize($el)">{{ $value ?? old($name) }}</textarea>

    @error($name)
        <span class="error-form-message">{{ $message }}</span>
    @enderror
</div>
