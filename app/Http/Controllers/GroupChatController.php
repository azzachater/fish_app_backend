<?php

namespace App\Http\Controllers;

use App\Events\GroupMessageEvent;
use App\Models\User;
use App\Models\GroupConversation;
use App\Models\GroupMessage;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Events\NotificationEvent;

class GroupChatController extends Controller
{
    
public function createGroup(Request $request)
{
    $request->validate([
        'name' => 'required|string',
        'avatar' => 'nullable|string',
        'member_ids' => 'required|array',
        'member_ids.*' => 'exists:users,id',
    ]);

    
    $existingGroup = GroupConversation::where('name', $request->name)->first();
    if ($existingGroup) {
        return response()->json(['message' => 'Group with this name already exists'], 400);
    }

    
    $group = GroupConversation::create([
        'name' => $request->name,
        'avatar' => $request->avatar,
        'owner_id' => auth()->id(),
    ]);

    
    $group->members()->attach(array_merge($request->member_ids, [auth()->id()]));

   
    $group->load(['members.profile']);

    return response()->json($group, 201);
}

    
    public function addUserToGroup(Request $request, $groupId)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $group = GroupConversation::findOrFail($groupId);
        $group->members()->syncWithoutDetaching($request->user_id);

        return response()->json(['message' => 'User added to group']);
    }

    public function getMyGroups()
    {
        $groups = auth()->user()->groupConversations()
            ->with([
                'members.profile',
                'messages' => function ($query) {
                    $query->latest()->limit(1)->with('sender.profile');
                }
            ])
            ->get();

        return response()->json($groups);
    }

    public function getGroupMessages($groupId)
    {
        $group = GroupConversation::with(['messages.sender.profile'])->findOrFail($groupId);

        return response()->json($group->messages);
    }

    public function sendGroupMessage(Request $request, $groupId)
{
    $request->validate([
        'content' => 'required|string',
    ]);

    $group = GroupConversation::with('members')->findOrFail($groupId);

    $message = GroupMessage::create([
        'group_conversation_id' => $group->id,
        'sender_id' => auth()->id(),
        'content' => $request->content,
    ]);

    foreach ($group->members as $member) {
        if ($member->id !== auth()->id()) {
            // Message lu/non-lu
            \DB::table('group_message_user')->insert([
                'group_message_id' => $message->id,
                'user_id' => $member->id,
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // ✅ Créer notification
            $notif = Notification::create([
                'sender_id' => auth()->id(),
                'receiver_id' => $member->id,
                'message' => auth()->user()->name . ' a envoyé un message dans le groupe "' . $group->name . '"',
                'type' => 'group',
                'group_conversation_id' => $group->id,
            ]);
            broadcast(new NotificationEvent($notif))->toOthers();
            \Log::info('Message pour le membre', ['message_id' => $message->id, 'user_id' => $member->id]);

        }
    }
   

    broadcast(new GroupMessageEvent($message))->toOthers();
    \Log::info('Event triggered', ['message_id' => $message->id]);

    return response()->json($message->load('sender.profile'), 201);
}
    public function markGroupMessagesAsRead($groupId)
{
    $userId = auth()->id();

    // Marquer tous les messages du groupe comme lus pour l'utilisateur
    \DB::table('group_message_user')
        ->join('group_messages', 'group_message_user.group_message_id', '=', 'group_messages.id')
        ->where('group_messages.group_conversation_id', $groupId)
        ->where('group_message_user.user_id', $userId)
        ->where('group_message_user.is_read', false)
        ->update(['group_message_user.is_read' => true]);

    return response()->json(['message' => 'Messages marked as read']);
}
public function getGroupUnreadCount($groupId)
{
    $userId = auth()->id();

    $count = \DB::table('group_message_user')
        ->join('group_messages', 'group_message_user.group_message_id', '=', 'group_messages.id')
        ->where('group_messages.group_conversation_id', $groupId)
        ->where('group_message_user.user_id', $userId)
        ->where('group_message_user.is_read', false)
        ->count();

    return response()->json(['unread_count' => $count]);
}
        
}
