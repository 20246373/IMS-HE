<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';

    protected $primaryKey = 'category_id';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id', 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
