<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\URL;

class Customer extends Authenticatable
{
    protected $table = 'customers';
    protected $primaryKey = 'customer_id';
    const UPDATED_AT = null;
    protected $guarded = [];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'email_verified_at' => 'datetime'];
    }

    // No remember_token column in the SDD schema.
    public function getRememberTokenName() { return ''; }

    public function hasVerifiedEmail(): bool { return $this->email_verified_at !== null; }

    public function markEmailAsVerified(): void { $this->forceFill(['email_verified_at' => now()])->save(); }

    /** Signed link valid for 24 hours (UC02). The hash ties the link to the current email address. */
    public function verificationUrl(): string
    {
        return URL::temporarySignedRoute('customer.verification.verify', now()->addHours(24), [
            'customer' => $this->customer_id,
            'hash' => sha1($this->email),
        ]);
    }

    public function cart() { return $this->hasOne(Cart::class, 'customer_id', 'customer_id'); }
    public function invoices() { return $this->hasMany(Invoice::class, 'customer_id', 'customer_id'); }
}
