<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use App\Models\DeviceToken;
use App\Services\Auth\CredentialAuthenticator;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request, CredentialAuthenticator $authenticator)
    {
        $data = $request->validate([
            'username' => 'required|string|max:100',
            'password' => 'required|string|max:255',
            'device_name' => 'nullable|string|max:100',
        ]);

        try {
            $result = $authenticator->attempt($data['username'], $data['password']);
        } catch (\Exception $e) {
            report($e);
            $result = null;
        }

        if (!$result) {
            throw ValidationException::withMessages([
                'username' => ['اسم المستخدم أو كلمة المرور غير صحيحة.'],
            ]);
        }

        $user = $result['user'];
        $token = $user->createToken($data['device_name'] ?? 'mobile-app', ['mobile']);

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => new UserResource($user->load('role')),
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        DeviceToken::where('personal_access_token_id', $token->id)->delete();
        $token->delete();

        return response()->json(['status' => 'logged_out']);
    }

    public function me(Request $request)
    {
        return new UserResource($request->user()->load('role'));
    }
}
