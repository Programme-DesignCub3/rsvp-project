<div class="form-group">
    <label class="form-label text-black" for="type">BNI / NON-BNI:</label>
    <select class="w-full border border-black p-2" id="type" wire:model.live="type">
        <option value="" selected disabled>Select registration type</option>
        <option value="bni">BNI</option>
        <option value="non_bni">NON-BNI</option>
    </select>

    <div>
        @error('type')
            <span class="error-form-message">{{ $message }}</span>
        @enderror
    </div>
</div>

@if (! empty($type) && $type !== null)
    @if ($type !== 'bni')
        <div class="form-group">
            <label class="form-label text-black" for="visitor_type">PERSONAL / COMPANY:</label>
            <select class="w-full border border-black p-2" id="visitor_type" wire:model.live="visitor_type">
                <option value="" selected disabled>PERSONAL / COMPANY</option>
                <option value="personal">PERSONAL</option>
                <option value="company">COMPANY</option>
            </select>

            <div>
                @error('visitor_type')
                    <span class="error-form-message">{{ $message }}</span>
                @enderror
            </div>
        </div>

        @if (! empty($visitor_type) && $visitor_type !== null && $visitor_type !== 'personal')
            <div class="form-group">
                <label class="form-label text-black" for="company">COMPANY NAME:</label>
                <input class="w-full border border-black p-2" id="company" type="text" wire:model='company' />
                <div>
                    @error('company')
                        <span class="error-form-message">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        @endif
    @else
        <div class="form-group">
            <label class="form-label text-black" for="chapter">CHAPTER NAME:</label>
            <input class="w-full border border-black p-2" id="chapter" type="text" wire:model='chapter' />
            <div>
                @error('chapter')
                    <span class="error-form-message">{{ $message }}</span>
                @enderror
            </div>
        </div>
    @endif
@endif
