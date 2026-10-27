<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'data' => $user->notifications()->latest()->limit(50)->get()->map(fn ($n) => [
                'id' => $n->id,
                'message' => $n->data['message'] ?? '',
                'ticket_id' => $n->data['ticket_id'] ?? null,
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['unread' => 0]);
    }

    /** Register this phone's Expo push token. */
    public function registerDevice(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255', 'regex:/^(ExponentPushToken|ExpoPushToken)\[.+\]$/'],
            'platform' => 'nullable|in:android,ios',
            'device_name' => 'nullable|string|max:100',
        ]);

        DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $data['platform'] ?? null,
                'device_name' => $data['device_name'] ?? null,
                'personal_access_token_id' => $request->user()->currentAccessToken()?->id,
                'last_used_at' => now(),
            ]
        );

        return response()->json(['status' => 'registered'], 201);
    }

    public function unregisterDevice(Request $request)
    {
        $data = $request->validate(['token' => 'required|string|max:255']);

        DeviceToken::where('token', $data['token'])->where('user_id', $request->user()->id)->delete();

        return response()->json(['status' => 'unregistered']);
    }
}
