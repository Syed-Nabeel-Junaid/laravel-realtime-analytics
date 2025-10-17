<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'rating',
    ];

    // A restaurant has many dishes
    public function dishes()
    {
        return $this->hasMany(Dish::class);
    }

    // A restaurant has many orders
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
