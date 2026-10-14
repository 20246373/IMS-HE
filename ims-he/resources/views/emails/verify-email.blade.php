<p>Hello {{ $customer->first_name }},</p>
<p>Thanks for signing up with Ibay Enterprises. Please confirm your email address to activate your account:</p>
<p><a href="{{ $url }}">Verify my email</a></p>
<p>If the button does not work, copy this link into your browser:<br>{{ $url }}</p>
<p>This link expires in 24 hours. If you did not sign up, you can ignore this email.</p>
