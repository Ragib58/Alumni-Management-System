<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * In-app (database) notifications for the authenticated user.
 */
class NotificationController extends Controller
{
    /**
     * GET /api/v1/notifications
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = (int) $request->integer('per_page', 15);

        $query = $request->boolean('unread')
            ? $user->unreadNotifications()
            : $user->notifications();

        $paginator = $query->paginate($perPage);

        return $this->success(
            NotificationResource::collection($paginator),
            'Notifications retrieved.',
            200,
            ['unread_count' => $user->unreadNotifications()->count()]
        );
    }

    /**
     * GET /api/v1/notifications/unread-count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return $this->success(
            ['unread_count' => $request->user()->unreadNotifications()->count()],
            'Unread count retrieved.'
        );
    }

    /**
     * PATCH /api/v1/notifications/{id}/read
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return $this->success(new NotificationResource($notification->fresh()), 'Notification marked as read.');
    }

    /**
     * PATCH /api/v1/notifications/read-all
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $this->success(null, 'All notifications marked as read.');
    }

    /**
     * DELETE /api/v1/notifications/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->where('id', $id)->delete();

        return $this->success(null, 'Notification deleted.');
    }
}
