<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\GroupConversation;
use App\Models\GroupMessage;
use Illuminate\Http\Request;

class GroupChatController extends Controller
{
    // 🆕 Créer un groupe
    // 🆕 Créer un groupe
public function createGroup(Request $request)
{
    $request->validate([
        'name' => 'required|string',
        'avatar' => 'nullable|string',
        'member_ids' => 'required|array',
        'member_ids.*' => 'exists:users,id',
    ]);

    // Vérifier si un groupe avec le même nom existe déjà
    $existingGroup = GroupConversation::where('name', $request->name)->first();
    if ($existingGroup) {
        return response()->json(['message' => 'Group with this name already exists'], 400);
    }

    // Créer le groupe
    $group = GroupConversation::create([
        'name' => $request->name,
        'avatar' => $request->avatar,
        'owner_id' => auth()->id(),
    ]);

    // Ajouter l'utilisateur authentifié et les autres membres au groupe
    $group->members()->attach(array_merge($request->member_ids, [auth()->id()]));

    // Charger les membres avec leur profil (nom + avatar)
    $group->load(['members.profile']);

    return response()->json($group, 201);
}

    // ➕ Ajouter un utilisateur à un groupe
    public function addUserToGroup(Request $request, $groupId)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $group = GroupConversation::findOrFail($groupId);
        $group->members()->syncWithoutDetaching($request->user_id);

        return response()->json(['message' => 'User added to group']);
    }

    // 📜 Récupérer tous les groupes de l’utilisateur connecté
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

    // 💬 Récupérer les messages d’un groupe
    public function getGroupMessages($groupId)
    {
        $group = GroupConversation::with(['messages.sender.profile'])->findOrFail($groupId);

        return response()->json($group->messages);
    }

    // ✉️ Envoyer un message dans un groupe
    public function sendGroupMessage(Request $request, $groupId)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $group = GroupConversation::with('members')->findOrFail($groupId);

        // Créer le message
        $message = GroupMessage::create([
            'group_conversation_id' => $group->id,
            'sender_id' => auth()->id(),
            'content' => $request->content,
        ]);
        
        // Marquer comme non lu pour les autres membres
        foreach ($group->members as $member) {
            if ($member->id !== auth()->id()) {
                \DB::table('group_message_user')->insert([
                    'group_message_id' => $message->id,
                    'user_id' => $member->id,
                    'is_read' => false,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
        
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
