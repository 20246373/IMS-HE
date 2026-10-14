<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

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
    public function getRememberTokenName()
    {
        return '';
    }

    public function cart()
    {
        return $this->hasOne(Cart::class, 'customer_id', 'customer_id')->where('status', 'Draft')->latest();
    }

    public function carts()
    {
        return $this->hasMany(Cart::class, 'customer_id', 'customer_id');
    }

    public function submittedCarts()
    {
        return $this->carts()->where('status', 'Submitted');
    }

    public function getRouteKeyName()
    {
        return 'customer_id';
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'customer_id', 'customer_id');
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;
    }
}
