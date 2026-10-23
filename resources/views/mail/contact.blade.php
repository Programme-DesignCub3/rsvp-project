<x-mail::message>
# Contact Form Message

**Name:** {{ $data['fullname'] }}  
**Email:** {{ $data['email'] }}  
**Phone:** {{ $data['phone'] }}  
**Company:** {{ $data['companyName'] }}  
**Business Classification:** {{ $data['businessClassification'] }}

**Message:**

{!! nl2br(e($data['message'])) !!}
</x-mail::message>
