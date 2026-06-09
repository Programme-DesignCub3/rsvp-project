<?php

namespace App\Services;

use App\Mail\VisitorMail;
use App\Models\Event;
use App\Models\Visitor;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class VisitorRegistrationService
{
    /**
     * Create a visitor for an event and run the common post-registration steps.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createForEvent(
        Event $event,
        array $attributes,
        ?TemporaryUploadedFile $paymentProof = null,
        bool $shouldStorePaymentProof = false,
        bool $shouldSendMail = true
    ): Visitor {
        $visitor = Visitor::create($this->attributesForCreate($event, $attributes));

        if ($shouldStorePaymentProof && $paymentProof !== null) {
            $this->storePaymentProof($visitor, $paymentProof);
        }

        if ($shouldSendMail) {
            $this->sendConfirmationEmail($visitor);
        }

        return $visitor;
    }

    /**
     * Generate the next five-digit offline order id for an event.
     */
    public function nextOfflineOrderId(Event $event): string
    {
        $lastOrderId = Visitor::where('event_id', $event->id)
            ->where('is_offline', true)
            ->orderByDesc('id')
            ->value('order_id') ?? '00000';

        return str_pad((string) (((int) $lastOrderId) + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function attributesForCreate(Event $event, array $attributes): array
    {
        $attributes['event_id'] = $event->id;

        if (($attributes['is_offline'] ?? false) && empty($attributes['order_id'])) {
            $attributes['order_id'] = $this->nextOfflineOrderId($event);
        }

        return $attributes;
    }

    /**
     * Attach the uploaded payment proof to the visitor media collection.
     */
    protected function storePaymentProof(Visitor $visitor, TemporaryUploadedFile $paymentProof): void
    {
        $visitor->addMedia($paymentProof->getRealPath())
            ->preservingOriginal()
            ->toMediaCollection('payment_proof');
    }

    /**
     * Send registration confirmation and report failures without blocking the saved visitor.
     */
    protected function sendConfirmationEmail(Visitor $visitor): void
    {
        try {
            Mail::to($visitor->email)->send(new VisitorMail($visitor));
        } catch (Throwable $throwable) {
            report($throwable);
        }
    }
}
