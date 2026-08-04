<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Kirim notifikasi ke satu user.
     *
     * Penyimpanan dibatasi: sisakan 100 notif terbaru per user,
     * sisanya dihapus agar tabel tidak menumpuk.
     */
    public static function send(
        int $userId,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): void {
        Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);

        // MariaDB butuh LIMIT bila pakai OFFSET.
        $excessIds = Notification::where('user_id', $userId)
            ->latest('id')
            ->skip(100)
            ->take(1000000)
            ->pluck('id');

        if ($excessIds->isNotEmpty()) {
            Notification::whereIn('id', $excessIds)->delete();
        }
    }

    /**
     * Kirim notifikasi ke semua admin.
     */
    public static function sendToAdmins(
        string $type,
        string $title,
        string $message,
        array $data = []
    ): void {
        User::where('role', 'admin')->get()->each(function ($admin) use ($type, $title, $message, $data) {
            self::send($admin->id, $type, $title, $message, $data);
        });
    }
}
