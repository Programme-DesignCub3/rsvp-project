<div class="form-group">
    <label class="form-label text-black" for="handicap">HANDICAP:</label>
    <input class="w-full border border-black p-2" id="handicap" placeholder="Select Handicap 1-32" type="number"
        min="1" max="32" step="1" wire:model='handicap' />
    <div>
        @error('handicap')
            <span class="error-form-message">{{ $message }}</span>
        @enderror
    </div>
</div>

<div class="form-group">
    <img src="{{ asset('hardcoded/golf-shirt-size.jpeg') }}" alt="ukuran baju">
</div>

<div class="form-group">
    <label class="form-label text-black" for="shirt_size">SHIRT SIZE:</label>
    <select class="w-full border border-black p-2" id="shirt_size" wire:model="shirt_size">
        <option value="" selected disabled>Select shirt size</option>
        <option value="XS">XS</option>
        <option value="S">S</option>
        <option value="M">M</option>
        <option value="L">L</option>
        <option value="XL">XL</option>
        <option value="XXL">XXL</option>
    </select>

    <div>
        @error('shirt_size')
            <span class="error-form-message">{{ $message }}</span>
        @enderror
    </div>
</div>
