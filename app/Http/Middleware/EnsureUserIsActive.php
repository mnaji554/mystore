<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Logs out accounts that an admin has disabled. */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            if (! $request->hasSession()) {
                return response()->json(['message' => 'تم تعطيل هذا الحساب.'], 403);
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'تم تعطيل هذا الحساب. يرجى التواصل مع الدعم.']);
        }

        return $next($request);
    }
}
