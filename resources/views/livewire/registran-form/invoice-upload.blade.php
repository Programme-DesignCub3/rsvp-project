<div class="mb-4 rounded-lg border border-black/10 bg-white/80 p-3 text-sm font-semibold text-black">
    @if ($this->paymentAmountLabel)
        Payment detail will update automatically when you change visitor type or food selection.
    @else
        Payment detail will appear after you select visitor type or food selection.
    @endif
</div>

@if ($this->paymentAmountLabel)
    <div class="mb-4 rounded-lg border border-bni-gold-dark bg-bni-gold p-4 text-black">
        <div>
            <p class="text-sm font-semibold uppercase">Payment detail</p>
            @if ($this->paymentSummaryLabel)
                <p class="text-sm font-semibold">Package for {{ $this->paymentSummaryLabel }}</p>
            @endif
        </div>

        <div class="mt-3 space-y-3 border-t border-black/20 pt-3">
            @foreach ($this->paymentBreakdownGroups as $paymentGroup)
                <div class="space-y-1 text-sm" wire:key="payment-group-{{ $loop->index }}">
                    <p class="font-bold">{{ $paymentGroup['label'] }}</p>

                    <div class="space-y-1">
                        @foreach ($paymentGroup['items'] as $paymentItem)
                            <div class="flex items-start justify-between gap-3"
                                wire:key="payment-item-{{ $loop->parent->index }}-{{ $loop->index }}">
                                @if ($paymentItem['description'])
                                    <p class="text-xs font-semibold">{{ $paymentItem['description'] }}</p>
                                @endif
                                @if ($paymentItem['amount_label'])
                                    <p class="whitespace-nowrap font-extrabold">{{ $paymentItem['amount_label'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-3 flex items-center justify-between gap-3 border-t border-black/20 pt-3">
            <p class="text-sm font-extrabold uppercase">Total payment</p>
            <p class="whitespace-nowrap text-2xl font-extrabold">{{ $this->paymentAmountLabel }}</p>
        </div>
    </div>
@endif

@if ($this->userShouldUploadInvoice)
    <p class="font-semibold">Please transfer payment to <br>
        <strong class="text-lg">
            Bank Jago 101916230906 a/n Stefanny Liezal
        </strong>
    </p>

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
@endif
