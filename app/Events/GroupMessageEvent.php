<?php



namespace App\Events;

use App\Models\GroupMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class GroupMessageEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(GroupMessage $message)
    {
        $this->message = $message->load('sender.profile'); // charge les infos du sender
    }

    public function broadcastOn():Channel
    {
        \Log::info("Broadcasting message to channel: group.{$this->message->group_conversation_id}", [
            'message_id' => $this->message->id,
            'content' => $this->message->content
        ]);
        return new PrivateChannel('group.' . $this->message->group_conversation_id);
    }
    public function broadcastAs(): string
    {
        return 'new-group-message';
    }
    public function broadcastWith()
    {
        return [
            'id' => $this->message->id,
            'group_conversation_id' => $this->message->group_conversation_id,
            'content' => $this->message->content,
            'sender' => [
                'id' => $this->message->sender->id,
                'name' => $this->message->sender->name,
                'avatar' => $this->message->sender->profile->avatar ?? null,
            ],
            'created_at' => $this->message->created_at->toDateTimeString()
        ];
    }
}
