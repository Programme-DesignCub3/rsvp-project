<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\Visitor;
use App\Services\VisitorRegistrationService;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Custom registration form for the 12 Feb 2026 BNI Golf event.
 *
 * Future custom event forms can follow this shape: keep event-specific fields
 * and validation in the Livewire component, then pass the final Visitor payload
 * to VisitorRegistrationService for the common create/order/media/email flow.
 */
class BniGolf12Feb2026 extends Component
{
    use WithFileUploads;

    private const OFFLINE_SESSION = 'offline';

    public bool $isSubmitted = false;

    public string $slug = '';

    public Event $event;

    public ?Visitor $visitor = null;

    public bool $requiresPaymentProof = false;

    /**
     * @var array<int, string>
     */
    public array $sessions = [self::OFFLINE_SESSION];

    public ?string $name = '';

    public ?string $phone = null;

    public ?string $email = null;

    public int|string|null $handicap = null;

    public ?string $shirt_size = '';

    public ?string $type = '';

    public ?string $chapter = '';

    public ?string $visitor_type = '';

    public ?string $company = '';

    public mixed $payment = null;

    /**
     * Initialize the custom form with its event and default offline session.
     */
    public function mount(string $slug, Event $event): void
    {
        $this->slug = $slug;
        $this->event = $event->loadMissing('detail');
        $this->requiresPaymentProof = (bool) ($this->event->detail->show_invoice_upload ?? false);

        if (! $this->event->checkable_one) {
            $this->sessions = $this->event->session;
        }
    }

    /**
     * Clear conditional fields when the participant switches between BNI and Non-BNI.
     */
    public function updatedType(): void
    {
        $this->reset([
            'chapter',
            'company',
            'visitor_type',
        ]);
    }

    /**
     * Clear stale Non-BNI detail fields when personal/company choice changes.
     */
    public function updatedVisitorType(): void
    {
        $this->reset([
            'chapter',
            'company',
        ]);
    }

    /**
     * Base validation rules for the golf form.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'phone' => ['required'],
            'handicap' => ['required', 'numeric', 'min:1', 'max:32'],
            'email' => [
                'required',
                Rule::unique('visitors', 'email')
                    ->where(fn (Builder $query): Builder => $query->where('event_id', $this->event->id)),
            ],
            'type' => ['required'],
            'shirt_size' => ['required'],
        ];
    }

    /**
     * Validation copy shown by the custom golf form.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => '* mandatory',
            'name.required' => '* mandatory',
            'company.required' => '* mandatory',
            'phone.required' => '* mandatory',
            'email.required' => '* mandatory',
            'payment.required' => '* mandatory',
        ];
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
     * Render the custom event registration form.
     */
    public function render()
    {
        return view('livewire.bni-golf12-feb2026');
    }

    /**
     * Validate event-specific fields and create the visitor through the shared service.
     */
    public function save(): void
    {
        $this->validate();
        $this->validateRegistrationType();
        $this->validatePaymentProof();

        $this->visitor = app(VisitorRegistrationService::class)->createForEvent(
            $this->event,
            $this->visitorData(),
            $this->payment,
            $this->requiresPaymentProof
        );

        $this->isSubmitted = true;
    }

    /**
     * Validate BNI-only or Non-BNI-only fields.
     */
    protected function validateRegistrationType(): void
    {
        if ($this->type === 'bni') {
            $this->validate(['chapter' => ['required']]);

            return;
        }

        $this->validate(['visitor_type' => ['required']]);

        if ($this->visitor_type === 'company') {
            $this->validate(['company' => ['required']]);
        }
    }

    /**
     * Require payment proof only when this event shows invoice upload.
     */
    protected function validatePaymentProof(): void
    {
        if (! $this->requiresPaymentProof) {
            return;
        }

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
     * Build the Visitor payload for this custom event.
     *
     * @return array{
     *     sessions: array<int, string>,
     *     name: string|null,
     *     phone: string|null,
     *     email: string|null,
     *     type: string|null,
     *     invited_by: string,
     *     company?: string|null,
     *     is_offline: bool,
     *     meta?: array<string, mixed>
     * }
     */
    protected function visitorData(): array
    {
        $data = [
            'sessions' => $this->sessions,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'type' => $this->type,
            'invited_by' => '',
            'is_offline' => true,
        ];

        if ($this->visitor_type === 'company') {
            $data['company'] = $this->company;
        }

        $meta = $this->metaData();

        if ($meta !== []) {
            $data['meta'] = $meta;
        }

        return $data;
    }

    /**
     * Collect event-specific custom fields into visitor meta.
     *
     * @return array<string, mixed>
     */
    protected function metaData(): array
    {
        return array_filter([
            'visitor_type' => $this->visitor_type,
            'chapter' => $this->chapter,
            'handicap' => $this->handicap,
            'shirt_size' => $this->shirt_size,
        ], fn (mixed $value): bool => filled($value));
    }

    /**
     * Format a time string as hour and minute.
     */
    protected function removeSeconds(string $time): string
    {
        return date('h:i', strtotime($time));
    }
}
