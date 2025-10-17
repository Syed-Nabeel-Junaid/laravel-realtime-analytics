<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'driver_id',
        'delivery_time',
        'estimated_duration',
    ];

    // Delivery belongs to an order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
