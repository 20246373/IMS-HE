<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = ['unit_price' => 'decimal:2', 'is_active' => 'boolean'];

    public function category() { return $this->belongsTo(Category::class); }
    public function invoiceItems() { return $this->hasMany(InvoiceItem::class); }

    public function scopeActive(Builder $q): Builder { return $q->where('is_active', true); }

    public function isLowStock(): bool { return $this->stock_quantity <= $this->reorder_point; }
}
