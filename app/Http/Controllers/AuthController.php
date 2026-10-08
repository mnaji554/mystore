<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = ['email' => strtolower($request->input('email')), 'password' => $request->input('password'), 'is_active' => true];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'بيانات الدخول غير صحيحة أو الحساب معطّل.']);
        }

        $request->session()->regenerate();

        return redirect()->intended($request->user()->isStaff() ? route('admin.dashboard') : route('account.dashboard'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $role = Role::firstOrCreate(['slug' => Role::CUSTOMER], ['name' => 'عميل', 'permissions' => []]);

        $user = new User($request->safe()->only(['name', 'email', 'phone', 'password']));
        $user->role()->associate($role);
        $user->save();

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account.dashboard')->with('success', 'تم إنشاء حسابك بنجاح. أهلاً بك!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
