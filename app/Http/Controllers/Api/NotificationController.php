<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * عرض إشعارات المستخدم المسجل.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(
            max($request->integer('per_page', 15), 1),
            50
        );

        $notifications = $request->user()
            ->notifications()
            ->latest('created_at')
            ->paginate($perPage);

        $items = $notifications
            ->getCollection()
            ->map(
                fn (DatabaseNotification $notification): array =>
                    $this->notificationData($notification)
            )
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'تم تحميل الإشعارات بنجاح.',
            'data' => [
                'notifications' => $items,
                'unread_count' => $request->user()
                    ->unreadNotifications()
                    ->count(),
                'pagination' => [
                    'current_page' =>
                        $notifications->currentPage(),
                    'last_page' =>
                        $notifications->lastPage(),
                    'per_page' =>
                        $notifications->perPage(),
                    'total' =>
                        $notifications->total(),
                ],
            ],
        ]);
    }

    /**
     * عرض عدد الإشعارات غير المقروءة.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $request->user()
                    ->unreadNotifications()
                    ->count(),
            ],
        ]);
    }

    /**
     * تحديد إشعار واحد كمقروء.
     */
    public function markAsRead(
        Request $request,
        string $notificationId,
    ): JsonResponse {
        $notification = $this->findNotification(
            $request,
            $notificationId
        );

        if (! $notification) {
            return $this->notificationNotFound();
        }

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديد الإشعار كمقروء.',
            'data' => $this->notificationData(
                $notification->fresh()
            ),
        ]);
    }

    /**
     * تحديد جميع الإشعارات كمقروءة.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $updatedCount = $request->user()
            ->unreadNotifications()
            ->update([
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديد جميع الإشعارات كمقروءة.',
            'data' => [
                'updated_count' => $updatedCount,
                'unread_count' => 0,
            ],
        ]);
    }

    /**
     * حذف إشعار واحد.
     */
    public function destroy(
        Request $request,
        string $notificationId,
    ): JsonResponse {
        $notification = $this->findNotification(
            $request,
            $notificationId
        );

        if (! $notification) {
            return $this->notificationNotFound();
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الإشعار بنجاح.',
        ]);
    }

    /**
     * حذف جميع إشعارات المستخدم.
     */
    public function clear(Request $request): JsonResponse
    {
        $deletedCount = $request->user()
            ->notifications()
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف جميع الإشعارات.',
            'data' => [
                'deleted_count' => $deletedCount,
            ],
        ]);
    }

    /**
     * جلب إشعار يخص المستخدم المسجل فقط.
     */
    private function findNotification(
        Request $request,
        string $notificationId,
    ): ?DatabaseNotification {
        return $request->user()
            ->notifications()
            ->whereKey($notificationId)
            ->first();
    }

    /**
     * استجابة الإشعار غير الموجود.
     */
    private function notificationNotFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'الإشعار غير موجود.',
        ], 404);
    }

    /**
     * تنسيق بيانات الإشعار للـ API.
     */
    private function notificationData(
        DatabaseNotification $notification,
    ): array {
        $data = is_array($notification->data)
            ? $notification->data
            : [];

        return [
            'id' => $notification->id,
            'type' => $data['type']
                ?? class_basename($notification->type),
            'title' => $data['title']
                ?? 'إشعار من غنم الوادي',
            'message' => $data['message'] ?? null,
            'icon' => $data['icon'] ?? null,
            'action' => $data['action'] ?? null,
            'data' => $data,
            'is_read' => $notification->read_at !== null,
            'read_at' => $notification->read_at
                ?->toIso8601String(),
            'created_at' => $notification->created_at
                ?->toIso8601String(),
        ];
    }
}