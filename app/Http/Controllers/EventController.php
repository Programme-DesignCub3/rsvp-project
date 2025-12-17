<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Event;
use App\Models\Member;
use App\Models\Visitor;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::with('detail')
            ->incoming()
            ->orderBy('start_date')
            ->where('hide', false)
            ->paginate(12)
            ->where(function ($event) {
                $date = now()->format('Y-m-d');
                $time = now()->format('H:i:s');
                $online_time = $event->is_online_event ? $event->detail->online_time : null;
                $offline_time = $event->is_offline_event ? $event->detail->offline_time : null;
                if ($event->start_date == $date) {
                    return $online_time > $time || $offline_time > $time;
                }

                return true;

            })
            ->sortBy(function ($event) {

                $online_time = $event->is_online_event ? $event->detail->online_time : null;
                $offline_time = $event->is_offline_event ? $event->detail->offline_time : null;
                $r = array_diff([$online_time, $offline_time], [null]);

                return min($r);

            })
            ->sortBy('start_date');

        $past_events = Event::with('detail')->past()->orderBy('start_date', 'desc')
            ->where('hide', false)
            ->paginate(12);

        $eventsCount = Event::with('detail')
            ->where('hide', false)
            ->count();
        $memberCount = Member::where('hide', false)->count();
        $visitorCount = floor((int) Visitor::count() / 100) * 100;

        // Modulus events on 3
        $skeletonsCount = max(0, 3 - ($events->count() % 3));

        if ($skeletonsCount == 3) {
            $skeletonsCount = 0;
        }

        $showcases = Banner::where('enable', true)->orderBy('order_column')->get();

        return view('events', [
            'events' => $events,
            'past_events' => $past_events,
            'eventsCount' => $eventsCount,
            'memberCount' => $memberCount,
            'visitorCount' => $visitorCount,
            'skeletonsCount' => $skeletonsCount,
            'showcases' => $showcases,
        ]);
    }

    public function show($slug)
    {
        $isDisabled = false;
        $event = Event::with('detail')->where('slug', $slug)->first();

        if ($event == null) {
            abort(404);
        }

        if ($event->detail == null || $event->detail->online_link == null) {
            abort(404);
        }

        if ($event->isEnded()) {
            $isDisabled = true;
        }

        if (! $event->detail->enable_registration) {
            $isDisabled = true;
        }

        return view('visitors.landing', [
            'slug' => $slug,
            'event' => $event,
            'isDisabled' => $isDisabled,
            'is_offline_event' => str_contains($slug, 'offline'),
        ]);
    }

    public function register($slug)
    {
        $event = Event::with('detail')->where('slug', $slug)->first();

        if ($event == null) {
            abort(404);
        }

        if ($event->detail == null || $event->detail->online_link == null) {
            abort(404);
        }

        if ($event->coming_soon) {
            return redirect()->route('event.show', $slug);
        }

        if ($event->isRegistrationEnded()) {
            return redirect()->route('event.show', $slug);
        }

        if ($event->isEnded()) {
            return redirect()->route('event.show', $slug);
        }

        if (! $event->detail->enable_registration) {
            return redirect()->route('event.show', $slug);
        }

        return view('register-visitor', ['slug' => $slug, 'event' => $event]);
    }
}
