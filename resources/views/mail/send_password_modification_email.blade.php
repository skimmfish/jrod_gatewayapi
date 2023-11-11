<x-mail::message>
Hi, {{$username}}, you or someone requested for password modification, kindly use the code below to validate your request.
Afterwards, you would be redirected to where you are to enter your new password.

OTP: {{$code}}

Thank you,<br>
{{ config('app.name') }}
</x-mail::message>
