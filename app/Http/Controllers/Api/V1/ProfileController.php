<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user()->load('role'));
    }

    public function update(ProfileUpdateRequest $request): UserResource
    {
        $request->user()->update($request->validated());

        return new UserResource($request->user()->fresh('role'));
    }

    public function password(PasswordUpdateRequest $request): JsonResponse
    {
        $request->user()->update(['password' => $request->validated('password')]);

        return response()->json(['message' => 'تم تغيير كلمة المرور.']);
    }
}
