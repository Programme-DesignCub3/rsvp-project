<?php

namespace App\Livewire;

use App\Enums\FoodType;
use App\Enums\VisitorStatusType;
use App\Enums\VisitorType;
use App\Models\Event;
use App\Models\Member;
use App\Models\Visitor;
use App\Services\VisitorRegistrationService;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Handles the default visitor registration flow for BNI event pages.
 *
 * The Blade view is intentionally split into partials, while this class owns
 * the Livewire state, event/session decisions, validation, persistence, media
 * attachment, and confirmation email workflow.
 */
class RegistranFormComponent extends Component
{
    use WithFileUploads;

    private const ONLINE_SESSION = 'online';

    private const OFFLINE_SESSION = 'offline';

    /**
     * @var array<int, string>
     */
    private const SELECTABLE_SESSIONS = [
        self::ONLINE_SESSION,
        self::OFFLINE_SESSION,
    ];

    public bool $isSubmitted = false;

    public string $slug = '';

    public Event $event;

    public ?Visitor $visitor = null;

    /**
     * @var array<int, string>
     */
    public array $sessions = [];

    public ?string $type = '';

    public ?string $name = '';

    public ?string $status = null;

    public ?string $business = null;

    public ?string $company = null;

    public ?string $phone = null;

    public ?string $email = null;

    public ?string $invited_by = '';

    /**
     * @var array<string, mixed>
     */
    public array $food = [];

    public mixed $payment = null;

    /**
     * @var array<int, VisitorType>
     */
    public array $visitor_type = [];

    public bool $invited_by_disabled = false;

    public ?string $substituted_by = null;

    /**
     * Initialize the form with the current event and its default sessions.
     */
    public function mount(string $slug, Event $event): void
    {
        $this->slug = $slug;
        $this->event = $event->loadMissing('detail');

        if (! $this->event->checkable_one) {
            $this->sessions = $this->event->session;
        }

        $this->updateVisitorType();
        $this->syncDefaultFoodSelection();
    }

    /**
     * Reset visitor identity fields when the selected registration type changes.
     */
    public function updatedType(): void
    {
        $this->resetVisitorIdentityFields();
        $this->syncInvitedByAvailability();
        $this->syncDefaultFoodSelection();
    }

    /**
     * Clear attendance status when sessions are updated from checkbox bindings.
     */
    public function updatingSessions(mixed $value, mixed $key): void
    {
        $this->resetAttendanceStatus();
    }

    /**
     * Clear substitute details whenever the selected status is no longer substitute.
     */
    public function updatingStatus(mixed $value): void
    {
        if ($value !== VisitorStatusType::SUBSTITUTE->value) {
            $this->reset('substituted_by');
        }
    }

    /**
     * Refresh available visitor types according to selected session and event overrides.
     */
    public function updateVisitorType(): void
    {
        $offlineVisitorTypes = $this->visitorTypesForSession(self::OFFLINE_SESSION);
        $onlineVisitorTypes = $this->visitorTypesForSession(self::ONLINE_SESSION);

        if ($this->event->checkable_one) {
            $this->visitor_type = $this->uniqueVisitorTypes([...$onlineVisitorTypes, ...$offlineVisitorTypes]);
            $this->syncDefaultFoodSelection();

            return;
        }

        $this->visitor_type = match (true) {
            $this->isOfflineSelected() && $this->isOnlineSelected() => $this->uniqueVisitorTypes([...$onlineVisitorTypes, ...$offlineVisitorTypes]),
            $this->isOnlineSelected() => $onlineVisitorTypes,
            $this->isOfflineSelected() => $offlineVisitorTypes,
            default => [],
        };

        if (! in_array($this->selectedVisitorType(), $this->visitor_type, true)) {
            $this->type = '';
        }

        $this->syncDefaultFoodSelection();
    }

    /**
     * Toggle a buffet food value in the selected food array.
     */
    public function handleFoodChange(string $food): void
    {
        if (! in_array($food, $this->food, true)) {
            $this->food = array_merge($this->food, [$food]);

            return;
        }

        $this->food = array_values(array_diff($this->food, [$food]));
    }

    /**
     * Auto-select food when a configured food type only has one valid choice.
     */
    protected function syncDefaultFoodSelection(): void
    {
        if (! $this->isOfflineSelected() || $this->offline_foods === []) {
            return;
        }

        match ($this->event->detail->food_type) {
            FoodType::BUFFET => $this->syncDefaultBuffetFoodSelection(),
            FoodType::ALA_CARTE => $this->syncDefaultAlaCarteFoodSelection(),
            FoodType::FIXED => $this->syncDefaultFixedFoodSelection(),
            default => null,
        };
    }

    /**
     * Store the only buffet item as an array selection.
     */
    protected function syncDefaultBuffetFoodSelection(): void
    {
        if (count($this->offline_foods) !== 1) {
            return;
        }

        $food = $this->offline_foods[0]['food'] ?? null;

        if (is_string($food) && filled($food)) {
            $this->food = [$food];
        }
    }

    /**
     * Store the only ala carte food and drink options in the nested food state.
     */
    protected function syncDefaultAlaCarteFoodSelection(): void
    {
        $foodConfiguration = $this->offline_foods[0] ?? null;

        if (! is_array($foodConfiguration)) {
            return;
        }

        foreach (['food', 'drink'] as $key) {
            $options = $this->alaCarteOptions($key);
            $optionNames = array_column($options, 'name');

            if (count($optionNames) === 1) {
                $this->food[$key] = reset($optionNames);

                continue;
            }

            if (isset($this->food[$key]) && ! in_array($this->food[$key], $optionNames, true)) {
                unset($this->food[$key]);
            }
        }
    }

    /**
     * Store fixed-menu values after visitor type selects the matching package.
     */
    protected function syncDefaultFixedFoodSelection(): void
    {
        $selectedFixedMenu = $this->selectedFixedMenu();

        if ($selectedFixedMenu === null) {
            return;
        }

        $this->syncFixedFoodSelection($selectedFixedMenu);
    }

    /**
     * Toggle the single allowed session for events configured as checkable-one.
     */
    public function handleSessionChange(string $session): void
    {
        if (! in_array($session, self::SELECTABLE_SESSIONS, true)) {
            return;
        }

        $this->sessions = in_array($session, $this->sessions, true) ? [] : [$session];

        $this->resetAttendanceStatus();
        $this->updateVisitorType();
    }

    /**
     * Build the base validation rules for member and non-member registration types.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required'],
            'email' => [
                Rule::unique('visitors', 'email')
                    ->where(fn (Builder $query): Builder => $query->where('event_id', $this->event->id)),
            ],
        ];

        if ($this->isVisitorTypeMagnitude()) {
            return $rules;
        }

        return [
            ...$rules,
            'business' => ['required'],
            'company' => ['required'],
            'phone' => ['required'],
            'invited_by' => ['sometimes'],
            'type' => ['required', Rule::enum(VisitorType::class)],
        ];
    }

    /**
     * Return short validation copy used by the registration form.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => '* mandatory',
            'type.enum' => '* mandatory',
            'sessions.required' => '* mandatory',
            'sessions.*' => '* mandatory',
            'name.required' => '* mandatory',
            'business.required' => '* mandatory',
            'company.required' => '* mandatory',
            'phone.required' => '* mandatory',
            'email.required' => '* mandatory',
            'invited_by.required' => '* mandatory',
            'food.*' => '* mandatory',
        ];
    }

    /**
     * Return status choices based on whether the visitor selected an online session.
     *
     * @return array<int, VisitorStatusType>
     */
    #[Computed]
    public function getStatusType(): array
    {
        if ($this->isOnlineSelected()) {
            return [
                VisitorStatusType::HADIR,
            ];
        }

        return [
            VisitorStatusType::SAKIT,
            VisitorStatusType::SUBSTITUTE,
        ];
    }

    /**
     * Determine whether the current registration type is an existing Magnitude member.
     */
    #[Computed]
    public function isVisitorTypeMagnitude(): bool
    {
        return $this->type === VisitorType::MAGNITUDE->value;
    }

    /**
     * Load visible members for the searchable Magnitude member dropdown.
     *
     * @return Collection<int, Member>|null
     */
    #[Computed]
    public function allMember(): ?Collection
    {
        if (! $this->isVisitorTypeMagnitude()) {
            return null;
        }

        return Member::query()
            ->where('hide', false)
            ->orderBy('name')
            ->get();
    }

    /**
     * Determine whether the offline session is selected.
     */
    #[Computed]
    public function isOfflineSelected(): bool
    {
        return in_array(self::OFFLINE_SESSION, $this->sessions ?? [], true);
    }

    /**
     * Determine whether the online session is selected.
     */
    #[Computed]
    public function isOnlineSelected(): bool
    {
        return in_array(self::ONLINE_SESSION, $this->sessions ?? [], true);
    }

    /**
     * Determine whether no attendance session has been selected.
     */
    #[Computed]
    public function isEmptySessions(): bool
    {
        return ! $this->isOfflineSelected() && ! $this->isOnlineSelected();
    }

    /**
     * Format the event online time without seconds for display.
     */
    #[Computed]
    public function online_hour(): string
    {
        return $this->event->detail->online_time ? $this->removeSeconds($this->event->detail->online_time) : '';
    }

    /**
     * Format the event offline time without seconds for display.
     */
    #[Computed]
    public function offline_hour(): string
    {
        return $this->event->detail->offline_time ? $this->removeSeconds($this->event->detail->offline_time) : '';
    }

    /**
     * Return configured offline food options from the event detail.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function offline_foods(): array
    {
        return $this->event->detail->offline_foods ?? [];
    }

    /**
     * Return ala carte food choices normalized from old string options or new priced options.
     *
     * @return array<int, array{name: string, price: mixed, has_item_price: bool, is_itemized: bool}>
     */
    #[Computed]
    public function alaCarteFoodOptions(): array
    {
        return $this->alaCarteOptions('food');
    }

    /**
     * Return ala carte drink choices normalized from old string options or new priced options.
     *
     * @return array<int, array{name: string, price: mixed, has_item_price: bool, is_itemized: bool}>
     */
    #[Computed]
    public function alaCarteDrinkOptions(): array
    {
        return $this->alaCarteOptions('drink');
    }

    /**
     * Return a public food option label with an optional IDR amount.
     */
    public function foodOptionLabel(?string $name, mixed $price): string
    {
        if (! is_string($name) || blank($name)) {
            return '';
        }

        $normalizedPrice = $this->normalizePaymentAmount($price);

        if ($normalizedPrice === null || $normalizedPrice === 0) {
            return $name;
        }

        return $name.' - IDR '.number_format($normalizedPrice, 0, ',', '.');
    }

    /**
     * Determine whether this visitor type is required to upload payment proof.
     */
    #[Computed]
    public function userShouldUploadInvoice(): bool
    {
        if (in_array($this->type, $this->event->detail->excluded_payment_list ?? [], true)) {
            return false;
        }

        $paymentBreakdown = $this->paymentBreakdown();

        return $paymentBreakdown === [] || $this->paymentTotalAmount() > 0;
    }

    /**
     * Return the fixed food package that matches the selected registration type.
     *
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function selectedFixedMenu(): ?array
    {
        if ($this->event->detail->food_type !== FoodType::FIXED || blank($this->type)) {
            return null;
        }

        foreach ($this->offline_foods as $item) {
            if (($item['visitor_type'] ?? null) === $this->type) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Return the payment price configured for the selected registration type.
     *
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function selectedRegistrationPaymentPrice(): ?array
    {
        if (blank($this->type)) {
            return null;
        }

        if (in_array($this->type, $this->event->detail->excluded_payment_list ?? [], true)) {
            return null;
        }

        foreach ($this->event->detail->registration_payment_prices ?? [] as $paymentPrice) {
            if (($paymentPrice['visitor_type'] ?? null) === $this->type) {
                return $paymentPrice;
            }
        }

        if ($this->event->detail->default_registration_fee !== null) {
            return [
                'price' => $this->event->detail->default_registration_fee,
                'label' => null,
            ];
        }

        return [
            'price' => 0,
            'label' => null,
        ];
    }

    /**
     * Return the package price shown near payment proof upload.
     */
    #[Computed]
    public function paymentAmountLabel(): ?string
    {
        return $this->formatPaymentAmount($this->paymentTotalAmount());
    }

    /**
     * Return detailed payment rows used by the proof upload summary.
     *
     * @return array<int, array{label: string, description: string|null, amount: int, amount_label: string|null}>
     */
    #[Computed]
    public function paymentBreakdown(): array
    {
        $selectedPaymentPrice = $this->selectedRegistrationPaymentPrice();
        $paymentBreakdown = [];

        if ($selectedPaymentPrice !== null) {
            $paymentBreakdown = [
                ...$paymentBreakdown,
                ...$this->paymentBreakdownLine(
                    'Registration fee',
                    $this->paymentPackageLabel(),
                    $selectedPaymentPrice['price'] ?? null
                ),
            ];
        }

        return [
            ...$paymentBreakdown,
            ...$this->foodPaymentBreakdown(),
        ];
    }

    /**
     * Group payment rows by category so repeated labels only appear once.
     *
     * @return array<int, array{label: string, items: array<int, array{description: string|null, amount: int, amount_label: string|null}>}>
     */
    #[Computed]
    public function paymentBreakdownGroups(): array
    {
        $groups = [];

        foreach ($this->paymentBreakdown() as $paymentItem) {
            $label = $paymentItem['label'];

            $groups[$label] ??= [
                'label' => $label,
                'items' => [],
            ];

            $groups[$label]['items'][] = [
                'description' => $paymentItem['description'],
                'amount' => $paymentItem['amount'],
                'amount_label' => $paymentItem['amount_label'],
            ];
        }

        return array_values($groups);
    }

    /**
     * Return the selected fixed food package label for payment detail.
     */
    #[Computed]
    public function fixedFoodPackageLabel(): ?string
    {
        $selectedFixedMenu = $this->selectedFixedMenu();

        if ($selectedFixedMenu === null) {
            return null;
        }

        foreach (['food', 'drink', 'custom'] as $key) {
            if (filled($selectedFixedMenu[$key] ?? null)) {
                return (string) $selectedFixedMenu[$key];
            }
        }

        return $this->paymentPackageLabel();
    }

    /**
     * Return the label shown near the payment summary header.
     */
    #[Computed]
    public function paymentSummaryLabel(): ?string
    {
        if ($this->selectedRegistrationPaymentPrice() !== null) {
            return $this->paymentPackageLabel();
        }

        return $this->foodPaymentSummaryLabel();
    }

    /**
     * Return the calculated total amount from all payment breakdown rows.
     */
    #[Computed]
    public function paymentTotalAmount(): ?int
    {
        $paymentBreakdown = $this->paymentBreakdown();

        return $paymentBreakdown === []
            ? null
            : array_sum(array_column($paymentBreakdown, 'amount'));
    }

    /**
     * Return custom copy for the selected payment price, when configured.
     */
    #[Computed]
    public function paymentPackageLabel(): ?string
    {
        $selectedPaymentPrice = $this->selectedRegistrationPaymentPrice();

        if (blank($selectedPaymentPrice['label'] ?? null)) {
            return $this->selectedVisitorTypeLabel();
        }

        return (string) $selectedPaymentPrice['label'];
    }

    /**
     * Return the selected registration type label for payment copy.
     */
    #[Computed]
    public function selectedVisitorTypeLabel(): ?string
    {
        return $this->selectedVisitorType()?->getLabel();
    }

    /**
     * Render the registration form view.
     */
    public function render()
    {
        return view('livewire.registran-form-component');
    }

    /**
     * Validate and persist the registration, then attach payment proof and email the visitor.
     */
    public function save(): void
    {
        $this->validate();
        $this->validateOfflineRequirements();

        $this->visitor = app(VisitorRegistrationService::class)->createForEvent(
            $this->event,
            $this->visitorData(),
            $this->payment,
            $this->shouldStorePaymentProof()
        );
        $this->isSubmitted = true;
    }

    /**
     * Clear personal fields that depend on the selected registration type.
     */
    protected function resetVisitorIdentityFields(): void
    {
        $this->reset([
            'name',
            'business',
            'company',
            'phone',
            'email',
            'invited_by',
            'status',
        ]);
    }

    /**
     * Disable invited-by input for visitor types that should not provide host details.
     */
    protected function syncInvitedByAvailability(): void
    {
        $this->invited_by_disabled = ! in_array(
            $this->type,
            [VisitorType::VISITOR->value, VisitorType::GUEST->value],
            true
        );

        if ($this->invited_by_disabled) {
            $this->reset('invited_by');
        }
    }

    /**
     * Clear status fields that become stale when session choice changes.
     */
    protected function resetAttendanceStatus(): void
    {
        $this->reset(['status', 'substituted_by']);
    }

    /**
     * Resolve the visitor types available for a session, honoring event overrides.
     *
     * @return array<int, VisitorType>
     */
    protected function visitorTypesForSession(string $session): array
    {
        if ($session === self::OFFLINE_SESSION) {
            return $this->event->detail->override_offline_visitor_type
                ? $this->visitorTypesFromValues($this->event->detail->offline_visitor_type_list ?? [])
                : $this->defaultOfflineVisitorTypes();
        }

        if ($session === self::ONLINE_SESSION) {
            return $this->event->detail->override_online_visitor_type
                ? $this->visitorTypesFromValues($this->event->detail->online_visitor_type_list ?? [])
                : VisitorType::cases();
        }

        return [];
    }

    /**
     * Convert stored visitor type string values into enum cases.
     *
     * @param  array<int, string>  $values
     * @return array<int, VisitorType>
     */
    protected function visitorTypesFromValues(array $values): array
    {
        $visitorTypes = [];

        foreach ($values as $value) {
            $visitorType = VisitorType::tryFrom($value);

            if ($visitorType !== null) {
                $visitorTypes[] = $visitorType;
            }
        }

        return $visitorTypes;
    }

    /**
     * Return the default offline visitor types when no event override is configured.
     *
     * @return array<int, VisitorType>
     */
    protected function defaultOfflineVisitorTypes(): array
    {
        return [
            VisitorType::VISITOR,
            VisitorType::MAGNITUDE,
            VisitorType::ALTITUDE,
        ];
    }

    /**
     * Remove duplicate visitor type enum cases while preserving insertion order.
     *
     * @param  array<int, VisitorType>  $visitorTypes
     * @return array<int, VisitorType>
     */
    protected function uniqueVisitorTypes(array $visitorTypes): array
    {
        $uniqueVisitorTypes = [];

        foreach ($visitorTypes as $visitorType) {
            $uniqueVisitorTypes[$visitorType->value] = $visitorType;
        }

        return array_values($uniqueVisitorTypes);
    }

    /**
     * Convert the currently selected registration type value into a VisitorType enum.
     */
    protected function selectedVisitorType(): ?VisitorType
    {
        return is_string($this->type) ? VisitorType::tryFrom($this->type) : null;
    }

    /**
     * Run validations that only apply to offline registrations.
     */
    protected function validateOfflineRequirements(): void
    {
        if (! $this->isOfflineSelected()) {
            return;
        }

        if ($this->event->detail->show_invoice_upload && $this->userShouldUploadInvoice()) {
            $this->validatePaymentProof();
        }

        if ($this->event->detail->food_required) {
            $this->validateFoodSelection();
        }
    }

    /**
     * Validate uploaded payment proof before passing it to the media library.
     */
    protected function validatePaymentProof(): void
    {
        $this->validate(
            [
                'payment' => ['required', 'image', 'max:4096'],
            ],
            [
                'payment.required' => '* mandatory',
                'payment.image' => 'File must be an image',
                'payment.max' => 'File size must be less than 4MB',
            ],
            ['payment' => 'PROOF OF PAYMENT']
        );
    }

    /**
     * Validate food selection when the event requires an offline meal choice.
     */
    protected function validateFoodSelection(): void
    {
        match ($this->event->detail->food_type) {
            FoodType::BUFFET => $this->validateBuffetFoodSelection(),
            FoodType::ALA_CARTE => $this->validateAlaCarteFoodSelection(),
            FoodType::FIXED => $this->validateFixedFoodSelection(),
            default => $this->validateRequiredFoodSelection(),
        };
    }

    /**
     * Validate that at least one buffet option is selected.
     */
    protected function validateBuffetFoodSelection(): void
    {
        $this->validate(
            [
                'food' => ['required', 'array', 'min:1'],
                'food.*' => ['required', 'string', Rule::in($this->buffetFoodOptionNames())],
            ],
            $this->foodValidationMessages(),
            ['food' => 'FOOD']
        );
    }

    /**
     * Validate each configured ala carte group instead of only the parent array.
     */
    protected function validateAlaCarteFoodSelection(): void
    {
        $rules = [
            'food' => ['required', 'array'],
        ];

        foreach (['food', 'drink'] as $key) {
            $optionNames = array_column($this->alaCarteOptions($key), 'name');

            if ($optionNames !== []) {
                $rules["food.{$key}"] = ['required', 'string', Rule::in($optionNames)];
            }
        }

        $this->validate(
            $rules,
            $this->foodValidationMessages(),
            ['food' => 'FOOD']
        );
    }

    /**
     * Validate that the selected visitor type has a configured fixed menu.
     */
    protected function validateFixedFoodSelection(): void
    {
        $this->validate(
            [
                'food' => [
                    function (string $attribute, mixed $value, Closure $fail): void {
                        if ($this->selectedFixedMenu() === null) {
                            $fail('* mandatory');
                        }
                    },
                ],
            ],
            $this->foodValidationMessages(),
            ['food' => 'FOOD']
        );
    }

    /**
     * Validate a generic required food selection.
     */
    protected function validateRequiredFoodSelection(): void
    {
        $this->validate(
            ['food' => ['required']],
            $this->foodValidationMessages(),
            ['food' => 'FOOD']
        );
    }

    /**
     * @return array<string, string>
     */
    protected function foodValidationMessages(): array
    {
        return [
            'food.required' => '* mandatory',
            'food.array' => '* mandatory',
            'food.min' => '* mandatory',
            'food.*.required' => '* mandatory',
            'food.*.string' => '* mandatory',
            'food.*.in' => '* mandatory',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function buffetFoodOptionNames(): array
    {
        return array_values(array_filter(array_map(
            fn (mixed $foodItem): ?string => is_array($foodItem) && isset($foodItem['food']) && is_string($foodItem['food'])
                ? $foodItem['food']
                : null,
            $this->offline_foods
        )));
    }

    /**
     * Build the payload used to create the Visitor record.
     *
     * @return array{
     *     sessions: array<int, string>,
     *     type: string|null,
     *     name: string|null,
     *     status: string|null,
     *     business: string|null,
     *     company: string|null,
     *     phone: string|null,
     *     email: string|null,
     *     invited_by: string|null,
     *     food: string|null,
     *     event_id: int,
     *     is_offline?: bool,
     *     is_online?: bool,
     *     meta?: array<string, mixed>
     * }
     */
    protected function visitorData(): array
    {
        $data = [
            'sessions' => $this->sessions,
            'type' => $this->type,
            'name' => $this->name,
            'status' => $this->isVisitorTypeMagnitude() ? $this->status : null,
            'business' => $this->business,
            'company' => $this->company,
            'phone' => $this->phone,
            'email' => $this->email,
            'invited_by' => $this->invited_by ?: null,
            'food' => $this->serializedFood(),
            'event_id' => $this->event->id,
        ];

        if ($this->isOfflineSelected()) {
            $data['is_offline'] = true;
        }

        if ($this->isOnlineSelected()) {
            $data['is_online'] = true;
        }

        if ($this->isSubstituteResponse()) {
            $data['meta'] = ['substituted_by' => $this->substituted_by];
        }

        return $data;
    }

    /**
     * Serialize food selection for storage only when an offline food menu exists.
     */
    protected function serializedFood(): ?string
    {
        if (! $this->isOfflineSelected() || count($this->offline_foods) === 0) {
            return null;
        }

        if ($this->event->detail->food_type === FoodType::FIXED) {
            $selectedFixedMenu = $this->selectedFixedMenu();

            return $selectedFixedMenu ? json_encode($selectedFixedMenu) : null;
        }

        return json_encode($this->food);
    }

    /**
     * Determine whether a Magnitude member submitted a substitute response.
     */
    protected function isSubstituteResponse(): bool
    {
        return $this->isVisitorTypeMagnitude()
            && $this->status === VisitorStatusType::SUBSTITUTE->value;
    }

    /**
     * Determine whether the current registration should persist a payment proof file.
     */
    protected function shouldStorePaymentProof(): bool
    {
        return $this->payment !== null
            && $this->isOfflineSelected()
            && $this->event->detail->show_invoice_upload
            && $this->userShouldUploadInvoice();
    }

    /**
     * Find the fixed menu for the selected visitor type and sync it into form state.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>|null
     */
    public function searchFixedMenu(?string $target, array $items, string $prop_key): ?array
    {
        foreach ($items as $item) {
            if (($item[$prop_key] ?? null) === $target) {
                $this->syncFixedFoodSelection($item);

                return $item;
            }
        }

        $this->reset('food');

        return null;
    }

    /**
     * Copy supported fixed-menu values into the food form state.
     *
     * @param  array<string, mixed>  $item
     */
    protected function syncFixedFoodSelection(array $item): void
    {
        foreach (['food', 'drink', 'price', 'custom'] as $key) {
            if (! empty($item[$key])) {
                $this->food[$key] = $item[$key];
            }
        }
    }

    /**
     * Format a configured payment amount for display, preserving non-numeric labels.
     */
    protected function formatPaymentAmount(mixed $amount): ?string
    {
        $normalizedAmount = $this->normalizePaymentAmount($amount);

        if ($normalizedAmount === null) {
            return null;
        }

        if ($normalizedAmount === 0) {
            return 'FREE';
        }

        return 'IDR '.number_format($normalizedAmount, 0, ',', '.');
    }

    /**
     * Build a single payment row when a usable amount exists.
     * Food rows can opt into zero-value rows without displaying a free amount label.
     *
     * @return array<int, array{label: string, description: string|null, amount: int, amount_label: string|null}>
     */
    protected function paymentBreakdownLine(string $label, ?string $description, mixed $amount, bool $showBlankAmountAsFree = false, bool $hideFreeAmountLabel = false): array
    {
        $normalizedAmount = $this->normalizePaymentAmount($amount);

        if ($normalizedAmount === null) {
            if (! $showBlankAmountAsFree) {
                return [];
            }

            $normalizedAmount = 0;
        }

        return [[
            'label' => $label,
            'description' => $description,
            'amount' => $normalizedAmount,
            'amount_label' => $hideFreeAmountLabel && $normalizedAmount === 0
                ? null
                : $this->formatPaymentAmount($normalizedAmount),
        ]];
    }

    /**
     * Build optional food payment rows for the configured food type.
     *
     * @return array<int, array{label: string, description: string|null, amount: int, amount_label: string|null}>
     */
    protected function foodPaymentBreakdown(): array
    {
        return match ($this->event->detail->food_type) {
            FoodType::BUFFET => $this->buffetPaymentBreakdown(),
            FoodType::ALA_CARTE => $this->alaCartePaymentBreakdown(),
            FoodType::FIXED => $this->fixedFoodPaymentBreakdown(),
            default => [],
        };
    }

    /**
     * Return one payment row for each selected buffet item, including free items.
     *
     * @return array<int, array{label: string, description: string|null, amount: int, amount_label: string|null}>
     */
    protected function buffetPaymentBreakdown(): array
    {
        $selectedFoods = is_array($this->food) ? $this->food : [$this->food];
        $paymentBreakdown = [];

        foreach ($this->offline_foods as $foodItem) {
            $foodName = $foodItem['food'] ?? null;

            if (! is_string($foodName) || ! in_array($foodName, $selectedFoods, true)) {
                continue;
            }

            $paymentBreakdown = [
                ...$paymentBreakdown,
                ...$this->paymentBreakdownLine('Food item', $foodName, $foodItem['price'] ?? null, true, true),
            ];
        }

        return $paymentBreakdown;
    }

    /**
     * Return ala carte payment rows using item prices when present, otherwise package fallback.
     *
     * @return array<int, array{label: string, description: string|null, amount: int, amount_label: string|null}>
     */
    protected function alaCartePaymentBreakdown(): array
    {
        $foodConfiguration = $this->offline_foods[0] ?? null;
        $selectionLabel = $this->alaCarteSelectionLabel();

        if (! is_array($foodConfiguration) || $selectionLabel === null) {
            return [];
        }

        if (! $this->shouldUseItemizedAlaCartePrices($foodConfiguration)) {
            return $this->paymentBreakdownLine(
                'Food package',
                $selectionLabel,
                $foodConfiguration['price'] ?? null,
                true,
                true
            );
        }

        $paymentBreakdown = [];

        foreach ([
            'food' => 'Food item',
            'drink' => 'Drink item',
        ] as $key => $label) {
            $selectedOption = $this->selectedAlaCarteOption($key);

            if ($selectedOption === null) {
                continue;
            }

            $paymentBreakdown = [
                ...$paymentBreakdown,
                ...$this->paymentBreakdownLine(
                    $label,
                    $selectedOption['name'],
                    $selectedOption['price'],
                    true,
                    true
                ),
            ];
        }

        return $paymentBreakdown;
    }

    /**
     * Determine whether selected ala carte items should be priced separately.
     *
     * @param  array<string, mixed>  $foodConfiguration
     */
    protected function shouldUseItemizedAlaCartePrices(array $foodConfiguration): bool
    {
        $hasItemizedOptions = false;

        foreach (['food', 'drink'] as $key) {
            foreach ($this->alaCarteOptions($key) as $option) {
                $hasItemizedOptions = $hasItemizedOptions || $option['is_itemized'];

                if ($option['has_item_price']) {
                    return true;
                }
            }
        }

        return $hasItemizedOptions && $this->normalizePaymentAmount($foodConfiguration['price'] ?? null) === null;
    }

    /**
     * Return the selected food or drink option with its optional price.
     *
     * @return array{name: string, price: mixed, has_item_price: bool, is_itemized: bool}|null
     */
    protected function selectedAlaCarteOption(string $key): ?array
    {
        $selectedName = $this->food[$key] ?? null;

        if (! is_string($selectedName) || blank($selectedName)) {
            return null;
        }

        foreach ($this->alaCarteOptions($key) as $option) {
            if ($option['name'] === $selectedName) {
                return $option;
            }
        }

        return null;
    }

    /**
     * Return normalized ala carte choices for the requested key.
     *
     * @return array<int, array{name: string, price: mixed, has_item_price: bool, is_itemized: bool}>
     */
    protected function alaCarteOptions(string $key): array
    {
        $foodConfiguration = $this->offline_foods[0] ?? null;

        if (! is_array($foodConfiguration)) {
            return [];
        }

        $options = $foodConfiguration[$key] ?? [];

        if (! is_array($options)) {
            return [];
        }

        return $this->uniqueAlaCarteOptions(array_values(array_filter(array_map(
            fn (mixed $option): ?array => $this->normalizeAlaCarteOption($option),
            $options
        ))));
    }

    /**
     * Normalize old string options and new priced options to one frontend shape.
     *
     * @return array{name: string, price: mixed, has_item_price: bool, is_itemized: bool}|null
     */
    protected function normalizeAlaCarteOption(mixed $option): ?array
    {
        if (is_string($option)) {
            return filled($option) ? [
                'name' => $option,
                'price' => null,
                'has_item_price' => false,
                'is_itemized' => false,
            ] : null;
        }

        if (! is_array($option)) {
            return null;
        }

        $name = $option['name'] ?? $option['food'] ?? $option['drink'] ?? null;

        if (! is_string($name) || blank($name)) {
            return null;
        }

        $price = $option['price'] ?? null;

        return [
            'name' => $name,
            'price' => $price,
            'has_item_price' => $this->normalizePaymentAmount($price) !== null,
            'is_itemized' => true,
        ];
    }

    /**
     * Deduplicate select options by name because the public dropdown value is the item name.
     *
     * @param  array<int, array{name: string, price: mixed, has_item_price: bool, is_itemized: bool}>  $options
     * @return array<int, array{name: string, price: mixed, has_item_price: bool, is_itemized: bool}>
     */
    protected function uniqueAlaCarteOptions(array $options): array
    {
        $uniqueOptions = [];

        foreach ($options as $option) {
            $uniqueOptions[mb_strtolower(trim($option['name']))] = $option;
        }

        return array_values($uniqueOptions);
    }

    /**
     * Return the optional fixed package food price.
     *
     * @return array<int, array{label: string, description: string|null, amount: int, amount_label: string|null}>
     */
    protected function fixedFoodPaymentBreakdown(): array
    {
        $selectedFixedMenu = $this->selectedFixedMenu();

        if ($selectedFixedMenu === null) {
            return [];
        }

        return $this->paymentBreakdownLine(
            'Food package',
            $this->fixedFoodPackageLabel(),
            $selectedFixedMenu['price'] ?? null,
            true,
            true
        );
    }

    /**
     * Return a concise food label for the payment summary header.
     */
    protected function foodPaymentSummaryLabel(): ?string
    {
        $foodBreakdown = $this->foodPaymentBreakdown();

        if (count($foodBreakdown) !== 1) {
            return null;
        }

        return $foodBreakdown[0]['description'];
    }

    /**
     * Return the selected ala carte food and drink as a package label.
     */
    protected function alaCarteSelectionLabel(): ?string
    {
        $selectedItems = array_filter([
            $this->food['food'] ?? null,
            $this->food['drink'] ?? null,
        ], fn (mixed $item): bool => is_string($item) && filled($item));

        return $selectedItems === [] ? null : implode(' + ', $selectedItems);
    }

    /**
     * Convert plain or masked IDR values into an integer amount.
     */
    protected function normalizePaymentAmount(mixed $amount): ?int
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', (string) $amount);

        return $digits === '' ? null : (int) $digits;
    }

    /**
     * Format a time string as hour and minute.
     */
    protected function removeSeconds(string $time): string
    {
        return date('h:i', strtotime($time));
    }
}
