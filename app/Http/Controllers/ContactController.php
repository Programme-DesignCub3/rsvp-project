<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Mail\ContactMail;
use App\Models\AdminEmailRecipient;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class ContactController extends Controller
{
    public function index(): View
    {
        return view('contact.index');
    }

    public function send(StoreContactRequest $request): RedirectResponse
    {
        $validatedData = $request->validated();

        $contactMessage = ContactMessage::create([
            'fullname' => $validatedData['fullname'],
            'business_classification' => $validatedData['businessClassification'],
            'company_name' => $validatedData['companyName'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'message' => $validatedData['message'],
        ]);

        $validatedData['createdDate'] = $contactMessage->created_at;

        try {
            $adminRecipients = AdminEmailRecipient::query()
                ->orderBy('order_column')
                ->get();

            $validRecipients = $adminRecipients
                ->filter(fn (AdminEmailRecipient $recipient): bool => filter_var(trim((string) $recipient->email), FILTER_VALIDATE_EMAIL) !== false)
                ->map(fn (AdminEmailRecipient $recipient): array => [
                    'email' => trim((string) $recipient->email),
                    'name' => filled($recipient->name) ? $recipient->name : trim((string) $recipient->email),
                ])
                ->values();

            $invalidRecipients = $adminRecipients
                ->reject(fn (AdminEmailRecipient $recipient): bool => filter_var(trim((string) $recipient->email), FILTER_VALIDATE_EMAIL) !== false)
                ->map(fn (AdminEmailRecipient $recipient): array => [
                    'id' => $recipient->id,
                    'name' => $recipient->name,
                    'email' => $recipient->email,
                ])
                ->values()
                ->all();

            if ($invalidRecipients !== []) {
                Log::warning('Contact form email skipped invalid admin recipients.', [
                    'contact_message_id' => $contactMessage->id,
                    'invalid_recipients' => $invalidRecipients,
                ]);
            }

            if ($validRecipients->isEmpty()) {
                Log::warning('Contact form email skipped because no valid admin recipients are configured.', [
                    'contact_message_id' => $contactMessage->id,
                ]);
            } else {

                Mail::to('contact@bnimagnitude.com')
                    ->cc($adminRecipients)
                    ->send(new ContactMail($validatedData));

                Log::info('Contact form email sent to admin recipients.', [
                    'contact_message_id' => $contactMessage->id,
                    'to' => 'contact@bnimagnitude.com',
                    'cc_recipients' => $adminRecipients,
                ]);
            }

        } catch (Throwable $exception) {
            Log::error('Contact form email failed to send.', [
                'contact_message_id' => $contactMessage->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return redirect()->back()->with('success', 'Message has been sent!');
    }
}
