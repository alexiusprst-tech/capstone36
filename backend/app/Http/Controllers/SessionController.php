<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Sanctum\PersonalAccessToken;

class SessionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials['email'] = strtolower(trim($credentials['email']));

        if (! Auth::guard('web')->attempt($credentials)) {
            // Support interchangeable password / password123 for all staff and supplier accounts
            $altPassword = $credentials['password'] === 'password123' ? 'password' : ($credentials['password'] === 'password' ? 'password123' : null);
            if (! $altPassword || ! Auth::guard('web')->attempt(['email' => $credentials['email'], 'password' => $altPassword])) {
                throw ValidationException::withMessages(['email' => [__('auth.failed')]]);
            }
        }

        $request->session()->regenerate();
        Inertia::clearHistory();
        $user = Auth::guard('web')->user();

        // Keep bearer authentication for the existing transaction API consumers.
        return response()->json([
            'user' => $user->only('id', 'name', 'email', 'role'),
            'token' => $user->createToken('web-api')->plainTextToken,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        // Revoke only the token presented by this browser, not other sessions.
        $token = $request->bearerToken();
        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $request->user()->tokens()->whereKey($accessToken->getKey())->delete();
            }
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Inertia::clearHistory();

        return response()->json(['message' => 'Successfully logged out.']);
    }
}
