<div class="form-group">
    <label class="form-label text-black" for="name">FULL NAME:</label>
    <input class="w-full border border-black p-2" id="name" type="text" wire:model.blur="name" />
    <div>
        @error('name')
            <span class="error-form-message">{{ $message }}</span>
        @enderror
    </div>
</div>

<div class="form-group">
    <label class="form-label text-black" for="phone">MOBILE PHONE / WHATSAPP:</label>
    <input class="w-full border border-black p-2" id="phone" type="tel" wire:model='phone' />
    <div>
        @error('phone')
            <span class="error-form-message">{{ $message }}</span>
        @enderror
    </div>
</div>

<div class="form-group">
    <label class="form-label text-black" for="email">EMAIL:</label>
    <input class="w-full border border-black p-2" id="email" type="email" wire:model='email' />
    <div>
        @error('email')
            <span class="error-form-message">{{ $message }}</span>
        @enderror
    </div>
</div>
