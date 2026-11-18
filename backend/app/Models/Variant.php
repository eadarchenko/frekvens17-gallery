<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['active' => 'boolean', 'price_cents' => 'integer', 'stock' => 'integer'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
