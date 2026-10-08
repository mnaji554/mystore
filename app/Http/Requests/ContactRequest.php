<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            'website' => ['nullable', 'max:0'], // honeypot
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'phone.regex' => 'رقم الجوال غير صحيح.',
            'subject.required' => 'عنوان الرسالة مطلوب.',
            'message.required' => 'نص الرسالة مطلوب.',
            'message.min' => 'الرسالة قصيرة جداً.',
        ];
    }
}
