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

        $notifications = Notification::where('receiver_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }

    public function markAsRead($id)
    {
        $notification = Notification::where('id', $id)
            ->where('receiver_id', auth()->id())
            ->firstOrFail();

        $notification->is_read = true;
        $notification->save();

        return response()->json(['status' => 'success']);
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
