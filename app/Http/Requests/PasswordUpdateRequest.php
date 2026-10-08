<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class PasswordUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'كلمة المرور الحالية مطلوبة.',
            'current_password.current_password' => 'كلمة المرور الحالية غير صحيحة.',
            'password.required' => 'كلمة المرور الجديدة مطلوبة.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
            'password.different' => 'يجب أن تختلف كلمة المرور الجديدة عن الحالية.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
            'password.letters' => 'كلمة المرور يجب أن تحتوي على أحرف.',
            'password.numbers' => 'كلمة المرور يجب أن تحتوي على أرقام.',
        ];
    }
}
