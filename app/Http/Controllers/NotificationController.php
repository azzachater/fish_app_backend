<?php

namespace App\Http\Controllers;
use App\Models\Notification;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
{
    $userId = auth()->id();

    // Vérifie s’il y a des notifications non lues
    $hasUnread = Notification::where('receiver_id', $userId)
                    ->where('is_read', false)
                    ->exists();

    // Puis marque les notifications comme lues
    Notification::where('receiver_id', $userId)
        ->where('is_read', false)
        ->update(['is_read' => true]);

    // Récupère toutes les notifications
    $notifications = Notification::where('receiver_id', $userId)
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json([
        'unread' => $hasUnread,
        'notifications' => $notifications
    ]);
}

   public function destroy($id)
{
    $notification = Notification::where('id', $id)
        ->where('receiver_id', auth()->id())
        ->first();

    if (!$notification) {
        return response()->json(['error' => 'Notification introuvable.'], 404);
    }

    $notification->delete();

    return response()->json(['status' => 'Notification supprimée avec succès.']);
}


}
