<div>
    <div class="flex justify-center">
        @if (!$isSubmitted)
            <div class="flex w-full max-w-full flex-col space-y-4 px-4 py-4 lg:max-w-screen-md lg:px-2">
                <form wire:submit="save">
                    {{-- To attain knowledge, add things every day; To attain wisdom, subtract things every day. --}}

                    <div class="sticky top-0 rounded bg-red-500 px-4 py-2 text-center text-white transition-all ease-in-out"
                        wire:offline>
                        <p class="text-bold">
                            You are currently offline. Please check your internet connection.
                        </p>
                    </div>

                    <div>

                        <img class="mb-6 max-w-48 lg:max-w-[300px]" src="{{ asset('img/logo_bni.png') }}" alt="">

                        <div>
                            <div>
                                <p class="flex items-center space-x-1 text-2xl font-medium leading-none lg:text-[42px]">
                                    <img class="w-10 lg:w-16" src="{{ asset('img/logo_bni.svg') }}" alt="">
                                    <span>
                                        GOLF TOURNAMENT
                                    </span>
                                </p>

                                <h1 class="mb-2 text-[40px] font-bold leading-none lg:text-[78px]">REGISTRATION</h1>

                                <span class="rounded-lg bg-black p-1 text-xl font-bold uppercase text-white">
                                    {{ $this->event->start_date_full_formatted }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-y-4 lg:py-4">

                        {{-- REGISTER AS --}}
                        <div>
                            {{-- NAMA LENGKAP --}}
                            <div class="form-group">
                                <label class="form-label text-black" for="name">FULL NAME:</label>
                                <input class="w-full border border-black p-2" id="name" type="text"
                                    wire:model.blur="name" />
                                <div>
                                    @error('name')
                                        <span class="error-form-message">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- NO HANDPHONE / WHATSAPP --}}
                            <div class="form-group">
                                <label class="form-label text-black" for="phone">MOBILE PHONE / WHATSAPP:</label>
                                <input class="w-full border border-black p-2" id="phone" type="tel"
                                    wire:model='phone' />
                                <div>
                                    @error('phone')
                                        <span class="error-form-message">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- EMAIL --}}
                            <div class="form-group">
                                <label class="form-label text-black" for="email">EMAIL:</label>
                                <input class="w-full border border-black p-2" id="email" type="email"
                                    wire:model='email' />
                                <div>
                                    @error('email')
                                        <span class="error-form-message">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Handicap --}}
                            <div class="form-group">
                                <label class="form-label text-black" for="handicap">HANDICAP:</label>
                                <input class="w-full border border-black p-2" id="handicap"
                                    placeholder="Select Handicap 1-32" type="number" min="1" max="32"
                                    step="1" wire:model='handicap' />
                                <div>
                                    @error('handicap')
                                        <span class="error-form-message">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- UKURAN BAJU --}}
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

                            {{-- BNI / Non-BNI --}}
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

                            <div>
                                @if (!empty($type) && $type !== null)
                                    @if ($type !== 'bni')
                                        <div class="form-group">
                                            <label class="form-label text-black" for="visitor_type">PERSONAL /
                                                COMPANY:</label>
                                            <select class="w-full border border-black p-2" id="visitor_type"
                                                wire:model.live="visitor_type">
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

                                        @if (!empty($visitor_type) && $visitor_type !== null && $visitor_type !== 'personal')
                                            <div class="form-group">
                                                <label class="form-label text-black" for="company">COMPANY
                                                    NAME:</label>
                                                <input class="w-full border border-black p-2" id="company"
                                                    type="text" wire:model='company' />
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
                                            <input class="w-full border border-black p-2" id="chapter" type="text"
                                                wire:model='chapter' />
                                            <div>
                                                @error('chapter')
                                                    <span class="error-form-message">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    @endif

                                @endif
                            </div>

                            {{-- Perusahaan --}}
                            {{-- <div class="form-group">
                                <label class="form-label text-black" for="invited_by"> (Name +
                                    Chapter):</label>
                                <input id="invited_by" @class([
                                    'w-full border border-black p-2',
                                    'bg-gray-400/50 cursor-not-allowed' => $this->invited_by_disabled,
                                ]) @disabled($this->invited_by_disabled)
                                    type="text" wire:model='invited_by' />
                                <div>
                                    @error('invited_by')
                                        <span class="error-form-message">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div> --}}



                        </div>



                    </div>

                    {{-- KETERANGAN --}}
                    @if ($this->event->detail->show_invoice_upload)
                        <p class="font-semibold">Please transfer payment to <br>
                            <strong class="text-lg">
                                Bank Jago 101916230906 a/n Stefanny Liezal
                            </strong>
                        </p>

                        {{-- UPLOAD BUKTI PEMBAYARAN --}}
                        <div class="form-group">
                            <label class="form-label text-black" for="payment">UPLOAD PROOF OF
                                PAYMENT:</label>
                            <input class="w-full border border-black p-2" id="payment" type="file"
                                accept="image/*" wire:model.live='payment' name="payment" />
                            @if ($payment)
                                <div class="bg-gray my-3 px-2">
                                    <img class="w-full max-w-screen-lg lg:max-w-sm"
                                        src="{{ $payment->temporaryUrl() }}" alt="">
                                </div>
                            @endif
                            {{-- <x-filepond::upload wire:model="payment" required="{{ $this->isOfflineSelected }}" /> --}}

                            <div>
                                @error('payment')
                                    <span class="error-form-message">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    @endif

                    <div class="mt-4 flex justify-center">
                        <button class="btn disabled:hover: w-full bg-red-bni disabled:bg-red-bni/80"
                            wire:loading.attr="disabled" type="submit">
                            <span class="items-center justify-center" wire:loading.flex wire:target="save">
                                <svg class="-ml-1 mr-3 h-5 w-5 animate-spin text-white" data-motion-id="svg 2"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4">
                                    </circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Processing...
                            </span>

                            <span wire:loading.remove wire:target="save">
                                COMPLETE REGISTRATION</span>
                        </button>
                    </div>

                </form>
            </div>
        @else
            <div class="flex w-full max-w-full flex-col space-y-4 px-4 py-4 lg:max-w-screen-md lg:px-2">
                {{-- To attain knowledge, add things every day; To attain wisdom, subtract things every day. --}}

                <div>

                    <img class="mb-6 max-w-48 lg:max-w-[300px]" src="{{ asset('img/logo-bni.png') }}"
                        alt="">


                    <div class="mb-6">
                        <h2 class="mb-2 text-[40px] font-bold leading-none lg:text-[78px]">
                            THANK YOU
                        </h2>
                        <h2 class="text-[40px] font-medium leading-none lg:text-[42px]">
                            FOR YOUR REGISTRATION
                        </h2>
                    </div>

                    <h2 class="text-[24px] text-xl font-bold">
                        SEE YOU ON {{ $this->event->start_date_full_formatted }}!
                    </h2>
                </div>

                <div class="grid grid-cols-1">

                    <div class="space-y-4 border border-black px-4 py-4 lg:px-6">
                        <h4 class="text-xl font-bold lg:text-2xl">
                            OFFLINE MEETING {{ $this->event->detail->offline_time_no_seconds }}
                        </h4>

                        <div>
                            <h5 class="text-lg font-bold text-gray-800">LOCATION:</h5>
                            {!! $this->event->detail->offline_address !!}
                        </div>

                        <a class="btn bg-red-bni" href="{{ $this->event->detail->offline_location }}"
                            target="blank">
                            GOOGLE MAP
                        </a>

                        {{-- PACKAGE SELECTED --}}
                        <div>

                            <div>
                                <h5 class="text-lg font-bold text-gray-800">PAKET MAKANAN + MINUMAN</h5>
                                <h4 class="text-xl font-bold lg:text-2xl">
                                    {{ $this->visitor->package }}
                                </h4>
                            </div>

                            {{-- ORDER ID --}}
                            <div>
                                <h5 class="text-lg font-bold text-gray-800">ORDER ID:</h5>
                                <h4 class="text-xl font-bold lg:text-2xl">
                                    #{{ $this->visitor->order_id }}
                                </h4>
                            </div>

                            {{-- PAYMENT --}}
                            <div class="mt-6">
                                <h5 class="mb-2 text-lg font-bold">WHAT TO PREPARE</h5>
                                <ul class="list-inside list-disc">
                                    @if ($this->event->slug != 'fun-bay-networking')
                                        <li class="text-lg font-medium">Wear Business Attire</li>
                                    @endif

                                    <li class="text-lg font-medium">Bring lots of Namecards</li>
                                    <li class="text-lg font-medium">Prepare Your Business Introduction</li>
                                    <li class="text-lg font-medium">Please be on-time</li>
                                </ul>
                            </div>
                        </div>

                    </div>


                </div>
            </div>

        @endif
    </div>
</div>
