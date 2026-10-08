<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use App\Services\ImageService;
use Livewire\Component;
use Livewire\WithFileUploads;

class Settings extends Component
{
    use WithFileUploads;

    public array $form = [];

    public $logo = null;

    public function mount(): void
    {
        $this->authorize('manage-settings');

        foreach (['store_name', 'store_tagline', 'currency', 'currency_symbol', 'tax_enabled', 'tax_rate', 'low_stock_threshold',
            'contact_email', 'contact_phone', 'contact_address', 'vat_number', 'meta_description'] as $key) {
            $this->form[$key] = (string) Setting::get($key, '');
        }
        $this->form['tax_enabled'] = (bool) (int) $this->form['tax_enabled'];
    }

    public function save(ImageService $images): void
    {
        $this->authorize('manage-settings');

        $this->validate([
            'form.store_name' => ['required', 'string', 'max:100'],
            'form.store_tagline' => ['nullable', 'string', 'max:160'],
            'form.currency' => ['required', 'string', 'size:3', 'alpha'],
            'form.currency_symbol' => ['required', 'string', 'max:10'],
            'form.tax_enabled' => ['boolean'],
            'form.tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'form.low_stock_threshold' => ['required', 'integer', 'min:0', 'max:100000'],
            'form.contact_email' => ['nullable', 'email', 'max:190'],
            'form.contact_phone' => ['nullable', 'string', 'max:40'],
            'form.contact_address' => ['nullable', 'string', 'max:255'],
            'form.vat_number' => ['nullable', 'string', 'max:40'],
            'form.meta_description' => ['nullable', 'string', 'max:300'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ], [
            'required' => 'هذا الحقل مطلوب.', 'size' => 'رمز العملة 3 أحرف (مثل SAR).', 'alpha' => 'أحرف إنجليزية فقط.',
            'numeric' => 'أدخل رقماً.', 'max' => 'القيمة كبيرة جداً.', 'email' => 'بريد غير صحيح.', 'image' => 'يجب أن يكون الملف صورة.',
        ]);

        foreach ($this->form as $key => $value) {
            Setting::put($key, $key === 'tax_enabled' ? ($value ? '1' : '0') : strip_tags((string) $value));
        }
        Setting::put('currency', strtoupper($this->form['currency']));

        if ($this->logo) {
            $old = Setting::get('logo');
            $stored = $images->store($this->logo, 'branding', 600, 200);
            $images->delete($stored['thumb_path'], $old);
            Setting::put('logo', $stored['path']);
            $this->logo = null;
        }

        $this->dispatch('notify', message: 'تم حفظ الإعدادات', type: 'success');
    }

    public function removeLogo(ImageService $images): void
    {
        $this->authorize('manage-settings');
        $images->delete(Setting::get('logo'));
        Setting::put('logo', null);
    }

    public function render()
    {
        return view('livewire.admin.settings')->layout('components.layouts.admin', ['title' => 'إعدادات المتجر']);
    }
}
