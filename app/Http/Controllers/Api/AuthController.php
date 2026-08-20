<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsappOtp;
use App\Services\WhatsAppOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $throttleKey = Str::lower((string) $request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'message' => 'Too many login attempts. Please try again after a minute.',
            ], 429);
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::withoutGlobalScopes()
            ->select(['id', 'name', 'email', 'password', 'status', 'image'])
            ->where('email', $validated['email'])
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password) || $user->status !== 'active') {
            RateLimiter::hit($throttleKey, 60);

            return response()->json([
                'message' => 'Invalid email or password.',
            ], 422);
        }

        RateLimiter::clear($throttleKey);

        $user->load(['roles:id,name', 'employeeDetail:user_id,id']);

        $token = $user->createToken($validated['device_name'] ?? 'flutter_crm_app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'image_url' => $user->image_url,
                'roles' => $user->roles->pluck('name')->values(),
            ],
            'permissions' => [
                'add_lead' => $user->permission('add_lead'),
                'add_tasks' => $user->permission('add_tasks'),
            ],
            'employee_id' => $user->employeeDetail?->id ?? $user->id,
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'image_url' => $user->image_url,
                'roles' => $user->roles->pluck('name')->values(),
            ],
            'permissions' => [
                'add_lead' => $user->permission('add_lead'),
                'add_tasks' => $user->permission('add_tasks'),
            ],
            'employee_id' => $user->employeeDetail?->id ?? $user->id,
        ]);
    }

}
