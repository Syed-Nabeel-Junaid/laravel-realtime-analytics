<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'user_id',
        'total_cost',
        'items',
        'order_time',
        'status',
    ];

    protected $casts = [
        'items' => 'array',
        'order_time' => 'datetime',
    ];

    // Order belongs to a restaurant
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    // Order has one delivery
    public function delivery()
    {
        return $this->hasOne(Delivery::class);
    }
}
