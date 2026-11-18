<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = ['active' => 'boolean'];

    public function variants()
    {
        return $this->hasMany(Variant::class);
    }
}
