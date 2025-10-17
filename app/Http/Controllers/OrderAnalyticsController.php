<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Dish;

class OrderAnalyticsController extends Controller
{

public function popularDishes(Request $request) {
    $restaurant_id = $request->query('restaurant_id', 1);

    $topDishes = Order::where('restaurant_id', $restaurant_id)
        ->whereIn('status', ['pending', 'completed'])
        ->get()
        ->flatMap(function($order){
            // Ensure $order->items is always an array
            $items = is_array($order->items) ? $order->items : (json_decode($order->items, true) ?? []);
            return $items;
        })
        ->groupBy('dish_id')
        ->map(function($dishItems, $dish_id){
            $dish = Dish::find($dish_id);

            // Convert Collection to array before using array_column
            $dishArray = $dishItems->toArray();

            return [
                'dish_id' => $dish_id,
                'dish_name' => $dish ? $dish->name : 'Dish #' . $dish_id,
                'quantity_sold' => array_sum(array_column($dishArray, 'qty')),
            ];
        })
        ->sortByDesc('quantity_sold')
        ->values()
        ->take(5)
        ->map(function($dish, $index){
            $dish['rank'] = $index + 1;
            return $dish;
        });

    return response()->json($topDishes);
}

public function deliveryTimes(Request $request)
{
    // Optional: filter by restaurant
    $restaurant_id = $request->query('restaurant_id');

    // Get deliveries joined with orders
    $query = \DB::table('deliveries')
        ->join('orders', 'deliveries.order_id', '=', 'orders.id')
        ->select(
            'deliveries.delivery_time',
            'deliveries.estimated_duration',
            'orders.order_time'
        );

    if ($restaurant_id) {
        $query->where('orders.restaurant_id', $restaurant_id);
    }

    $deliveries = $query->get();

    // Compute actual delivery durations in minutes
    $deliveries = $deliveries->map(function ($d) {
        $actualDuration = strtotime($d->delivery_time) - strtotime($d->order_time);
        return [
            'actual_duration' => round($actualDuration / 60, 2), // minutes
            'estimated_duration' => $d->estimated_duration,
            'date' => date('Y-m-d', strtotime($d->delivery_time)),
            'week' => date('o-W', strtotime($d->delivery_time)), // ISO week
        ];
    });

    // Daily average
    $daily = $deliveries->groupBy('date')->map(function ($group, $date) {
        $avg = collect($group)->avg('actual_duration');
        return round($avg, 2);
    });

    // Weekly average
    $weekly = $deliveries->groupBy('week')->map(function ($group, $week) {
        $avg = collect($group)->avg('actual_duration');
        return round($avg, 2);
    });

    return response()->json([
        'daily_avg_delivery_minutes' => $daily,
        'weekly_avg_delivery_minutes' => $weekly,
    ]);
}


    // Peak ordering hours
    public function peakHours(Request $request)
    {
        $peakHours = Order::selectRaw('HOUR(order_time) as hour, COUNT(*) as total_orders')
            ->groupBy('hour')
            ->orderByDesc('total_orders')
            ->get();

        return response()->json($peakHours);
    }

}
