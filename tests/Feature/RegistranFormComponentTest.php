<?php

namespace Tests\Feature;

use App\Enums\FoodType;
use App\Enums\VisitorType;
use App\Livewire\RegistranFormComponent;
use App\Mail\VisitorMail;
use App\Models\Event;
use App\Models\EventDetail;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class RegistranFormComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_mounts_with_event_sessions_and_available_visitor_types(): void
    {
        $event = $this->createEvent(['session' => ['offline']]);

        Livewire::test(RegistranFormComponent::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])
            ->assertSet('sessions', ['offline'])
            ->assertSet('visitor_type.0', VisitorType::VISITOR)
            ->assertSet('visitor_type.1', VisitorType::MAGNITUDE)
            ->assertSet('visitor_type.2', VisitorType::ALTITUDE);
    }

    public function test_it_switches_single_checkable_session_and_resets_status(): void
    {
        $event = $this->createEvent([
            'checkable_one' => true,
            'session' => ['online', 'offline'],
        ]);

        Livewire::test(RegistranFormComponent::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])
            ->set('status', 'sakit')
            ->set('substituted_by', 'Replacement Member')
            ->call('handleSessionChange', 'offline')
            ->assertSet('sessions', ['offline'])
            ->assertSet('status', null)
            ->assertSet('substituted_by', null)
            ->call('handleSessionChange', 'offline')
            ->assertSet('sessions', []);
    }

    public function test_it_saves_a_basic_visitor_registration(): void
    {
        Mail::fake();

        $event = $this->createEvent([
            'session' => ['offline'],
        ], [
            'show_invoice_upload' => false,
        ]);

        Visitor::create([
            'name' => 'Existing Visitor',
            'business' => 'Existing Business',
            'company' => 'Existing Company',
            'phone' => '0800000000',
            'email' => 'existing@example.com',
            'invited_by' => 'Host',
            'event_id' => $event->id,
            'is_offline' => true,
            'order_id' => '00007',
            'type' => VisitorType::VISITOR->value,
        ]);

        Livewire::test(RegistranFormComponent::class, [
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

        $this->assertDatabaseHas('visitors', [
            'event_id' => $event->id,
            'name' => 'New Visitor',
            'email' => 'new@example.com',
            'is_offline' => true,
            'order_id' => '00008',
            'type' => VisitorType::VISITOR->value,
        ]);

        Mail::assertSent(VisitorMail::class);
    }

    public function test_it_resolves_fixed_payment_price_from_selected_visitor_type(): void
    {
        $event = $this->createEvent([
            'session' => ['offline'],
        ], [
            'food_type' => FoodType::FIXED,
            'offline_foods' => [
                [
                    'visitor_type' => VisitorType::MAGNITUDE->value,
                    'food' => 'Magnitude Dinner',
                    'price' => '125000',
                ],
                [
                    'visitor_type' => VisitorType::VISITOR->value,
                    'food' => 'Visitor Dinner',
                    'price' => '175000',
                ],
            ],
        ]);

        $component = Livewire::test(RegistranFormComponent::class, [
            'slug' => $event->slug,
            'event' => $event,
        ]);

        $component->set('type', VisitorType::MAGNITUDE->value);

        $this->assertSame('IDR 125.000', $component->instance()->paymentAmountLabel());

        $component->set('type', VisitorType::VISITOR->value);

        $this->assertSame('IDR 175.000', $component->instance()->paymentAmountLabel());
    }

    public function test_it_combines_registration_price_and_food_price_in_payment_detail(): void
    {
        $event = $this->createEvent([
            'session' => ['offline'],
        ], [
            'food_type' => FoodType::FIXED,
            'offline_foods' => [
                [
                    'visitor_type' => VisitorType::MAGNITUDE->value,
                    'food' => 'Magnitude Dinner',
                    'price' => '125000',
                ],
                [
                    'visitor_type' => VisitorType::VISITOR->value,
                    'food' => 'Visitor Dinner',
                    'price' => '175000',
                ],
            ],
            'registration_payment_prices' => [
                [
                    'visitor_type' => VisitorType::MAGNITUDE->value,
                    'price' => '99.000',
                    'label' => 'MAGNITUDE early bird',
                ],
                [
                    'visitor_type' => VisitorType::VISITOR->value,
                    'price' => '149000',
                ],
            ],
        ]);

        $component = Livewire::test(RegistranFormComponent::class, [
            'slug' => $event->slug,
            'event' => $event,
        ]);

        $component->set('type', VisitorType::MAGNITUDE->value);

        $this->assertSame('IDR 224.000', $component->instance()->paymentAmountLabel());
        $this->assertSame('MAGNITUDE early bird', $component->instance()->paymentPackageLabel());
        $this->assertSame('Magnitude Dinner', $component->instance()->fixedFoodPackageLabel());
        $this->assertSame(224000, $component->instance()->paymentTotalAmount());
        $this->assertSame([
            [
                'label' => 'Registration fee',
                'description' => 'MAGNITUDE early bird',
                'amount' => 99000,
                'amount_label' => 'IDR 99.000',
            ],
            [
                'label' => 'Food package',
                'description' => 'Magnitude Dinner',
                'amount' => 125000,
                'amount_label' => 'IDR 125.000',
            ],
        ], $component->instance()->paymentBreakdown());

        $component->set('type', VisitorType::VISITOR->value);

        $this->assertSame('IDR 324.000', $component->instance()->paymentAmountLabel());
        $this->assertSame('Visitor', $component->instance()->paymentPackageLabel());
    }

    public function test_it_uses_default_registration_fee_when_visitor_type_has_no_override(): void
    {
        $event = $this->createEvent([
            'session' => ['offline'],
        ], [
            'default_registration_fee' => 100000,
            'registration_payment_prices' => [
                [
                    'visitor_type' => VisitorType::MAGNITUDE->value,
                    'price' => 75000,
                ],
            ],
        ]);

        $component = Livewire::test(RegistranFormComponent::class, [
            'slug' => $event->slug,
            'event' => $event,
        ]);

        $component->set('type', VisitorType::MAGNITUDE->value);
        $this->assertSame('IDR 75.000', $component->instance()->paymentAmountLabel());

        $component->set('type', VisitorType::VISITOR->value);
        $this->assertSame('IDR 100.000', $component->instance()->paymentAmountLabel());
    }

    public function test_zero_default_registration_fee_is_free_and_requires_no_payment_proof(): void
    {
        $event = $this->createEvent([
            'session' => ['offline'],
        ], [
            'default_registration_fee' => 0,
            'show_invoice_upload' => true,
        ]);

        $component = Livewire::test(RegistranFormComponent::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])->set('type', VisitorType::VISITOR->value);

        $this->assertSame('FREE', $component->instance()->paymentAmountLabel());
        $this->assertSame(0, $component->instance()->paymentTotalAmount());
        $this->assertFalse($component->instance()->userShouldUploadInvoice());
        $this->assertSame([
            [
                'label' => 'Registration fee',
                'description' => 'Visitor',
                'amount' => 0,
                'amount_label' => 'FREE',
            ],
        ], $component->instance()->paymentBreakdown());
    }

    public function test_default_registration_fee_is_stored_inside_existing_payment_prices_json(): void
    {
        $event = $this->createEvent(['session' => ['offline']]);
        $detail = $event->detail;

        $detail->default_registration_fee = 100000;
        $detail->registration_payment_prices = [
            [
                'visitor_type' => VisitorType::MAGNITUDE->value,
                'price' => 75000,
            ],
        ];
        $detail->save();
        $detail->refresh();

        $this->assertSame(100000, $detail->default_registration_fee);
        $this->assertSame([
            [
                'visitor_type' => VisitorType::MAGNITUDE->value,
                'price' => 75000,
            ],
        ], $detail->registration_payment_prices);

        $storedPrices = json_decode($detail->getRawOriginal('registration_payment_prices'), true);

        $this->assertContains([
            'visitor_type' => EventDetail::DEFAULT_REGISTRATION_PRICE_TYPE,
            'price' => 100000,
            'label' => null,
        ], $storedPrices);
    }

    public function test_it_only_adds_selected_optional_buffet_food_prices(): void
    {
        $event = $this->createEvent([
            'session' => ['offline'],
        ], [
            'food_type' => FoodType::BUFFET,
            'offline_foods' => [
                ['food' => 'Nasi Goreng', 'price' => '25.000'],
                ['food' => 'Sate Ayam', 'price' => null],
                ['food' => 'Dessert', 'price' => '15000'],
            ],
        ]);

        $component = Livewire::test(RegistranFormComponent::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])->set('food', ['Nasi Goreng', 'Sate Ayam']);

        $this->assertSame('IDR 25.000', $component->instance()->paymentAmountLabel());
        $this->assertSame([
            [
                'label' => 'Food item',
                'description' => 'Nasi Goreng',
                'amount' => 25000,
                'amount_label' => 'IDR 25.000',
            ],
        ], $component->instance()->paymentBreakdown());
    }

    public function test_it_adds_optional_ala_carte_package_price(): void
    {
        $event = $this->createEvent([
            'session' => ['offline'],
        ], [
            'food_type' => FoodType::ALA_CARTE,
            'offline_foods' => [
                [
                    'food' => ['Nasi Goreng', 'Mie Goreng'],
                    'drink' => ['Tea', 'Coffee'],
                    'price' => '35.000',
                ],
            ],
        ]);

        $component = Livewire::test(RegistranFormComponent::class, [
            'slug' => $event->slug,
            'event' => $event,
        ])->set('food', [
            'food' => 'Mie Goreng',
            'drink' => 'Coffee',
        ]);

        $this->assertSame('IDR 35.000', $component->instance()->paymentAmountLabel());
        $this->assertSame('Mie Goreng + Coffee', $component->instance()->paymentSummaryLabel());
    }

    public function test_food_selection_controls_update_live(): void
    {
        $buffetEvent = $this->createEvent([
            'slug' => 'buffet-event',
            'session' => ['offline'],
        ], [
            'food_type' => FoodType::BUFFET,
            'offline_foods' => [
                ['food' => 'Nasi Goreng', 'price' => '25000'],
                ['food' => 'Sate Ayam', 'price' => '30000'],
            ],
        ]);

        Livewire::test(RegistranFormComponent::class, [
            'slug' => $buffetEvent->slug,
            'event' => $buffetEvent,
        ])->assertSeeHtml('wire:model.live="food"');

        $alaCarteEvent = $this->createEvent([
            'slug' => 'ala-carte-event',
            'session' => ['offline'],
        ], [
            'food_type' => FoodType::ALA_CARTE,
            'offline_foods' => [
                [
                    'food' => ['Nasi Goreng', 'Mie Goreng'],
                    'drink' => ['Tea', 'Coffee'],
                    'price' => '35000',
                ],
            ],
        ]);

        Livewire::test(RegistranFormComponent::class, [
            'slug' => $alaCarteEvent->slug,
            'event' => $alaCarteEvent,
        ])
            ->assertSeeHtml('wire:model.live="food.food"')
            ->assertSeeHtml('wire:model.live="food.drink"');
    }

    /**
     * @param  array<string, mixed>  $eventAttributes
     * @param  array<string, mixed>  $detailAttributes
     */
    private function createEvent(array $eventAttributes = [], array $detailAttributes = []): Event
    {
        $event = Event::create([
            'slug' => $eventAttributes['slug'] ?? 'weekly-meeting',
            'name' => $eventAttributes['name'] ?? 'Weekly Meeting',
            'start_date' => $eventAttributes['start_date'] ?? now()->addWeek()->toDateString(),
            'registration_date' => $eventAttributes['registration_date'] ?? now()->subDay()->toDateString(),
            'session' => $eventAttributes['session'] ?? ['online', 'offline'],
            'checkable' => $eventAttributes['checkable'] ?? true,
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
            'default_registration_fee' => $detailAttributes['default_registration_fee'] ?? null,
        ]);

        return $event->refresh()->load('detail');
    }
}
