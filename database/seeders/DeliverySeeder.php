<?php 
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DeliverySeeder extends Seeder
{
    public function run()
    {
        $orders = DB::table('orders')->pluck('id');

        foreach ($orders as $orderId) {
            DB::table('deliveries')->insert([
                'order_id' => $orderId,
                'driver_id' => rand(1, 5),
                'delivery_time' => Carbon::now()->subMinutes(rand(10, 120)),
                'estimated_duration' => rand(20, 60), // in minutes
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
