<?php

namespace Tests\Feature;

use App\Enums\FoodType;
use App\Enums\VisitorType;
use App\Livewire\Bni11Aug;
use App\Mail\VisitorMail;
use App\Models\Event;
use App\Models\EventDetail;
use App\Models\Member;
use App\Models\MemberCategory;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class Bni11AugTest extends TestCase
{
    use RefreshDatabase;

    public function test_industry_connect_field_only_renders_for_visitor_type(): void
    {
        MemberCategory::factory()->create(['name' => 'Financial Services']);
        MemberCategory::factory()->create(['name' => 'Food & Beverage']);

        $event = $this->createEvent();

        Livewire::test(Bni11Aug::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])
            ->assertDontSee('WHICH INDUSTRY YOU WANT TO CONNECT WITH?')
            ->set('type', VisitorType::VISITOR->value)
            ->assertSee('WHICH INDUSTRY YOU WANT TO CONNECT WITH?')
            ->assertSee('Financial Services')
            ->assertSee('Food & Beverage')
            ->assertSeeHtml('wire:model.change="industries"')
            ->set('type', VisitorType::MAGNITUDE->value)
            ->assertDontSee('WHICH INDUSTRY YOU WANT TO CONNECT WITH?');
    }

    public function test_visitor_can_save_without_selecting_an_industry(): void
    {
        Mail::fake();

        $event = $this->createEvent();

        Livewire::test(Bni11Aug::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])
            ->set('type', VisitorType::VISITOR->value)
            ->set('name', 'New Visitor')
            ->set('business', 'Consulting')
            ->set('company', 'Acme')
            ->set('phone', '08123456789')
            ->set('email', 'new@example.com')
            ->set('invited_by', 'A Member')
            ->call('save')
            ->assertSet('isSubmitted', true);

        $visitor = Visitor::where('event_id', $event->id)
            ->where('email', 'new@example.com')
            ->firstOrFail();

        $this->assertArrayNotHasKey('connect_industry', $visitor->meta ?? []);

        Mail::assertSent(VisitorMail::class);
    }

    public function test_visitor_rejects_unknown_industry_selection(): void
    {
        Mail::fake();

        MemberCategory::factory()->create(['name' => 'Financial Services']);

        $event = $this->createEvent();

        Livewire::test(Bni11Aug::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])
            ->set('type', VisitorType::VISITOR->value)
            ->set('name', 'New Visitor')
            ->set('business', 'Consulting')
            ->set('company', 'Acme')
            ->set('phone', '08123456789')
            ->set('email', 'new@example.com')
            ->set('invited_by', 'A Member')
            ->set('industries', ['Unknown Industry'])
            ->call('save')
            ->assertHasErrors(['industries.0' => 'in'])
            ->assertSet('isSubmitted', false);

        Mail::assertNothingSent();
    }

    public function test_visitor_can_select_multiple_industries_and_they_are_stored(): void
    {
        Mail::fake();

        MemberCategory::factory()->create(['name' => 'Financial Services']);
        MemberCategory::factory()->create(['name' => 'Food & Beverage']);

        $event = $this->createEvent();

        Livewire::test(Bni11Aug::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])
            ->set('type', VisitorType::VISITOR->value)
            ->set('name', 'New Visitor')
            ->set('business', 'Consulting')
            ->set('company', 'Acme')
            ->set('phone', '08123456789')
            ->set('email', 'new@example.com')
            ->set('invited_by', 'A Member')
            ->set('industries', ['Financial Services', 'Food & Beverage'])
            ->call('save')
            ->assertSet('isSubmitted', true);

        $visitor = Visitor::where('event_id', $event->id)
            ->where('email', 'new@example.com')
            ->firstOrFail();

        $this->assertSame('Financial Services, Food & Beverage', $visitor->meta['connect_industry']);

        Mail::assertSent(VisitorMail::class);
    }

    public function test_magnitude_registration_does_not_require_industries(): void
    {
        Member::create([
            'name' => 'Magnitude Member',
            'email' => 'member@example.com',
            'phone' => '08123456789',
        ]);

        $event = $this->createEvent();

        $component = Livewire::test(Bni11Aug::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])->set('type', VisitorType::MAGNITUDE->value);

        $this->assertArrayNotHasKey('industries', $component->instance()->rules());
    }

    public function test_industries_are_cleared_when_switching_away_from_visitor_type(): void
    {
        $event = $this->createEvent();

        Livewire::test(Bni11Aug::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])
            ->set('type', VisitorType::VISITOR->value)
            ->set('industries', ['Financial Services'])
            ->set('type', VisitorType::MAGNITUDE->value)
            ->assertSet('industries', []);
    }

    /**
     * @param  array<string, mixed>  $eventAttributes
     * @param  array<string, mixed>  $detailAttributes
     */
    private function createEvent(array $eventAttributes = [], array $detailAttributes = []): Event
    {
        $event = Event::create([
            'slug' => $eventAttributes['slug'] ?? 'visitor-day-11-aug-2026',
            'name' => $eventAttributes['name'] ?? 'Visitor Day',
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
            'override_offline_visitor_type' => $detailAttributes['override_offline_visitor_type'] ?? true,
            'online_visitor_type_list' => $detailAttributes['online_visitor_type_list'] ?? null,
            'offline_visitor_type_list' => $detailAttributes['offline_visitor_type_list']
                ?? [VisitorType::VISITOR->value, VisitorType::MAGNITUDE->value],
            'excluded_payment_list' => $detailAttributes['excluded_payment_list'] ?? null,
            'registration_payment_prices' => $detailAttributes['registration_payment_prices'] ?? null,
            'default_registration_fee' => array_key_exists('default_registration_fee', $detailAttributes)
                ? $detailAttributes['default_registration_fee']
                : null,
        ]);

        return $event->refresh()->load('detail');
    }
}
