<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;

class ContactController extends Controller
{
    public function index()
    {
        return view('contact.index');
    }

    public function send(Request $request)
    {
        $validatedData = $request->validate([
            'fullname' => 'required',
            'businessClassification' => 'required',
            'companyName' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'message' => 'required',
        ]);

        if (! $validatedData) {
            return Redirect::back()->withErrors($request->all())->withInput();
        }

        Mail::to('david@designcub3.com')
            ->send(new \App\Mail\ContactMail($validatedData));

        Log::info('Email sent to david@designcub3.com with data: ', $validatedData);

        return redirect()->back()->with('success', 'Message has been sent!');
    }
}
