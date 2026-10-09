<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsCourier
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->hasRole(Role::COURIER), 403, 'هذه الصفحة مخصصة لمناديب التوصيل.');

        return $next($request);
    }
}
