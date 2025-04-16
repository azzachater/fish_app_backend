<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Notification;

class NotificationEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $notification;
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }
    public function broadcastOn():Channel
    {
        // Chaque user écoute son propre canal privé
        return new PrivateChannel('notifications.' . $this->notification->receiver_id);
    }
    public function broadcastAs(): string
    {
        return 'new-message-notification';
    }
    public function broadcastWith():array
    {
        return [
            'id' => $this->notification->id,
            'message' => $this->notification->message,
            'type' => $this->notification->type,
            'sender_id' => $this->notification->sender_id,
            'receiver_id' => $this->notification->receiver_id,
            'created_at' => $this->notification->created_at->toDateTimeString(),
        ];
    }
}
