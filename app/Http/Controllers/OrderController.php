<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Dish;
use Pusher\Pusher;


class OrderController extends Controller
{
    // POST /api/orders  → create new order
    public function store(Request $request)
    {
        // Validate request
        $request->validate([
            'restaurant_id' => 'required|integer|exists:restaurants,id',
            'items' => 'required|array|min:1',
            'items.*.dish_id' => 'required|integer|exists:dishes,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        // Load all involved dishes at once to avoid N+1
        $dishIds = collect($request->items)->pluck('dish_id');
        $dishes = Dish::whereIn('id', $dishIds)->get()->keyBy('id');

        // Calculate total cost
        $totalCost = collect($request->items)->sum(function ($item) use ($dishes) {
            $dish = $dishes->get($item['dish_id']);
            $price = $dish ? $dish->price : 0;
            return $price * $item['qty'];
        });

        // Add dish names to items
        $itemsWithNames = collect($request->items)->map(function ($item) use ($dishes) {
            $dish = $dishes->get($item['dish_id']);
            $item['dish_name'] = $dish ? $dish->name : 'Dish #' . $item['dish_id'];
            $item['price'] = $dish ? $dish->price : 0;
            return $item;
        });

        // Create the order
        $order = Order::create([
            'restaurant_id' => $request->restaurant_id,
            'user_id' => $request->user_id,
            'items' => $itemsWithNames->toJson(),
            'status' => 'pending',
            'order_time' => now(),
            'total_cost' => $totalCost,
        ]);

        // Broadcast via Pusher
        $pusher = new Pusher(
            env('PUSHER_APP_KEY'),
            env('PUSHER_APP_SECRET'),
            env('PUSHER_APP_ID'),
            [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'useTLS' => true
            ]
        );

        $pusher->trigger('orders', 'OrderCreated', [
            'id' => $order->id,
            'restaurant_id' => $order->restaurant_id,
            'user_id' => $order->user_id,
            'items' => $itemsWithNames,
            'status' => $order->status,
            'order_time' => $order->order_time->toDateTimeString(),
            'total_cost' => $totalCost,
        ]);

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order
        ]);
    }
        // GET /api/orders/active  → list incomplete orders older than 30 minutes
public function getActiveOrders()
{
    $thirtyMinutesAgo = now()->subMinutes(30);

    $orders = \App\Models\Order::whereNotIn('status', ['completed', 'cancelled'])
                ->where('order_time', '<', $thirtyMinutesAgo)
                ->with('restaurant')
                ->orderBy('order_time', 'desc')
                ->get()
                ->map(function($order) {
                    // Handle both string (JSON) or array
                    $items = $order->items;

                    if (is_string($items)) {
                        $items = json_decode($items, true) ?? [];
                    } elseif (!is_array($items)) {
                        $items = [];
                    }

                    $items = array_map(function($item) {
                        $dish = \App\Models\Dish::find($item['dish_id']);
                        $item['dish_name'] = $dish ? $dish->name : 'Dish #' . $item['dish_id'];
                        return $item;
                    }, $items);

                    $order->items = $items;
                    return $order;
                });

    return response()->json([
        'count' => $orders->count(),
        'data'  => $orders
    ]);
}




}
