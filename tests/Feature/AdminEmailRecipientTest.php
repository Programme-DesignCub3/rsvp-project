<?php

namespace Tests\Feature;

use App\Filament\Resources\AdminEmailRecipientResource\Pages\CreateAdminEmailRecipient;
use App\Filament\Resources\AdminEmailRecipientResource\Pages\ListAdminEmailRecipients;
use App\Models\AdminEmailRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminEmailRecipientTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_admin_email_recipient(): void
    {
        $admin = User::factory()->create(['email' => 'dean@designcub3.com']);

        Livewire::actingAs($admin)
            ->test(CreateAdminEmailRecipient::class)
            ->fillForm([
                'name' => 'Operations Team',
                'email' => 'operations@example.com',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(AdminEmailRecipient::class, [
            'name' => 'Operations Team',
            'email' => 'operations@example.com',
        ]);
    }

    public function test_admin_can_browse_and_search_admin_email_recipients(): void
    {
        $admin = User::factory()->create(['email' => 'dean@designcub3.com']);

        $recipient = AdminEmailRecipient::factory()->create([
            'name' => 'Operations Team',
            'email' => 'operations@example.com',
        ]);
        $otherRecipient = AdminEmailRecipient::factory()->create();

        Livewire::actingAs($admin)
            ->test(ListAdminEmailRecipients::class)
            ->assertCanSeeTableRecords([$recipient, $otherRecipient])
            ->searchTable('operations@example.com')
            ->assertCanSeeTableRecords([$recipient])
            ->assertCanNotSeeTableRecords([$otherRecipient]);
    }

    public function test_admin_can_edit_admin_email_recipient_from_modal(): void
    {
        $admin = User::factory()->create(['email' => 'dean@designcub3.com']);

        $recipient = AdminEmailRecipient::factory()->create([
            'name' => 'Operations Team',
            'email' => 'operations@example.com',
        ]);

        Livewire::actingAs($admin)
            ->test(ListAdminEmailRecipients::class)
            ->callTableAction('edit', $recipient, [
                'name' => 'Marketing Team',
                'email' => 'marketing@example.com',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas(AdminEmailRecipient::class, [
            'id' => $recipient->id,
            'name' => 'Marketing Team',
            'email' => 'marketing@example.com',
        ]);
    }

    public function test_admin_can_reorder_admin_email_recipients(): void
    {
        $admin = User::factory()->create(['email' => 'dean@designcub3.com']);

        $firstRecipient = AdminEmailRecipient::factory()->create([
            'name' => 'First Recipient',
        ]);
        $secondRecipient = AdminEmailRecipient::factory()->create([
            'name' => 'Second Recipient',
        ]);

        Livewire::actingAs($admin)
            ->test(ListAdminEmailRecipients::class)
            ->call('reorderTable', [$secondRecipient->id, $firstRecipient->id]);

        $this->assertDatabaseHas(AdminEmailRecipient::class, [
            'id' => $secondRecipient->id,
            'order_column' => 1,
        ]);

        $this->assertDatabaseHas(AdminEmailRecipient::class, [
            'id' => $firstRecipient->id,
            'order_column' => 2,
        ]);
    }

    public function test_admin_email_recipient_email_must_be_unique(): void
    {
        $admin = User::factory()->create(['email' => 'dean@designcub3.com']);

        AdminEmailRecipient::factory()->create([
            'email' => 'operations@example.com',
        ]);

        Livewire::actingAs($admin)
            ->test(CreateAdminEmailRecipient::class)
            ->fillForm([
                'name' => 'Another Team',
                'email' => 'operations@example.com',
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'unique']);

        $this->assertDatabaseCount(AdminEmailRecipient::class, 1);
    }
}
