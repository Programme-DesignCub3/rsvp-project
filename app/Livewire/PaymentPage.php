<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\Visitor;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Shows the signed payment instructions and accepts the payment proof upload.
 */
class PaymentPage extends Component
{
    use WithFileUploads;

    #[Locked]
    public Event $event;

    #[Locked]
    public Visitor $visitor;

    public mixed $payment = null;

    public function mount(Event $event, Visitor $visitor): void
    {
        abort_unless((int) $visitor->event_id === (int) $event->id, 404);

        $this->event = $event->loadMissing('detail');
        $this->visitor = $visitor;
    }

    public function submitPaymentProof(): void
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

        $this->visitor->addMedia($this->payment->getRealPath())
            ->preservingOriginal()
            ->toMediaCollection('payment_proof');

        $this->visitor->refresh();
        $this->reset('payment');
        session()->flash('payment-proof-uploaded', 'Payment proof uploaded. Please wait for verification.');
    }

    #[Computed]
    public function requiresPaymentProof(): bool
    {
        return $this->event->detail->show_invoice_upload
            && $this->visitor->payment_amount > 0
            && ! $this->visitor->payment_verified;
    }

    #[Computed]
    public function hasPaymentProof(): bool
    {
        return $this->visitor->hasMedia('payment_proof');
    }

    #[Computed]
    public function uniqueCodeAmount(): int
    {
        return max(0, (int) $this->visitor->payment_amount - (int) $this->visitor->payment_base_amount);
    }

    public function render()
    {
        return view('livewire.payment-page');
    }
}
