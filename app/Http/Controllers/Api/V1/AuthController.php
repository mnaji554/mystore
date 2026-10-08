<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $role = Role::firstOrCreate(['slug' => Role::CUSTOMER], ['name' => 'عميل', 'permissions' => []]);

        $user = new User($request->safe()->only(['name', 'email', 'phone', 'password']));
        $user->role()->associate($role);
        $user->save();

        event(new Registered($user));

        return response()->json([
            'user' => new UserResource($user),
            'token' => $user->createToken($request->input('device', 'api'))->plainTextToken,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', strtolower($request->input('email')))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password) || ! $user->is_active) {
            throw ValidationException::withMessages(['email' => 'بيانات الدخول غير صحيحة أو الحساب معطّل.']);
        }

        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return response()->json([
            'user' => new UserResource($user->load('role')),
            'token' => $user->createToken($request->input('device', 'api'))->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'تم تسجيل الخروج.']);
    }
}
