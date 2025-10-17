<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Dish;
use App\Models\Restaurant;

class DashboardController extends Controller
{
    public function getDashboard(Request $request)
    {
        $restaurant_id = $request->query('restaurant_id', 1);
        $thirtyMinutesAgo = now()->subMinutes(30);

        // -----------------------------
        // 1️⃣ Total Orders
        // -----------------------------
        $totalOrders = Order::where('restaurant_id', $restaurant_id)->count();

        // -----------------------------
        // 2️⃣ Pending Orders
        // -----------------------------
        $pendingOrders = Order::where('restaurant_id', $restaurant_id)
            ->where('status', 'pending')
            ->count();

        // -----------------------------
        // 2️⃣ Completed Orders
        // -----------------------------
        $completedOrders = Order::where('restaurant_id', $restaurant_id)
            ->where('status', 'completed')
            ->count();
        // -----------------------------
        // 3️⃣ Active Orders > 30 mins
        // -----------------------------
        $activeOrders = Order::where('restaurant_id', $restaurant_id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where('order_time', '<', $thirtyMinutesAgo)
            ->with('restaurant')
            ->get()
            ->map(function($order) {
                $items = $order->items;
                if (is_string($items)) $items = json_decode($items, true) ?? [];
                elseif (!is_array($items)) $items = [];

                $items = array_map(function($item) {
                    $dish = Dish::find($item['dish_id']);
                    $item['dish_name'] = $dish ? $dish->name : 'Dish #' . $item['dish_id'];
                    return $item;
                }, $items);

                $order->items = $items;
                return $order;
            });

        // -----------------------------
        // 4️⃣ Top 5 Popular Dishes
        // -----------------------------
        $topDishes = Order::where('restaurant_id', $restaurant_id)
            ->whereIn('status', ['pending', 'completed'])
            ->get()
            ->flatMap(function($order){
                $items = is_array($order->items) ? $order->items : (json_decode($order->items, true) ?? []);
                return $items;
            })
            ->groupBy('dish_id')
            ->map(function($dishItems, $dish_id){
                $dishArray = $dishItems->toArray();
                $dish = Dish::find($dish_id);

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

        // -----------------------------
        // 5️⃣ Average Delivery Times
        // -----------------------------
        $deliveries = \DB::table('deliveries')
            ->join('orders', 'deliveries.order_id', '=', 'orders.id')
            ->select(
                'deliveries.delivery_time',
                'deliveries.estimated_duration',
                'orders.order_time'
            )
            ->where('orders.restaurant_id', $restaurant_id)
            ->get()
            ->map(function ($d) {
                $actualDuration = strtotime($d->delivery_time) - strtotime($d->order_time);
                return [
                    'actual_duration' => round($actualDuration / 60, 2),
                    'estimated_duration' => $d->estimated_duration,
                    'date' => date('Y-m-d', strtotime($d->delivery_time)),
                    'week' => date('o-W', strtotime($d->delivery_time)),
                ];
            });

        $dailyAvg = $deliveries->groupBy('date')->map(fn($group) => round(collect($group)->avg('actual_duration'), 2));
        $weeklyAvg = $deliveries->groupBy('week')->map(fn($group) => round(collect($group)->avg('actual_duration'), 2));

        // -----------------------------
        // 6️⃣ Peak Ordering Hours
        // -----------------------------
        $peakHours = Order::where('restaurant_id', $restaurant_id)
            ->selectRaw('HOUR(order_time) as hour, COUNT(*) as total_orders')
            ->groupBy('hour')
            ->orderByDesc('total_orders')
            ->get();

        // -----------------------------
        // Final Response
        // -----------------------------
        return response()->json([
            'total_orders' => $totalOrders,
            'pending_orders' => $pendingOrders,
            'completed_orders' => $completedOrders,
            'active_orders' => [
                'count' => $activeOrders->count(),
                'data' => $activeOrders
            ],
            'top_dishes' => $topDishes,
            'delivery_times' => [
                'daily_avg_minutes' => $dailyAvg,
                'weekly_avg_minutes' => $weeklyAvg
            ],
            'peak_hours' => $peakHours
        ]);
    }
}
