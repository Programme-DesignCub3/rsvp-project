<div class="flex w-full flex-col gap-6">
    <div>
        <img class="mb-6 max-w-48 lg:max-w-[300px]" src="{{ asset('img/logo-bni.png') }}" alt="">

        <h2 class="text-[40px] font-bold leading-none lg:text-[64px]">
            PAYMENT
        </h2>
        <p class="mt-3 text-lg font-semibold">
            Registration #{{ $this->visitor->registration_sequence }} &mdash; {{ $this->visitor->name }}
        </p>
        <p class="text-base font-medium">
            {{ $this->visitor->type ? \App\Enums\VisitorType::tryFrom($this->visitor->type)?->getLabel() : '' }}
        </p>
    </div>

    <div class="space-y-4 border border-black px-4 py-4 lg:px-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h4 class="text-lg font-bold">TOTAL PAYMENT</h4>
                @if ($this->visitor->payment_amount > 0)
                    <p class="text-sm font-semibold text-gray-700">
                        Transfer the exact amount so we can match your payment.
                    </p>
                @else
                    <p class="text-sm font-semibold text-gray-700">
                        No transfer needed for this registration.
                    </p>
                @endif
            </div>
            <p class="whitespace-nowrap text-3xl font-extrabold">
                {{ $this->visitor->payment_amount > 0 ? 'Rp ' . number_format($this->visitor->payment_amount, 0, ',', '.') : 'Gratis' }}
            </p>
        </div>

        @if ($this->visitor->payment_amount > 0)
            <div class="space-y-1 border-t border-black/20 pt-3 text-sm">
                <div class="flex justify-between gap-4">
                    <span>Registration fee</span>
                    <span class="font-semibold">Rp {{ number_format($this->visitor->payment_base_amount, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span>Unique code</span>
                    <span class="font-semibold">{{ $this->uniqueCodeAmount }}</span>
                </div>
            </div>

            <div class="space-y-2 border-t border-black/20 pt-3">
                <p class="text-sm font-semibold">Transfer to</p>
                <p class="text-lg font-extrabold">Bank Jago 101916230906 a/n Stefanny Liezal</p>
                <p class="text-sm font-semibold">
                    BERITA: <span class="text-base font-extrabold">{{ $this->visitor->payment_berita }}</span>
                </p>
            </div>
        @endif

        <div class="border-t border-black/20 pt-3">
            @if ($this->visitor->payment_verified)
                <p class="rounded bg-green-100 px-3 py-2 text-sm font-bold text-green-800">
                    Payment verified.
                </p>
            @elseif ($this->hasPaymentProof)
                <p class="rounded bg-yellow-100 px-3 py-2 text-sm font-bold text-yellow-800">
                    Payment proof received. Waiting for verification.
                </p>

                <a class="mt-3 inline-block text-sm font-semibold underline"
                    href="{{ $this->visitor->getFirstMediaUrl('payment_proof') }}" target="_blank" rel="noreferrer">
                    View uploaded proof
                </a>
            @endif

            @if ($this->requiresPaymentProof)
                <form class="mt-4 space-y-3" wire:submit="submitPaymentProof">
                    <label class="block text-sm font-bold" for="payment">UPLOAD PROOF OF PAYMENT</label>
                    <input class="w-full border border-black p-2" id="payment" type="file" accept="image/*"
                        wire:model="payment" name="payment">

                    @if ($payment)
                        <img class="max-w-sm" src="{{ $payment->temporaryUrl() }}" alt="Payment proof preview">
                    @endif

                    @error('payment')
                        <span class="error-form-message">{{ $message }}</span>
                    @enderror

                    @if (session('payment-proof-uploaded'))
                        <p class="text-sm font-semibold text-green-700">{{ session('payment-proof-uploaded') }}</p>
                    @endif

                    <button class="btn bg-red-bni" type="submit" wire:loading.attr="disabled">
                        UPLOAD PROOF
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4">
        @if ($this->visitor->is_online)
            @include('livewire.registran-form.success-online')
        @endif

        @if ($this->visitor->is_offline)
            @include('livewire.registran-form.success-offline')
        @endif
    </div>
</div>
