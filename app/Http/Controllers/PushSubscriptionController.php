<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'endpoint'        => 'required|url|max:1024',
            'keys.p256dh'     => 'required|string|max:255',
            'keys.auth'       => 'required|string|max:255',
            'contentEncoding' => 'nullable|string|in:aesgcm,aes128gcm',
        ]);

        $request->user()->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
            $data['contentEncoding'] ?? 'aesgcm'
        );

        return response()->json(['status' => 'subscribed']);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['endpoint' => 'required|url|max:1024']);

        $request->user()->deletePushSubscription($data['endpoint']);

        return response()->json(['status' => 'unsubscribed']);
    }
}
