<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\MemberController;
use App\Mail\VisitorMail;
use App\Models\Visitor;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

Route::redirect('/', '/event')->name('home');

Route::group(['prefix' => 'event', 'as' => 'event.'], function () {

    Route::get('/', [EventController::class, 'index'])->name('index');

    Route::get('{slug}', [EventController::class, 'show'])->name('show');

    Route::get('{slug}/register', [EventController::class, 'register'])->name('register');

    Route::get('{event:slug}/payment/{visitor}', [EventController::class, 'payment'])
        ->middleware('signed')
        ->name('payment');
});

Route::group(['prefix' => 'members', 'as' => 'members.'], function () {

    Route::get('/', [MemberController::class, 'index'])->name('index');

});

Route::get('contact', [ContactController::class, 'index'])->name('contact.index');

Route::post('contact', [ContactController::class, 'send'])->name('contact.send');

Route::get('test-email-register', function () {

    $visitor = Visitor::inRandomOrder()->limit(1)->get()->first();
    // $visitor = Visitor::find(117);
    Mail::to('test@mail.com')->send(new VisitorMail($visitor));
});

Route::get('test-email-contact', function () {

    $visitor = Visitor::inRandomOrder()->limit(1)->get()->first();

    // $visitor = Visitor::find(117);
    return new App\Mail\VisitorMail($visitor);
});
