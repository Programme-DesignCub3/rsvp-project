<?php

namespace Tests\Feature;

use App\Filament\Exports\ContactMessageExporter;
use App\Filament\Resources\ContactMessageResource\Pages\ListContactMessages;
use App\Filament\Resources\ContactMessageResource\Pages\ViewContactMessage;
use App\Mail\ContactMail;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class ContactMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_saves_message_and_sends_email(): void
    {
        Mail::fake();

        $response = $this->post(route('contact.send'), [
            'fullname' => 'John Doe',
            'businessClassification' => 'Technology',
            'companyName' => 'Acme Corp',
            'email' => 'john@example.com',
            'phone' => '08123456789',
            'message' => 'I would like to join the next event.',
        ]);

        $response->assertRedirect()
            ->assertSessionHas('success', 'Message has been sent!');

        $this->assertDatabaseHas(ContactMessage::class, [
            'fullname' => 'John Doe',
            'business_classification' => 'Technology',
            'company_name' => 'Acme Corp',
            'email' => 'john@example.com',
            'phone' => '08123456789',
            'message' => 'I would like to join the next event.',
            'is_read' => false,
        ]);

        Mail::assertSent(ContactMail::class);
    }

    public function test_contact_form_still_saves_message_when_email_fails(): void
    {
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new RuntimeException('Connection could not be established with host "mailpit:1025"'));

        $response = $this->post(route('contact.send'), [
            'fullname' => 'John Doe',
            'businessClassification' => 'Technology',
            'companyName' => 'Acme Corp',
            'email' => 'john@example.com',
            'phone' => '08123456789',
            'message' => 'I would like to join the next event.',
        ]);

        $response->assertRedirect()
            ->assertSessionHas('success', 'Message has been sent!');

        $this->assertDatabaseHas(ContactMessage::class, [
            'fullname' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_contact_form_requires_all_fields(): void
    {
        Mail::fake();

        $response = $this->post(route('contact.send'), []);

        $response->assertSessionHasErrors([
            'fullname',
            'businessClassification',
            'companyName',
            'email',
            'phone',
            'message',
        ]);

        $this->assertDatabaseCount(ContactMessage::class, 0);
        Mail::assertNothingSent();
    }

    public function test_admin_can_browse_contact_messages(): void
    {
        $admin = User::factory()->create(['email' => 'dean@designcub3.com']);

        $message = ContactMessage::factory()->create([
            'message' => 'This is a very long contact message that should be truncated in the table list because it contains many words for testing purposes.',
        ]);
        $readMessage = ContactMessage::factory()->create(['is_read' => true]);

        Livewire::actingAs($admin)
            ->test(ListContactMessages::class)
            ->assertCanSeeTableRecords([$message, $readMessage])
            ->assertSee($message->fullname)
            ->assertSee($message->email)
            ->assertSee(str($message->message)->words(10)->toString());
    }

    public function test_admin_can_search_and_filter_contact_messages(): void
    {
        $admin = User::factory()->create(['email' => 'dean@designcub3.com']);

        $unreadMessage = ContactMessage::factory()->create([
            'fullname' => 'Unread Sender',
            'is_read' => false,
            'created_at' => now()->subDays(2),
        ]);
        $readMessage = ContactMessage::factory()->create([
            'fullname' => 'Read Sender',
            'is_read' => true,
            'created_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(ListContactMessages::class)
            ->searchTable('Unread Sender')
            ->assertCanSeeTableRecords([$unreadMessage])
            ->assertCanNotSeeTableRecords([$readMessage])
            ->searchTable(null)
            ->set('activeTab', 'unread')
            ->assertCanSeeTableRecords([$unreadMessage])
            ->assertCanNotSeeTableRecords([$readMessage])
            ->set('activeTab', 'read')
            ->assertCanSeeTableRecords([$readMessage])
            ->assertCanNotSeeTableRecords([$unreadMessage])
            ->set('activeTab', 'all')
            ->filterTable('created_at', [
                'from' => now()->toDateString(),
                'until' => now()->toDateString(),
            ])
            ->assertCanSeeTableRecords([$readMessage])
            ->assertCanNotSeeTableRecords([$unreadMessage]);
    }

    public function test_admin_can_export_with_only_one_date(): void
    {
        $admin = User::factory()->create(['email' => 'dean@designcub3.com']);

        ContactMessage::factory()->create([
            'created_at' => now()->subDays(5),
        ]);
        ContactMessage::factory()->create([
            'created_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(ListContactMessages::class)
            ->callTableAction('export', null, [
                'from' => now()->subDays(6)->toDateString(),
            ]);

        $this->assertDatabaseHas('exports', [
            'exporter' => ContactMessageExporter::class,
            'successful_rows' => 2,
        ]);

        Livewire::actingAs($admin)
            ->test(ListContactMessages::class)
            ->callTableAction('export', null, [
                'until' => now()->subDay()->toDateString(),
            ]);

        $this->assertDatabaseHas('exports', [
            'exporter' => ContactMessageExporter::class,
            'successful_rows' => 1,
        ]);
    }

    public function test_admin_can_export_contact_messages_with_filters(): void
    {
        $admin = User::factory()->create(['email' => 'dean@designcub3.com']);

        ContactMessage::factory()->create([
            'is_read' => false,
            'created_at' => now()->subDays(2),
        ]);
        ContactMessage::factory()->create([
            'is_read' => true,
            'created_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(ListContactMessages::class)
            ->callTableAction('export', null, [
                'from' => now()->subDays(3)->toDateString(),
                'until' => now()->subDay()->toDateString(),
                'is_read' => '0',
            ]);

        $this->assertDatabaseHas('exports', [
            'exporter' => ContactMessageExporter::class,
            'successful_rows' => 1,
        ]);

        $this->assertDatabaseHas('exports', [
            'exporter' => ContactMessageExporter::class,
            'total_rows' => 1,
        ]);
    }

    public function test_viewing_a_message_marks_it_as_read(): void
    {
        $admin = User::factory()->create(['email' => 'dean@designcub3.com']);

        $message = ContactMessage::factory()->create(['is_read' => false]);

        Livewire::actingAs($admin)
            ->test(ViewContactMessage::class, ['record' => $message->getRouteKey()])
            ->assertSuccessful();

        $this->assertTrue($message->refresh()->is_read);
    }
}
