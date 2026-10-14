<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

// UC02: sent to a new customer with a 24-hour signed verification link.
class VerifyCustomerEmail extends Mailable
{
    public function __construct(public Customer $customer, public string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verify your IMS-HE account');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.verify-email');
    }
}
