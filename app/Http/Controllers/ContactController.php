<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Mail\ContactMail;
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

        try {
            Mail::to('david@designcub3.com')
                ->send(new ContactMail($validatedData));

            Log::info('Email sent to david@designcub3.com with data: ', $validatedData);
        } catch (Throwable $exception) {
            Log::error('Contact form email failed to send.', [
                'contact_message_id' => $contactMessage->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return redirect()->back()->with('success', 'Message has been sent!');
    }
}
