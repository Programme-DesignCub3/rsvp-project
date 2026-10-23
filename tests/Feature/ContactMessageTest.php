<?php

namespace Tests\Feature;

use App\Filament\Exports\ContactMessageExporter;
use App\Filament\Resources\ContactMessageResource\Pages\ListContactMessages;
use App\Filament\Resources\ContactMessageResource\Pages\ViewContactMessage;
use App\Mail\ContactMail;
use App\Models\AdminEmailRecipient;
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

        $recipient = AdminEmailRecipient::factory()->create([
            'name' => 'Admin Recipient',
            'email' => 'admin@example.com',
        ]);
        $ccRecipient = AdminEmailRecipient::factory()->create([
            'name' => 'Secondary Recipient',
            'email' => 'secondary@example.com',
        ]);

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

        Mail::assertSent(ContactMail::class, function (ContactMail $mail) use ($recipient, $ccRecipient): bool {
            $contactMessage = ContactMessage::query()->firstOrFail();

            return $mail->hasTo($recipient->email, $recipient->name)
                && $mail->hasCc($ccRecipient->email, $ccRecipient->name)
                && str_contains($mail->envelope()->subject, 'John Doe')
                && str_contains($mail->envelope()->subject, $contactMessage->created_at->format('l, d-m-y | H:i:s'))
                && ! str_contains($mail->envelope()->subject, '(timestamp)');
        });
    }

    public function test_contact_form_uses_the_first_ordered_recipient_as_primary(): void
    {
        Mail::fake();

        $ccRecipient = AdminEmailRecipient::factory()->create([
            'name' => 'Second In Line',
            'email' => 'second@example.com',
        ]);
        $primaryRecipient = AdminEmailRecipient::factory()->create([
            'name' => 'Primary Contact',
            'email' => 'primary@example.com',
        ]);

        $primaryRecipient->update(['order_column' => 1]);
        $ccRecipient->update(['order_column' => 2]);

        $this->post(route('contact.send'), [
            'fullname' => 'John Doe',
            'businessClassification' => 'Technology',
            'companyName' => 'Acme Corp',
            'email' => 'john@example.com',
            'phone' => '08123456789',
            'message' => 'I would like to join the next event.',
        ]);

        Mail::assertSent(ContactMail::class, function (ContactMail $mail) use ($primaryRecipient, $ccRecipient): bool {
            return $mail->hasTo($primaryRecipient->email, $primaryRecipient->name)
                && $mail->hasCc($ccRecipient->email, $ccRecipient->name);
        });
    }

    public function test_contact_form_skips_empty_or_invalid_admin_email_recipients(): void
    {
        Mail::fake();

        $validRecipient = AdminEmailRecipient::factory()->create([
            'name' => 'Valid Recipient',
            'email' => 'valid@example.com',
        ]);
        AdminEmailRecipient::factory()->create([
            'name' => 'Invalid Recipient',
            'email' => 'not-an-email',
        ]);
        AdminEmailRecipient::factory()->create([
            'name' => 'Empty Recipient',
            'email' => '',
        ]);

        $this->post(route('contact.send'), [
            'fullname' => 'John Doe',
            'businessClassification' => 'Technology',
            'companyName' => 'Acme Corp',
            'email' => 'john@example.com',
            'phone' => '08123456789',
            'message' => 'I would like to join the next event.',
        ]);

        Mail::assertSent(ContactMail::class, function (ContactMail $mail) use ($validRecipient): bool {
            return $mail->hasTo($validRecipient->email, $validRecipient->name)
                && count($mail->cc) === 0;
        });
    }

    public function test_contact_textarea_has_no_leading_whitespace_and_auto_resizes(): void
    {
        $response = $this->get(route('contact.index'));

        $response->assertOk();

        $html = $response->getContent();

        $this->assertMatchesRegularExpression('/<textarea\b[^>]*><\/textarea>/s', $html);
        $this->assertStringContainsString('x-init="resize($el)"', $html);
        $this->assertStringContainsString('@input="resize($event.target)"', $html);
    }

    public function test_contact_textarea_uses_error_border_when_message_is_invalid(): void
    {
        $this->from(route('contact.index'))->post(route('contact.send'), []);

        $response = $this->get(route('contact.index'));

        $response->assertOk();
        $this->assertStringContainsString('border-red-300', $response->getContent());
    }

    public function test_contact_form_preserves_message_line_breaks(): void
    {
        Mail::fake();

        AdminEmailRecipient::factory()->create();

        $message = "First line\nSecond line\n\nSecond paragraph";

        $this->post(route('contact.send'), [
            'fullname' => 'John Doe',
            'businessClassification' => 'Technology',
            'companyName' => 'Acme Corp',
            'email' => 'john@example.com',
            'phone' => '08123456789',
            'message' => $message,
        ]);

        $this->assertDatabaseHas(ContactMessage::class, [
            'email' => 'john@example.com',
            'message' => $message,
        ]);

        Mail::assertSent(ContactMail::class, function (ContactMail $mail): bool {
            $body = $mail->render();

            return str_contains($body, 'First line<br')
                && str_contains($body, 'Second line<br')
                && str_contains($body, 'Second paragraph');
        });
    }

    public function test_contact_form_saves_message_but_skips_email_when_no_admin_recipients_are_configured(): void
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
            'email' => 'john@example.com',
        ]);

        Mail::assertNothingSent();
    }

    public function test_contact_form_still_saves_message_when_email_fails(): void
    {
        AdminEmailRecipient::factory()->create();

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
