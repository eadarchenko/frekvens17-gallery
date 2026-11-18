<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['owner_session', 'idempotency_key'];

    protected $casts = ['address' => 'array', 'is_test' => 'boolean'];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
