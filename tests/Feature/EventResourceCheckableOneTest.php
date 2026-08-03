<?php

namespace Tests\Feature;

use App\Filament\Resources\EventResource\Pages\CreateEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventResourceCheckableOneTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkable_one_is_hidden_until_both_sessions_and_checkable_are_active(): void
    {
        $admin = User::factory()->create(['email' => 'dean@designcub3.com']);

        Livewire::actingAs($admin)
            ->test(CreateEvent::class)
            ->assertFormFieldIsHidden('checkable_one')
            ->set('data.session', ['offline', 'online'])
            ->assertFormFieldIsHidden('checkable_one')
            ->set('data.checkable', true)
            ->assertFormFieldIsVisible('checkable_one');
    }

    public function test_checkable_one_is_reset_when_offline_session_is_removed(): void
    {
        $admin = User::factory()->create(['email' => 'dean@designcub3.com']);

        Livewire::actingAs($admin)
            ->test(CreateEvent::class)
            ->set('data.session', ['offline', 'online'])
            ->set('data.checkable', true)
            ->set('data.checkable_one', true)
            ->assertSet('data.checkable_one', true)
            ->assertFormFieldIsVisible('checkable_one')
            ->set('data.session', ['offline'])
            ->assertSet('data.checkable_one', false)
            ->assertFormFieldIsHidden('checkable_one');
    }

    public function test_checkable_one_is_reset_when_online_session_is_removed(): void
    {
        $admin = User::factory()->create(['email' => 'dean@designcub3.com']);

        Livewire::actingAs($admin)
            ->test(CreateEvent::class)
            ->set('data.session', ['offline', 'online'])
            ->set('data.checkable', true)
            ->set('data.checkable_one', true)
            ->set('data.session', ['online'])
            ->assertSet('data.checkable_one', false)
            ->assertFormFieldIsHidden('checkable_one');
    }

    public function test_checkable_one_is_reset_when_checkable_is_disabled(): void
    {
        $admin = User::factory()->create(['email' => 'dean@designcub3.com']);

        Livewire::actingAs($admin)
            ->test(CreateEvent::class)
            ->set('data.session', ['offline', 'online'])
            ->set('data.checkable', true)
            ->set('data.checkable_one', true)
            ->set('data.checkable', false)
            ->assertSet('data.checkable_one', false)
            ->assertFormFieldIsHidden('checkable_one');
    }
}
