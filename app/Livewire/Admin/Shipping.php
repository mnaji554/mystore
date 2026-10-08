<?php

namespace App\Livewire\Admin;

use App\Models\ShippingCompany;
use App\Models\ShippingMethod;
use Livewire\Component;

class Shipping extends Component
{
    public string $tab = 'methods';

    // Method form
    public bool $showMethodForm = false;

    public ?int $methodId = null;

    public array $method = [];

    // Company form
    public bool $showCompanyForm = false;

    public ?int $companyId = null;

    public array $company = [];

    public function mount(): void
    {
        $this->authorize('manage-shipping');
        $this->resetMethod();
        $this->resetCompany();
    }

    public function resetMethod(): void
    {
        $this->method = [
            'name' => '', 'description' => '', 'shipping_company_id' => '', 'price' => '0', 'is_free' => false,
            'free_shipping_threshold' => '', 'delivery_days_min' => 1, 'delivery_days_max' => 3, 'sort_order' => 0, 'is_active' => true,
        ];
        $this->methodId = null;
        $this->showMethodForm = false;
        $this->resetValidation();
    }

    public function resetCompany(): void
    {
        $this->company = ['name' => '', 'tracking_url_template' => '', 'is_active' => true];
        $this->companyId = null;
        $this->showCompanyForm = false;
        $this->resetValidation();
    }

    public function newMethod(): void
    {
        $this->authorize('manage-shipping');
        $this->resetMethod();
        $this->showMethodForm = true;
    }

    public function editMethod(int $id): void
    {
        $this->authorize('manage-shipping');
        $m = ShippingMethod::findOrFail($id);
        $this->resetMethod();
        $this->methodId = $id;
        $this->method = [
            'name' => $m->name, 'description' => (string) $m->description, 'shipping_company_id' => (string) ($m->shipping_company_id ?? ''),
            'price' => (string) (float) $m->price, 'is_free' => $m->is_free,
            'free_shipping_threshold' => $m->free_shipping_threshold !== null ? (string) (float) $m->free_shipping_threshold : '',
            'delivery_days_min' => $m->delivery_days_min, 'delivery_days_max' => $m->delivery_days_max,
            'sort_order' => $m->sort_order, 'is_active' => $m->is_active,
        ];
        $this->showMethodForm = true;
    }

    public function saveMethod(): void
    {
        $this->authorize('manage-shipping');

        $this->validate([
            'method.name' => ['required', 'string', 'max:120'],
            'method.description' => ['nullable', 'string', 'max:255'],
            'method.shipping_company_id' => ['nullable', 'integer', 'exists:shipping_companies,id'],
            'method.price' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'method.is_free' => ['boolean'],
            'method.free_shipping_threshold' => ['nullable', 'numeric', 'min:0'],
            'method.delivery_days_min' => ['required', 'integer', 'min:0', 'max:365'],
            'method.delivery_days_max' => ['required', 'integer', 'gte:method.delivery_days_min', 'max:365'],
            'method.sort_order' => ['required', 'integer', 'min:0'],
            'method.is_active' => ['boolean'],
        ], ['required' => 'هذا الحقل مطلوب.', 'numeric' => 'أدخل رقماً.', 'gte' => 'يجب ألا تقل عن الحد الأدنى.', 'integer' => 'أدخل رقماً صحيحاً.']);

        $m = $this->method;
        $data = [
            'name' => strip_tags($m['name']), 'description' => $m['description'] ?: null,
            'shipping_company_id' => $m['shipping_company_id'] !== '' ? (int) $m['shipping_company_id'] : null,
            'price' => $m['price'], 'is_free' => (bool) $m['is_free'],
            'free_shipping_threshold' => $m['free_shipping_threshold'] !== '' ? $m['free_shipping_threshold'] : null,
            'delivery_days_min' => (int) $m['delivery_days_min'], 'delivery_days_max' => (int) $m['delivery_days_max'],
            'sort_order' => (int) $m['sort_order'], 'is_active' => (bool) $m['is_active'],
        ];

        ($this->methodId ? ShippingMethod::findOrFail($this->methodId) : new ShippingMethod)->fill($data)->save();

        $this->resetMethod();
        $this->dispatch('notify', message: 'تم حفظ طريقة الشحن', type: 'success');
    }

    public function deleteMethod(int $id): void
    {
        $this->authorize('manage-shipping');
        $this->authorize('delete-records');
        ShippingMethod::findOrFail($id)->delete();
        $this->dispatch('notify', message: 'تم حذف طريقة الشحن', type: 'success');
    }

    public function editCompany(int $id): void
    {
        $this->authorize('manage-shipping');
        $c = ShippingCompany::findOrFail($id);
        $this->companyId = $id;
        $this->company = ['name' => $c->name, 'tracking_url_template' => (string) $c->tracking_url_template, 'is_active' => $c->is_active];
        $this->showCompanyForm = true;
    }

    public function newCompany(): void
    {
        $this->authorize('manage-shipping');
        $this->resetCompany();
        $this->showCompanyForm = true;
    }

    public function saveCompany(): void
    {
        $this->authorize('manage-shipping');

        $this->validate([
            'company.name' => ['required', 'string', 'max:120'],
            'company.tracking_url_template' => ['nullable', 'string', 'max:300', 'starts_with:http://,https://'],
            'company.is_active' => ['boolean'],
        ], ['required' => 'هذا الحقل مطلوب.', 'starts_with' => 'يجب أن يبدأ الرابط بـ http:// أو https://']);

        ($this->companyId ? ShippingCompany::findOrFail($this->companyId) : new ShippingCompany)->fill([
            'name' => strip_tags($this->company['name']),
            'tracking_url_template' => $this->company['tracking_url_template'] ?: null,
            'is_active' => (bool) $this->company['is_active'],
        ])->save();

        $this->resetCompany();
        $this->dispatch('notify', message: 'تم حفظ شركة الشحن', type: 'success');
    }

    public function deleteCompany(int $id): void
    {
        $this->authorize('manage-shipping');
        $this->authorize('delete-records');
        ShippingCompany::findOrFail($id)->delete();
        $this->dispatch('notify', message: 'تم حذف شركة الشحن', type: 'success');
    }

    public function render()
    {
        return view('livewire.admin.shipping', [
            'methods' => ShippingMethod::with('company')->orderBy('sort_order')->get(),
            'companies' => ShippingCompany::withCount('methods')->get(),
        ])->layout('components.layouts.admin', ['title' => 'الشحن']);
    }
}
