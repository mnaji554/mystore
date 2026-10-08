<?php

namespace App\Livewire\Account;

use App\Models\Address;
use Livewire\Component;

class Addresses extends Component
{
    public ?int $editingId = null;

    public bool $showForm = false;

    public string $label = '';

    public string $full_name = '';

    public string $phone = '';

    public string $country = 'المملكة العربية السعودية';

    public string $city = '';

    public string $district = '';

    public string $street = '';

    public string $building = '';

    public string $postal_code = '';

    public bool $is_default = false;

    protected function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:60'],
            'full_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'country' => ['required', 'string', 'max:80'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'street' => ['required', 'string', 'max:190'],
            'building' => ['nullable', 'string', 'max:60'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return ['required' => 'هذا الحقل مطلوب.', 'regex' => 'صيغة غير صحيحة.', 'max' => 'القيمة طويلة جداً.'];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->full_name = auth()->user()->name;
        $this->phone = (string) auth()->user()->phone;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $address = Address::findOrFail($id);
        $this->authorize('update', $address);

        $this->fill($address->only(['label', 'full_name', 'phone', 'country', 'city', 'district', 'street', 'building', 'postal_code', 'is_default']));
        $this->label = (string) $address->label;
        $this->district = (string) $address->district;
        $this->building = (string) $address->building;
        $this->postal_code = (string) $address->postal_code;
        $this->editingId = $id;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate();
        $user = auth()->user();

        if ($this->editingId) {
            $address = Address::findOrFail($this->editingId);
            $this->authorize('update', $address);
        } else {
            $address = new Address(['user_id' => $user->id]);
        }

        if ($user->addresses()->doesntExist()) {
            $data['is_default'] = true;
        }

        $address->fill($data)->save();

        if ($address->is_default) {
            $user->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
        }

        $this->resetForm();
        $this->dispatch('notify', message: 'تم حفظ العنوان', type: 'success');
    }

    public function makeDefault(int $id): void
    {
        $address = Address::findOrFail($id);
        $this->authorize('update', $address);

        auth()->user()->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);
    }

    public function delete(int $id): void
    {
        $address = Address::findOrFail($id);
        $this->authorize('delete', $address);
        $address->delete();

        if ($address->is_default && ($next = auth()->user()->addresses()->first())) {
            $next->update(['is_default' => true]);
        }

        $this->dispatch('notify', message: 'تم حذف العنوان', type: 'success');
    }

    public function resetForm(): void
    {
        $this->reset('editingId', 'showForm', 'label', 'full_name', 'phone', 'city', 'district', 'street', 'building', 'postal_code', 'is_default');
        $this->country = 'المملكة العربية السعودية';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.account.addresses', [
            'addresses' => auth()->user()->addresses()->orderByDesc('is_default')->latest('id')->get(),
        ])->layout('components.layouts.app', ['title' => 'عناويني', 'noindex' => true]);
    }
}
