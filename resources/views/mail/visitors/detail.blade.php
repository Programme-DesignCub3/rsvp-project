<x-mail::message>
# YOUR BOOKING CONFIRMATION

Hi {{ $visitor->name }},

Thank you for registering to our event. Here are the details of the event you have registered for:

@if ($visitor->payment_amount !== null)
- Registration No: #{{ $visitor->registration_sequence }}
- Total Payment: {{ $visitor->payment_amount > 0 ? 'Rp ' . number_format($visitor->payment_amount, 0, ',', '.') : 'Gratis' }}

@if ($visitor->payment_amount > 0)
- Transfer to: Bank Jago 101916230906 a/n Stefanny Liezal
- BERITA: {{ $visitor->payment_berita }}
@endif

<x-mail::button :url="$visitor->payment_url">
View Payment Details
</x-mail::button>
@endif

@if ($visitor->is_online)
- Date: {{ $visitor->event->start_date_full_formatted }}
- Time: {{ $visitor->event->detail->online_time_no_seconds }}
- Location: Online
- Admission: {{ $visitor->payment_amount === null || $visitor->payment_amount === 0 ? 'Free' : 'Paid' }}

<x-mail::button url="https://drive.google.com/drive/folders/1tJ4z08SV7Pd3d3n5q06UTmeiXFIV2RuZ">
Download Zoom Meeting Background
</x-mail::button>

<div>
Zoom Link:<br>
<span style="word-break: break-all"> {{ $visitor->event->detail->online_link }}</span>
</div>

<x-mail::button  :url="$visitor->event->detail->online_link">
{{-- https://bnionline.zoom.us/j/93733175392?pwd=Pwbs4oc3Vk15MANDShzhE5JDuiWSaq.1 --}}
Visit Zoom Link
</x-mail::button>
@endif

@if ($visitor->is_offline)
- ORDER ID : #{{ $visitor->order_id }}
- Date: {{ $visitor->event->start_date_full_formatted }}
- Time: {{ $visitor->event->detail->offline_time_no_seconds }}
- Location: {!! $visitor->event->detail->offline_address !!}

{{-- Add map url button --}}
<x-mail::button :url="$visitor->event->detail->offline_location">
    View Map Location
</x-mail::button>
@endif


Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
