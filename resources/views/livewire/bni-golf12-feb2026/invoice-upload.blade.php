<p class="font-semibold">Please transfer payment to <br>
    <strong class="text-lg">
        Bank Jago 101916230906 a/n Stefanny Liezal
    </strong>
</p>

<div class="form-group">
    <label class="form-label text-black" for="payment">UPLOAD PROOF OF PAYMENT:</label>
    <input class="w-full border border-black p-2" id="payment" type="file" accept="image/*"
        wire:model.live='payment' name="payment" />
    @if ($payment)
        <div class="bg-gray my-3 px-2">
            <img class="w-full max-w-screen-lg lg:max-w-sm" src="{{ $payment->temporaryUrl() }}" alt="">
        </div>
    @endif

    <div>
        @error('payment')
            <span class="error-form-message">{{ $message }}</span>
        @enderror
    </div>
</div>
