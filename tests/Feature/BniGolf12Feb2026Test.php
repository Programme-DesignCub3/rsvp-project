<?php

namespace Tests\Feature;

use App\Livewire\BniGolf12Feb2026;
use App\Mail\VisitorMail;
use App\Models\Event;
use App\Models\EventDetail;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class BniGolf12Feb2026Test extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_the_custom_golf_registration_form(): void
    {
        $event = $this->createEvent();

        Livewire::test(BniGolf12Feb2026::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])
            ->assertSee('GOLF TOURNAMENT')
            ->assertSet('sessions', ['offline']);
    }

    public function test_it_requires_payment_proof_when_invoice_upload_is_enabled(): void
    {
        $event = $this->createEvent([], [
            'show_invoice_upload' => true,
        ]);

        Livewire::test(BniGolf12Feb2026::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])
            ->set('requiresPaymentProof', true)
            ->set('type', 'bni')
            ->set('name', 'Golf Member')
            ->set('phone', '08123456789')
            ->set('email', 'member@example.com')
            ->set('handicap', 12)
            ->set('shirt_size', 'L')
            ->set('chapter', 'Magnitude')
            ->call('save')
            ->assertHasErrors(['payment' => 'required']);
    }

    public function test_it_saves_a_custom_golf_registration(): void
    {
        Mail::fake();

        $event = $this->createEvent([], [
            'show_invoice_upload' => false,
        ]);

        Visitor::create([
            'name' => 'Existing Golfer',
            'phone' => '0800000000',
            'email' => 'existing-golfer@example.com',
            'invited_by' => '',
            'event_id' => $event->id,
            'is_offline' => true,
            'order_id' => '00009',
            'type' => 'bni',
        ]);

        Livewire::test(BniGolf12Feb2026::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])
            ->set('type', 'bni')
            ->set('name', 'New Golfer')
            ->set('phone', '08123456789')
            ->set('email', 'new-golfer@example.com')
            ->set('handicap', 18)
            ->set('shirt_size', 'XL')
            ->set('chapter', 'Altitude')
            ->call('save')
            ->assertSet('isSubmitted', true);

        $this->assertDatabaseHas('visitors', [
            'event_id' => $event->id,
            'name' => 'New Golfer',
            'email' => 'new-golfer@example.com',
            'is_offline' => true,
            'order_id' => '00010',
            'type' => 'bni',
        ]);

        Mail::assertSent(VisitorMail::class);
    }

    /**
     * @param  array<string, mixed>  $eventAttributes
     * @param  array<string, mixed>  $detailAttributes
     */
    private function createEvent(array $eventAttributes = [], array $detailAttributes = []): Event
    {
        $event = Event::create([
            'slug' => $eventAttributes['slug'] ?? 'bni-golf-12-feb-2026',
            'name' => $eventAttributes['name'] ?? 'BNI Golf 12 Feb 2026',
            'start_date' => $eventAttributes['start_date'] ?? now()->addWeek()->toDateString(),
            'registration_date' => $eventAttributes['registration_date'] ?? now()->subDay()->toDateString(),
            'session' => $eventAttributes['session'] ?? ['offline'],
            'checkable' => $eventAttributes['checkable'] ?? true,
            'checkable_one' => $eventAttributes['checkable_one'] ?? false,
        ]);

        $eventDetail = EventDetail::create([
            'event_id' => $event->id,
            'offline_time' => $detailAttributes['offline_time'] ?? '09:00:00',
            'offline_address' => $detailAttributes['offline_address'] ?? 'Golf Club',
            'offline_location' => $detailAttributes['offline_location'] ?? 'https://maps.example.test',
            'show_invoice_upload' => false,
        ]);

        $eventDetail->update([
            'show_invoice_upload' => $detailAttributes['show_invoice_upload'] ?? false,
        ]);

        return $event->refresh()->load('detail');
    }
}
