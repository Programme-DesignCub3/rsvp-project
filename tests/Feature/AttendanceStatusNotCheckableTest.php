<?php

namespace Tests\Feature;

use App\Enums\FoodType;
use App\Enums\VisitorStatusType;
use App\Enums\VisitorType;
use App\Livewire\Bni11Aug;
use App\Livewire\RegistranFormComponent;
use App\Models\Event;
use App\Models\EventDetail;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceStatusNotCheckableTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_attendance_status_when_event_is_not_checkable(): void
    {
        $event = $this->createEvent([
            'checkable' => false,
            'session' => ['offline'],
        ]);

        Livewire::test(RegistranFormComponent::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])
            ->set('type', VisitorType::MAGNITUDE->value)
            ->assertSee('STATUS KEHADIRAN')
            ->assertSeeHtml('value="'.VisitorStatusType::HADIR->value.'"')
            ->assertSeeHtml('value="'.VisitorStatusType::SUBSTITUTE->value.'"');
    }

    public function test_it_saves_hadir_status_when_event_is_not_checkable(): void
    {
        Mail::fake();

        $member = $this->makeMember();

        $event = $this->createEvent([
            'checkable' => false,
            'session' => ['offline'],
        ]);

        Livewire::test(RegistranFormComponent::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])
            ->set('type', VisitorType::MAGNITUDE->value)
            ->set('name', $member->name)
            ->set('email', $member->email)
            ->set('phone', $member->phone)
            ->set('invited_by', 'A Member')
            ->set('status', VisitorStatusType::HADIR->value)
            ->call('save')
            ->assertSet('isSubmitted', true);

        $this->assertDatabaseHas('visitors', [
            'event_id' => $event->id,
            'email' => $member->email,
            'status' => VisitorStatusType::HADIR->value,
        ]);
    }

    public function test_it_requires_status_when_event_is_not_checkable(): void
    {
        Mail::fake();

        $member = $this->makeMember();

        $event = $this->createEvent([
            'checkable' => false,
            'session' => ['offline'],
        ]);

        Livewire::test(RegistranFormComponent::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])
            ->set('type', VisitorType::MAGNITUDE->value)
            ->set('name', $member->name)
            ->set('email', $member->email)
            ->set('phone', $member->phone)
            ->call('save')
            ->assertHasErrors(['status' => 'required'])
            ->assertSet('isSubmitted', false);

        $this->assertDatabaseMissing('visitors', [
            'event_id' => $event->id,
            'email' => $member->email,
        ]);
    }

    public function test_it_rejects_sakit_status_when_event_is_not_checkable(): void
    {
        Mail::fake();

        $member = $this->makeMember();

        $event = $this->createEvent([
            'checkable' => false,
            'session' => ['offline'],
        ]);

        Livewire::test(RegistranFormComponent::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])
            ->set('type', VisitorType::MAGNITUDE->value)
            ->set('name', $member->name)
            ->set('email', $member->email)
            ->set('phone', $member->phone)
            ->set('status', VisitorStatusType::SAKIT->value)
            ->call('save')
            ->assertHasErrors(['status' => 'in'])
            ->assertSet('isSubmitted', false);
    }

    public function test_it_requires_substituted_by_for_substitute_when_event_is_not_checkable(): void
    {
        Mail::fake();

        $member = $this->makeMember();

        $event = $this->createEvent([
            'checkable' => false,
            'session' => ['offline'],
        ]);

        Livewire::test(RegistranFormComponent::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])
            ->set('type', VisitorType::MAGNITUDE->value)
            ->set('name', $member->name)
            ->set('email', $member->email)
            ->set('phone', $member->phone)
            ->set('status', VisitorStatusType::SUBSTITUTE->value)
            ->call('save')
            ->assertHasErrors(['substituted_by' => 'required'])
            ->assertSet('isSubmitted', false);
    }

    public function test_it_does_not_require_status_when_event_is_checkable(): void
    {
        Mail::fake();

        $member = $this->makeMember();

        $event = $this->createEvent([
            'checkable' => true,
            'session' => ['offline'],
        ]);

        Livewire::test(RegistranFormComponent::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])
            ->set('type', VisitorType::MAGNITUDE->value)
            ->set('name', $member->name)
            ->set('email', $member->email)
            ->set('phone', $member->phone)
            ->set('invited_by', 'A Member')
            ->call('save')
            ->assertSet('isSubmitted', true);
    }

    public function test_bni11aug_shows_attendance_status_when_event_is_not_checkable(): void
    {
        $event = $this->createEvent([
            'checkable' => false,
            'session' => ['offline'],
        ]);

        Livewire::test(Bni11Aug::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])
            ->set('type', VisitorType::MAGNITUDE->value)
            ->assertSee('STATUS KEHADIRAN')
            ->assertSeeHtml('value="'.VisitorStatusType::HADIR->value.'"')
            ->assertSeeHtml('value="'.VisitorStatusType::SUBSTITUTE->value.'"');
    }

    private function makeMember(): Member
    {
        return Member::create([
            'name' => 'Magnitude Member',
            'email' => 'member@example.com',
            'phone' => '08123456789',
        ]);
    }

    /**
     * @param  array<string, mixed>  $eventAttributes
     * @param  array<string, mixed>  $detailAttributes
     */
    private function createEvent(array $eventAttributes = [], array $detailAttributes = []): Event
    {
        $event = Event::create([
            'slug' => $eventAttributes['slug'] ?? 'attendance-status-event',
            'name' => $eventAttributes['name'] ?? 'Attendance Status Event',
            'start_date' => $eventAttributes['start_date'] ?? now()->addWeek()->toDateString(),
            'registration_date' => $eventAttributes['registration_date'] ?? now()->subDay()->toDateString(),
            'session' => $eventAttributes['session'] ?? ['offline'],
            'checkable' => $eventAttributes['checkable'] ?? false,
            'checkable_one' => $eventAttributes['checkable_one'] ?? false,
        ]);

        EventDetail::create([
            'event_id' => $event->id,
            'online_time' => $detailAttributes['online_time'] ?? '08:00:00',
            'offline_time' => $detailAttributes['offline_time'] ?? '09:00:00',
            'offline_address' => $detailAttributes['offline_address'] ?? 'Main Hall',
            'offline_foods' => $detailAttributes['offline_foods'] ?? null,
            'food_type' => $detailAttributes['food_type'] ?? FoodType::BUFFET,
            'show_invoice_upload' => $detailAttributes['show_invoice_upload'] ?? false,
            'food_required' => $detailAttributes['food_required'] ?? false,
            'override_online_visitor_type' => $detailAttributes['override_online_visitor_type'] ?? false,
            'override_offline_visitor_type' => $detailAttributes['override_offline_visitor_type'] ?? false,
            'online_visitor_type_list' => $detailAttributes['online_visitor_type_list'] ?? null,
            'offline_visitor_type_list' => $detailAttributes['offline_visitor_type_list'] ?? null,
            'excluded_payment_list' => $detailAttributes['excluded_payment_list'] ?? null,
            'registration_payment_prices' => $detailAttributes['registration_payment_prices'] ?? null,
            'default_registration_fee' => array_key_exists('default_registration_fee', $detailAttributes)
                ? $detailAttributes['default_registration_fee']
                : null,
        ]);

        return $event->refresh()->load('detail');
    }
}
