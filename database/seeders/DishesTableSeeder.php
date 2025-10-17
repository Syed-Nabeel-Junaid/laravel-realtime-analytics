<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DishesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    // public function run()
    // {
    //     //
    // }

    public function run()
    {
        DB::table('dishes')->insert([
            [
                'restaurant_id' => 1,
                'name' => 'Pizza Margherita',
                'category' => 'Pizza',
                'price' => 250.00,
                'popularity_score' => 0,
                'availability_status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'restaurant_id' => 1,
                'name' => 'Veg Burger',
                'category' => 'Burger',
                'price' => 150.00,
                'popularity_score' => 0,
                'availability_status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'restaurant_id' => 1,
                'name' => 'Pasta Alfredo',
                'category' => 'Pasta',
                'price' => 300.00,
                'popularity_score' => 0,
                'availability_status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
