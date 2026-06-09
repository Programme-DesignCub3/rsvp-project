<p class="font-semibold">Please transfer payment to <br>
    <strong class="text-lg">
        Bank Jago 101916230906 a/n Stefanny Liezal
    </strong>
</p>

@if ($this->paymentAmountLabel)
    <div class="rounded-lg border border-bni-gold-dark bg-bni-gold px-4 py-3">
        <p class="text-sm font-semibold uppercase text-black">Total payment</p>
        <p class="text-2xl font-extrabold text-black">{{ $this->paymentAmountLabel }}</p>
        @if ($this->selectedVisitorTypeLabel)
            <p class="text-sm font-semibold text-black">
                Package for {{ $this->selectedVisitorTypeLabel }}
            </p>
        @endif
    </div>
@endif

<div class="rounded-lg bg-gray-200 p-2">
    <p class="mb-2">Sertakan Berita dengan format penulisan:
        <strong>"Chapter/Visitor" + "Nama" @if ($this->event->slug == 'fun-bay-networking')
                + APR22
            @endif </strong>
    </p>
    <p>Contoh:</p>

    @if ($this->event->slug == 'fun-bay-networking')
        <ul class="list-inside list-disc pl-1 lg:pl-2">
            <li class="font-semibold">Magnitude Deddy + APR22</li>
            <li class="font-semibold">Altitude Edo + APR22</li>
            <li class="font-semibold">Visitor Daniel + APR22</li>
        </ul>
    @else
        <ul class="list-inside list-disc pl-1 lg:pl-2">
            <li class="font-semibold">Magnitude Deddy</li>
            <li class="font-semibold">Altitude Edo</li>
            <li class="font-semibold">Visitor Daniel</li>
        </ul>
    @endif
</div>

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
