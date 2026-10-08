<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    public function request()
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']], [
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
        ]);

        Password::sendResetLink($request->only('email'));

        // Identical response either way: do not reveal which e-mails are registered.
        return back()->with('status', 'إذا كان البريد مسجلاً لدينا فقد أرسلنا رابط إعادة تعيين كلمة المرور.');
    }

    public function reset(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ], [
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
            'password.letters' => 'كلمة المرور يجب أن تحتوي على أحرف.',
            'password.numbers' => 'كلمة المرور يجب أن تحتوي على أرقام.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'تم تغيير كلمة المرور بنجاح. يمكنك تسجيل الدخول الآن.')
            : back()->withInput($request->only('email'))->withErrors(['email' => 'رابط إعادة التعيين غير صالح أو منتهي.']);
    }
}
