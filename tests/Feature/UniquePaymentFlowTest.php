<?php

namespace Tests\Feature;

use App\Enums\FoodType;
use App\Enums\UserRole;
use App\Enums\VisitorType;
use App\Filament\Bendahara\Pages\Dashboard as BendaharaDashboard;
use App\Filament\Bendahara\Resources\EventResource\Pages\ListEvents;
use App\Filament\Bendahara\Resources\EventResource\Pages\ManageVisitor;
use App\Filament\Bendahara\Widgets\EventPaymentSummary;
use App\Filament\Bendahara\Widgets\PaymentStatsOverview;
use App\Filament\Resources\EventResource\Pages\ManageVisitor as AdminManageVisitor;
use App\Livewire\PaymentPage;
use App\Livewire\RegistranFormComponent;
use App\Mail\VisitorMail;
use App\Models\Event;
use App\Models\EventDetail;
use App\Models\User;
use App\Models\Visitor;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class UniquePaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_redirects_to_signed_payment_page_and_freezes_unique_amount(): void
    {
        Mail::fake();

        $event = $this->createEvent([
            'default_registration_fee' => 500000,
            'show_invoice_upload' => true,
            'enable_unique_payment_code' => true,
        ]);

        $component = Livewire::test(RegistranFormComponent::class, [
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
            ->assertHasNoErrors()
            ->assertRedirectContains('/payment/');

        $visitor = Visitor::query()->where('email', 'new@example.com')->firstOrFail();

        $this->assertSame(1, $visitor->registration_sequence);
        $this->assertSame(500000, $visitor->payment_base_amount);
        $this->assertSame(500001, $visitor->payment_amount);
        $this->assertSame('New Visitor Visitor', $visitor->payment_berita);
        $this->assertSame('00001', $visitor->order_id);
        $this->assertSame(1, $event->refresh()->registration_counter);
        $this->assertNotNull($visitor->payment_url);
        $this->assertStringContainsString('signature=', $visitor->payment_url);

        $component->assertRedirect($visitor->payment_url);

        Mail::assertSent(VisitorMail::class, function (VisitorMail $mail) use ($visitor): bool {
            return $mail->visitor->is($visitor);
        });
    }

    public function test_second_registration_gets_the_next_sequence_and_amount(): void
    {
        Mail::fake();

        $event = $this->createEvent([
            'default_registration_fee' => 1000000,
            'show_invoice_upload' => true,
            'enable_unique_payment_code' => true,
        ]);

        $first = $this->registerVisitor($event, 'First Visitor', 'first@example.com');
        $second = $this->registerVisitor($event, 'Second Visitor', 'second@example.com');

        $this->assertSame(1, $first->registration_sequence);
        $this->assertSame(1000001, $first->payment_amount);
        $this->assertSame(2, $second->registration_sequence);
        $this->assertSame(1000002, $second->payment_amount);
        $this->assertSame(2, $event->refresh()->registration_counter);
    }

    public function test_sequence_starts_after_existing_visitors_when_the_toggle_is_enabled(): void
    {
        Mail::fake();

        $event = $this->createEvent([
            'default_registration_fee' => 500000,
            'show_invoice_upload' => true,
            'enable_unique_payment_code' => true,
        ]);

        foreach (['Existing One', 'Existing Two'] as $index => $name) {
            Visitor::create([
                'name' => $name,
                'business' => 'Existing Business',
                'company' => 'Existing Company',
                'phone' => '080000000'.$index,
                'email' => 'existing'.$index.'@example.com',
                'invited_by' => 'Host',
                'event_id' => $event->id,
                'is_offline' => true,
                'type' => VisitorType::VISITOR->value,
            ]);
        }

        $visitor = $this->registerVisitor($event, 'New Visitor', 'new@example.com');

        $this->assertSame(3, $visitor->registration_sequence);
        $this->assertSame(500003, $visitor->payment_amount);
        $this->assertSame('00003', $visitor->order_id);
        $this->assertSame(3, $event->refresh()->registration_counter);
    }

    public function test_free_registration_still_uses_a_sequence_and_shows_gratis(): void
    {
        Mail::fake();

        $event = $this->createEvent([
            'default_registration_fee' => 0,
            'show_invoice_upload' => true,
            'enable_unique_payment_code' => true,
        ]);

        $visitor = $this->registerVisitor($event, 'Free Visitor', 'free@example.com');

        $this->assertSame(1, $visitor->registration_sequence);
        $this->assertSame(0, $visitor->payment_base_amount);
        $this->assertSame(0, $visitor->payment_amount);
        $this->assertSame('Free Visitor Visitor', $visitor->payment_berita);

        $this->get($visitor->payment_url)
            ->assertOk()
            ->assertSee('Gratis')
            ->assertDontSee('Bank Jago');
    }

    public function test_payment_page_requires_a_valid_signature_and_shows_payment_details(): void
    {
        Mail::fake();

        $event = $this->createEvent([
            'default_registration_fee' => 500000,
            'show_invoice_upload' => true,
            'enable_unique_payment_code' => true,
        ]);

        $visitor = $this->registerVisitor($event, 'New Visitor', 'new@example.com');

        $this->get(route('event.payment', [
            'event' => $event->slug,
            'visitor' => $visitor->id,
        ]))->assertForbidden();

        $this->get($visitor->payment_url)
            ->assertOk()
            ->assertSee('Registration #1')
            ->assertSee('Rp 500.001')
            ->assertSee('Bank Jago 101916230906 a/n Stefanny Liezal')
            ->assertSee('New Visitor Visitor');
    }

    public function test_payment_page_rejects_a_legacy_visitor_without_a_sequence(): void
    {
        Mail::fake();

        $event = $this->createEvent([
            'default_registration_fee' => 500000,
            'show_invoice_upload' => true,
            'enable_unique_payment_code' => true,
        ]);

        $legacyVisitor = Visitor::create([
            'name' => 'Legacy Visitor',
            'business' => 'Consulting',
            'company' => 'Acme',
            'phone' => '08123456789',
            'email' => 'legacy@example.com',
            'invited_by' => 'A Member',
            'event_id' => $event->id,
            'is_offline' => true,
            'type' => VisitorType::VISITOR->value,
        ]);

        $this->get(URL::signedRoute('event.payment', [
            'event' => $event->slug,
            'visitor' => $legacyVisitor->id,
        ]))->assertNotFound();
    }

    public function test_payment_page_locks_the_visitor_property(): void
    {
        Mail::fake();

        $event = $this->createEvent([
            'default_registration_fee' => 500000,
            'show_invoice_upload' => true,
            'enable_unique_payment_code' => true,
        ]);

        $visitor = $this->registerVisitor($event, 'New Visitor', 'new@example.com');
        $otherVisitor = $this->registerVisitor($event, 'Other Visitor', 'other@example.com');

        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(PaymentPage::class, [
            'event' => $event->refresh()->load('detail'),
            'visitor' => $visitor,
        ])->set('visitor', $otherVisitor);
    }

    public function test_payment_proof_can_be_uploaded_from_the_payment_page(): void
    {
        Mail::fake();
        Storage::fake('public');

        $event = $this->createEvent([
            'default_registration_fee' => 500000,
            'show_invoice_upload' => true,
            'enable_unique_payment_code' => true,
        ]);

        $visitor = $this->registerVisitor($event, 'New Visitor', 'new@example.com');

        Livewire::test(PaymentPage::class, [
            'event' => $event->refresh()->load('detail'),
            'visitor' => $visitor,
        ])
            ->set('payment', UploadedFile::fake()->image('proof.jpg'))
            ->call('submitPaymentProof')
            ->assertHasNoErrors();

        $this->assertTrue($visitor->refresh()->hasMedia('payment_proof'));
    }

    public function test_bendahara_can_access_the_panel_and_verify_payment(): void
    {
        Mail::fake();

        Filament::setCurrentPanel(Filament::getPanel('bendahara'));

        $bendahara = User::factory()->create(['role' => UserRole::Bendahara]);
        $event = $this->createEvent([
            'default_registration_fee' => 500000,
            'show_invoice_upload' => true,
            'enable_unique_payment_code' => true,
        ]);
        $visitor = $this->registerVisitor($event, 'New Visitor', 'new@example.com');

        $this->assertTrue($bendahara->canAccessPanel(Filament::getPanel('bendahara')));
        $this->assertFalse($bendahara->canAccessPanel(Filament::getPanel('admin')));

        $this->actingAs($bendahara);

        Livewire::test(ManageVisitor::class, ['record' => $event->getKey()])
            ->mountTableAction('payment_verification', $visitor)
            ->assertSee('Verify payment?')
            ->callMountedTableAction();

        $visitor->refresh();

        $this->assertTrue($visitor->payment_verified);
        $this->assertNotNull($visitor->payment_verified_at);
        $this->assertSame($bendahara->id, $visitor->payment_verified_by);

        Livewire::test(ManageVisitor::class, ['record' => $event->getKey()])
            ->mountTableAction('payment_verification', $visitor->refresh())
            ->assertSee('Unverify payment?')
            ->callMountedTableAction();

        $visitor->refresh();

        $this->assertFalse($visitor->payment_verified);
        $this->assertNull($visitor->payment_verified_at);
        $this->assertNull($visitor->payment_verified_by);
    }

    public function test_admin_can_verify_payment_with_confirmation(): void
    {
        Mail::fake();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $event = $this->createEvent([
            'default_registration_fee' => 500000,
            'show_invoice_upload' => true,
            'enable_unique_payment_code' => true,
        ]);
        $visitor = $this->registerVisitor($event, 'New Visitor', 'new@example.com');

        $this->actingAs($admin);

        Livewire::test(AdminManageVisitor::class, ['record' => $event->getKey()])
            ->mountTableAction('payment_verification', $visitor)
            ->assertSee('Verify payment?')
            ->callMountedTableAction();

        $visitor->refresh();

        $this->assertTrue($visitor->payment_verified);
        $this->assertSame($admin->id, $visitor->payment_verified_by);
    }

    public function test_bendahara_can_resend_payment_email(): void
    {
        Mail::fake();

        Filament::setCurrentPanel(Filament::getPanel('bendahara'));

        $bendahara = User::factory()->create(['role' => UserRole::Bendahara]);
        $event = $this->createEvent([
            'name' => 'BNI Test Chapter',
            'default_registration_fee' => 500000,
            'show_invoice_upload' => true,
            'enable_unique_payment_code' => true,
        ]);
        $visitor = $this->registerVisitor($event, 'New Visitor', 'new@example.com');

        $this->actingAs($bendahara);

        Livewire::test(ManageVisitor::class, ['record' => $event->getKey()])
            ->mountTableAction('resend_payment_email', $visitor)
            ->assertSee('Resend email?')
            ->assertSee('new@example.com')
            ->callMountedTableAction();

        Mail::assertSent(VisitorMail::class, function (VisitorMail $mail) use ($visitor): bool {
            return $mail->visitor->is($visitor)
                && $mail->envelope()->subject === 'Visitor Attendance Information - BNI Test Chapter';
        });
    }

    public function test_resend_email_action_is_hidden_when_email_is_empty(): void
    {
        Mail::fake();

        Filament::setCurrentPanel(Filament::getPanel('bendahara'));

        $bendahara = User::factory()->create(['role' => UserRole::Bendahara]);
        $event = $this->createEvent([
            'default_registration_fee' => 500000,
            'show_invoice_upload' => true,
            'enable_unique_payment_code' => true,
        ]);
        $visitor = $this->registerVisitor($event, 'No Email', 'no-email@example.com');
        $visitor->update(['email' => '']);

        $this->actingAs($bendahara);

        Livewire::test(ManageVisitor::class, ['record' => $event->getKey()])
            ->assertTableActionHidden('resend_payment_email', $visitor);
    }

    public function test_bendahara_dashboard_widgets_summarise_payments_per_event(): void
    {
        Mail::fake();

        Filament::setCurrentPanel(Filament::getPanel('bendahara'));

        $bendahara = User::factory()->create(['role' => UserRole::Bendahara]);
        $event = $this->createEvent([
            'name' => 'Dashboard Event',
            'default_registration_fee' => 500000,
            'show_invoice_upload' => true,
            'enable_unique_payment_code' => true,
        ]);
        $paid = $this->registerVisitor($event, 'Paid Visitor', 'paid@example.com');
        $paid->update(['payment_verified' => true]);
        $this->registerVisitor($event, 'Pending Visitor', 'pending@example.com');

        $oldVisitor = $this->registerVisitor($event, 'Old Visitor', 'old@example.com');
        $oldVisitor->update(['created_at' => now()->startOfMonth()->subDay()]);

        $emptyEvent = $this->createEvent([
            'slug' => 'empty-event',
            'name' => 'Empty Event',
            'default_registration_fee' => 500000,
            'show_invoice_upload' => true,
            'enable_unique_payment_code' => true,
        ]);

        $this->actingAs($bendahara);

        $filters = [
            'startDate' => now()->startOfMonth()->toDateString(),
            'endDate' => now()->endOfMonth()->toDateString(),
        ];

        Livewire::test(PaymentStatsOverview::class, ['filters' => $filters])
            ->assertSee('Waiting verification')
            ->assertSee('Rp 500.002')
            ->assertSee('Verified payments')
            ->assertSee('Rp 500.001')
            ->assertDontSee('Rp 500.003');

        Livewire::test(EventPaymentSummary::class, ['filters' => $filters])
            ->assertCanSeeTableRecords([$event])
            ->assertCanNotSeeTableRecords([$emptyEvent])
            ->filterTable('has_payments', false)
            ->assertCanSeeTableRecords([$emptyEvent]);
    }

    public function test_bendahara_dashboard_defaults_filters_to_current_month(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('bendahara'));

        $bendahara = User::factory()->create(['role' => UserRole::Bendahara]);

        $this->actingAs($bendahara);

        Livewire::test(BendaharaDashboard::class)
            ->assertSet('filters.startDate', now()->startOfMonth()->toDateString())
            ->assertSet('filters.endDate', now()->endOfMonth()->toDateString());
    }

    public function test_event_payment_summary_lists_latest_created_events_first(): void
    {
        Mail::fake();

        Filament::setCurrentPanel(Filament::getPanel('bendahara'));

        $bendahara = User::factory()->create(['role' => UserRole::Bendahara]);

        $olderEvent = $this->createEvent([
            'slug' => 'older-event',
            'name' => 'Older Event',
            'default_registration_fee' => 500000,
            'show_invoice_upload' => true,
            'enable_unique_payment_code' => true,
        ]);
        $this->registerVisitor($olderEvent, 'Older Visitor', 'older@example.com');
        $olderEvent->update(['created_at' => now()->subDay()]);

        $newerEvent = $this->createEvent([
            'slug' => 'newer-event',
            'name' => 'Newer Event',
            'default_registration_fee' => 500000,
            'show_invoice_upload' => true,
            'enable_unique_payment_code' => true,
        ]);
        $this->registerVisitor($newerEvent, 'Newer Visitor', 'newer@example.com');

        $this->actingAs($bendahara);

        $records = Livewire::test(EventPaymentSummary::class, [
            'filters' => [
                'startDate' => now()->startOfMonth()->toDateString(),
                'endDate' => now()->endOfMonth()->toDateString(),
            ],
        ])->instance()->getTableRecords();

        $this->assertSame(
            [$newerEvent->id, $olderEvent->id],
            $records->pluck('id')->all()
        );
    }

    public function test_bendahara_visitor_list_is_scoped_to_event_and_searchable(): void
    {
        Mail::fake();

        Filament::setCurrentPanel(Filament::getPanel('bendahara'));

        $bendahara = User::factory()->create(['role' => UserRole::Bendahara]);
        $event = $this->createEvent([
            'default_registration_fee' => 500000,
            'show_invoice_upload' => true,
            'enable_unique_payment_code' => true,
        ]);
        $alice = $this->registerVisitor($event, 'Alice', 'alice@example.com');
        $bob = $this->registerVisitor($event, 'Bob', 'bob@example.com');

        $otherEvent = $this->createEvent([
            'slug' => 'another-event',
            'name' => 'Another Event',
            'default_registration_fee' => 500000,
            'show_invoice_upload' => true,
            'enable_unique_payment_code' => true,
        ]);
        $otherVisitor = $this->registerVisitor($otherEvent, 'Other', 'other@example.com');

        $alice->update(['payment_verified' => true]);

        $this->actingAs($bendahara);

        Livewire::test(ListEvents::class)
            ->assertCanSeeTableRecords([$event, $otherEvent]);

        Livewire::test(ManageVisitor::class, ['record' => $event->getKey()])
            ->assertCanSeeTableRecords([$alice, $bob])
            ->assertCanNotSeeTableRecords([$otherVisitor])
            ->searchTable('alice@example.com')
            ->assertCanSeeTableRecords([$alice])
            ->assertCanNotSeeTableRecords([$bob])
            ->searchTable('')
            ->filterTable('payment_verified', true)
            ->assertCanSeeTableRecords([$alice])
            ->assertCanNotSeeTableRecords([$bob]);
    }

    public function test_step_one_preview_shows_base_fee_and_unique_code_note(): void
    {
        $event = $this->createEvent([
            'default_registration_fee' => 500000,
            'show_invoice_upload' => true,
            'enable_unique_payment_code' => true,
        ]);

        Livewire::test(RegistranFormComponent::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])
            ->set('type', VisitorType::VISITOR->value)
            ->assertSee('Payment detail')
            ->assertSee('IDR 500.000')
            ->assertSee('A unique code will be added automatically after you submit.')
            ->assertDontSee('Bank Jago')
            ->assertDontSee('UPLOAD PROOF OF PAYMENT');
    }

    public function test_step_one_preview_updates_dynamically_with_food_selection(): void
    {
        $event = $this->createEvent([
            'default_registration_fee' => 500000,
            'show_invoice_upload' => true,
            'enable_unique_payment_code' => true,
            'offline_foods' => [
                ['food' => 'Lunch', 'price' => '150000'],
                ['food' => 'Dinner', 'price' => '100000'],
            ],
        ]);

        $component = Livewire::test(RegistranFormComponent::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])
            ->set('type', VisitorType::VISITOR->value)
            ->assertSee('IDR 500.000');

        $component->set('food', ['Lunch'])
            ->assertSee('IDR 650.000');

        $component->set('food', ['Dinner'])
            ->assertSee('IDR 600.000');

        $component->set('food', [])
            ->assertSee('IDR 500.000');
    }

    public function test_step_one_preview_shows_free_and_hides_payment_instructions(): void
    {
        $event = $this->createEvent([
            'default_registration_fee' => 0,
            'show_invoice_upload' => true,
            'enable_unique_payment_code' => true,
        ]);

        Livewire::test(RegistranFormComponent::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])
            ->set('type', VisitorType::VISITOR->value)
            ->assertSee('FREE')
            ->assertSee('No payment needed for this registration.')
            ->assertDontSee('Bank Jago')
            ->assertDontSee('UPLOAD PROOF OF PAYMENT');
    }

    /**
     * @param  array<string, mixed>  $detailAttributes
     */
    private function createEvent(array $detailAttributes = []): Event
    {
        $event = Event::create([
            'slug' => $detailAttributes['slug'] ?? 'unique-payment-event',
            'name' => $detailAttributes['name'] ?? 'Unique Payment Event',
            'start_date' => now()->addWeek()->toDateString(),
            'registration_date' => now()->subDay()->toDateString(),
            'session' => ['offline'],
            'checkable' => false,
            'checkable_one' => false,
        ]);

        EventDetail::create([
            'event_id' => $event->id,
            'online_time' => '08:00:00',
            'offline_time' => '09:00:00',
            'offline_address' => 'Main Hall',
            'offline_foods' => $detailAttributes['offline_foods'] ?? null,
            'food_type' => $detailAttributes['food_type'] ?? FoodType::BUFFET,
            'show_invoice_upload' => $detailAttributes['show_invoice_upload'] ?? false,
            'enable_unique_payment_code' => $detailAttributes['enable_unique_payment_code'] ?? false,
            'food_required' => $detailAttributes['food_required'] ?? false,
            'override_online_visitor_type' => false,
            'override_offline_visitor_type' => false,
            'online_visitor_type_list' => null,
            'offline_visitor_type_list' => null,
            'excluded_payment_list' => null,
            'registration_payment_prices' => null,
            'default_registration_fee' => $detailAttributes['default_registration_fee'] ?? null,
        ]);

        return $event->refresh()->load('detail');
    }

    private function registerVisitor(Event $event, string $name, string $email): Visitor
    {
        Livewire::test(RegistranFormComponent::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])
            ->set('type', VisitorType::VISITOR->value)
            ->set('name', $name)
            ->set('business', 'Consulting')
            ->set('company', 'Acme')
            ->set('phone', '08123456789')
            ->set('email', $email)
            ->set('invited_by', 'A Member')
            ->call('save')
            ->assertHasNoErrors();

        return Visitor::query()
            ->where('event_id', $event->id)
            ->where('email', $email)
            ->firstOrFail();
    }
}
