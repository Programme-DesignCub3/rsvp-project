<?php

namespace App\Services;

use App\Mail\VisitorMail;
use App\Models\Event;
use App\Models\Visitor;
use Illuminate\Support\Facades\DB;
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
        bool $shouldSendMail = true,
        bool $withPaymentCode = false,
        int $paymentBaseAmount = 0,
        ?string $paymentBerita = null
    ): Visitor {
        $visitor = DB::transaction(function () use (
            $event,
            $attributes,
            $withPaymentCode,
            $paymentBaseAmount,
            $paymentBerita
        ): Visitor {
            $attributes = $this->attributesForCreate($event, $attributes, $withPaymentCode);

            if ($withPaymentCode) {
                $attributes = $this->withPaymentCodeAttributes(
                    $event,
                    $attributes,
                    $paymentBaseAmount,
                    $paymentBerita
                );
            }

            return Visitor::create($attributes);
        });

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
    protected function attributesForCreate(Event $event, array $attributes, bool $withPaymentCode = false): array
    {
        $attributes['event_id'] = $event->id;

        if (! $withPaymentCode && ($attributes['is_offline'] ?? false) && empty($attributes['order_id'])) {
            $attributes['order_id'] = $this->nextOfflineOrderId($event);
        }

        return $attributes;
    }

    /**
     * Allocate the event-wide registration number and freeze payment values.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function withPaymentCodeAttributes(
        Event $event,
        array $attributes,
        int $paymentBaseAmount,
        ?string $paymentBerita
    ): array {
        $lockedEvent = Event::query()->lockForUpdate()->findOrFail($event->id);
        $existingVisitorCount = Visitor::query()->where('event_id', $event->id)->count();
        $lockedEvent->registration_counter = max(
            (int) $lockedEvent->registration_counter,
            $existingVisitorCount
        ) + 1;
        $lockedEvent->save();

        $sequence = $lockedEvent->registration_counter;
        $paymentBaseAmount = max(0, $paymentBaseAmount);

        $attributes['registration_sequence'] = $sequence;
        $attributes['payment_base_amount'] = $paymentBaseAmount;
        $attributes['payment_amount'] = $paymentBaseAmount > 0 ? $paymentBaseAmount + $sequence : 0;
        $attributes['payment_berita'] = $paymentBerita;

        if ($attributes['is_offline'] ?? false) {
            $attributes['order_id'] = str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
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
