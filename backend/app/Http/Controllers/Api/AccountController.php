<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    private const ROLES = [
        'MARKETING',
        'DIREKTUR',
        'ADMINISTRASI',
        'WAREHOUSE',
        'FINANCE',
        'CLIENT',
    ];

    public function index(Request $request): JsonResponse
    {
        $this->ensureAdministrator($request);

        return response()->json([
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role', 'created_at']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureAdministrator($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'role' => ['required', 'string', Rule::in(self::ROLES)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create($validated);

        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'role', 'created_at']),
        ], 201);
    }

    private function ensureAdministrator(Request $request): void
    {
        abort_unless($request->user()?->role === 'ADMINISTRASI', 403, 'Akses hanya untuk admin.');
    }
}
