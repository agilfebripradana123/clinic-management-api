<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Daftar notifikasi user yang login.
     */
    public function index(Request $request)
    {
        $query = Notification::where('user_id', $request->user()->id)
            ->latest();

        if ($request->has('unread_only')) {
            $query->where('is_read', false);
        }

        return response()->json(
            $query->paginate($request->integer('per_page', 20))
        );
    }

    /**
     * Tandai satu notifikasi sudah dibaca.
     */
    public function markRead(Notification $notification)
    {
        abort_if(
            $notification->user_id !== request()->user()->id,
            403
        );

        $notification->update(['is_read' => true]);

        return response()->json([
            'message' => 'Notifikasi ditandai sudah dibaca.',
        ]);
    }

    /**
     * Tandai semua notifikasi user sudah dibaca.
     */
    public function markAllRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'message' => 'Semua notifikasi ditandai sudah dibaca.',
        ]);
    }

    /**
     * Jumlah notifikasi belum dibaca (untuk badge).
     */
    public function unreadCount(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json(['unread_count' => $count]);
    }
}
