Hello {{ $user->name }},

Click this link to verify your AYULEDGERS email:
{!! $verificationUrl !!}

Or enter this six-digit verification code:
{{ $code }}

The link and code expire in 10 minutes. If you did not create an account, you can ignore this email.
