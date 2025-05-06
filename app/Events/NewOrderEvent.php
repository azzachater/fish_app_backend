<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewOrderEvent implements ShouldBroadcast
{
    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('seller.' . $this->order->items()->first()->seller_id);
    }
    public function broadcastWith()
    {
        return [
            'order_id' => $this->order->id,
            'buyer_name' => $this->order->buyer->name,
            'total' => $this->order->total,
            'created_at' => $this->order->created_at->toDateTimeString()
        ];
    }
}
