<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\ListNotificationsRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function index(ListNotificationsRequest $request): JsonResponse
    {
        $notifications = $this->notificationService->paginate($request->user(), $request->validated());
        $payload = NotificationResource::collection($notifications)->response()->getData(true);

        return ApiResponse::success('Notifications retrieved', [
            'notifications' => $payload['data'],
            'links' => $payload['links'],
            'meta' => $payload['meta'],
        ]);
    }

    public function unreadCount(): JsonResponse
    {
        return ApiResponse::success('Unread notifications retrieved', [
            'unread_count' => $this->notificationService->unreadCount(request()->user()),
        ]);
    }

    public function markAsRead(Notification $notification): JsonResponse
    {
        $notification = $this->notificationService->markAsRead($notification, request()->user());

        return ApiResponse::success('Notification marked as read', [
            'notification' => new NotificationResource($notification),
        ]);
    }

    public function markAllAsRead(): JsonResponse
    {
        $updated = $this->notificationService->markAllAsRead(request()->user());

        return ApiResponse::success('Notifications marked as read', [
            'updated' => $updated,
        ]);
    }
}
