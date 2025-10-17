<?php

namespace App\Events;

use App\Models\Order;
use App\Models\Dish;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */

    public function broadcastOn()
    {
        return new Channel('orders');
    }

    public function broadcastAs()
    {
        return 'OrderCreated';
    }

    public function broadcastWith()
    {
        // Convert order items JSON to array
        $items = is_array($this->order->items) ? $this->order->items : json_decode($this->order->items, true);

        $firstItem = $items[0] ?? null;

        // Map first dish ID to its name
        if ($firstItem) {
            $dish = Dish::find($firstItem['dish_id']);
            $dishName = $dish ? $dish->name : 'Dish #' . $firstItem['dish_id'];
        } else {
            $dishName = 'Unknown';
        }

        return [
            'id' => $this->order->id,
            'product_name' => $dishName,
            'quantity' => $firstItem['qty'] ?? 0,
            'status' => $this->order->status,
            'created_at' => $this->order->created_at->toDateTimeString()
        ];
    }


}
