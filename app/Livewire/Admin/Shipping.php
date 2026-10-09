<?php

namespace App\Livewire\Admin;

use App\Models\Role;
use App\Models\ShippingCompany;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Shipping extends Component
{
    use WithFileUploads;

    public string $tab = 'methods';

    // Method form
    public bool $showMethodForm = false;

    public ?int $methodId = null;

    public array $method = [];

    // Company form
    public bool $showCompanyForm = false;

    public ?int $companyId = null;

    public array $company = [];

    // Courier account form
    public bool $showCourierForm = false;

    public ?int $courierId = null;

    public array $courier = [];

    public ?TemporaryUploadedFile $nationalIdImage = null;

    public ?TemporaryUploadedFile $vehicleRegistrationImage = null;

    public ?TemporaryUploadedFile $drivingLicenseImage = null;

    /** @var array<string, bool> */
    public array $courierDocumentExists = [];

    public function mount(): void
    {
        $this->authorize('manage-shipping');
        $this->resetMethod();
        $this->resetCompany();
        $this->resetCourier();
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

    public function resetCourier(): void
    {
        $this->courier = [
            'name' => '', 'email' => '', 'phone' => '', 'password' => '', 'password_confirmation' => '',
            'is_active' => true, 'national_id' => '', 'vehicle_plate' => '', 'driving_license_number' => '',
        ];
        $this->courierId = null;
        $this->nationalIdImage = null;
        $this->vehicleRegistrationImage = null;
        $this->drivingLicenseImage = null;
        $this->courierDocumentExists = [];
        $this->showCourierForm = false;
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

    public function newCourier(): void
    {
        $this->authorize('manage-shipping');
        $this->resetCourier();
        $this->showCourierForm = true;
    }

    public function editCourier(int $id): void
    {
        $this->authorize('manage-shipping');
        $user = $this->findCourier($id);
        $this->courierId = $user->id;
        $this->courier = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => (string) $user->phone,
            'password' => '',
            'password_confirmation' => '',
            'is_active' => $user->is_active,
            'national_id' => (string) ($user->courierProfile?->national_id ?? ''),
            'vehicle_plate' => (string) ($user->courierProfile?->vehicle_plate ?? ''),
            'driving_license_number' => (string) ($user->courierProfile?->driving_license_number ?? ''),
        ];
        $profile = $user->courierProfile;
        $this->courierDocumentExists = [
            'national-id' => filled($profile?->national_id_image_path),
            'vehicle-registration' => filled($profile?->vehicle_registration_image_path),
            'driving-license' => filled($profile?->driving_license_image_path),
        ];
        $this->showCourierForm = true;
    }

    public function saveCourier(): void
    {
        $this->authorize('manage-shipping');

        $this->validate([
            'courier.name' => ['required', 'string', 'max:120'],
            'courier.email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($this->courierId)],
            'courier.phone' => ['nullable', 'string', 'regex:/^[0-9+\\-\\s()]{7,20}$/'],
            'courier.password' => [$this->courierId ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'courier.is_active' => ['boolean'],
            'courier.national_id' => ['nullable', 'digits:10'],
            'courier.vehicle_plate' => ['nullable', 'string', 'max:20'],
            'courier.driving_license_number' => ['nullable', 'string', 'max:30'],
            'nationalIdImage' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'vehicleRegistrationImage' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'drivingLicenseImage' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'required' => 'هذا الحقل مطلوب.',
            'email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'courier.password.min' => 'يجب ألا تقل كلمة المرور عن 8 أحرف.',
            'courier.password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
            'unique' => 'البريد الإلكتروني مستخدم بالفعل.',
            'regex' => 'صيغة رقم الجوال غير صحيحة.',
            'courier.national_id.digits' => 'رقم الهوية يجب أن يتكون من 10 أرقام.',
            'image' => 'يجب رفع صورة صالحة.',
            'mimes' => 'الصيغ المسموحة: JPG أو PNG أو WEBP.',
            'max' => 'يجب ألا يتجاوز حجم الصورة 5 ميجابايت.',
        ]);

        $storedPaths = [];
        $replacedPaths = [];

        try {
            DB::transaction(function () use (&$storedPaths, &$replacedPaths): void {
                $user = $this->courierId ? $this->findCourier($this->courierId) : new User;
                $user->name = strip_tags(trim($this->courier['name']));
                $user->email = strtolower(trim($this->courier['email']));
                $user->phone = $this->courier['phone'] ?: null;
                $user->is_active = (bool) $this->courier['is_active'];

                if ($this->courier['password'] !== '') {
                    $user->password = $this->courier['password'];
                }

                if (! $this->courierId) {
                    $role = Role::query()->firstOrCreate(
                        ['slug' => Role::COURIER],
                        [
                            'name' => Permissions::ROLES[Role::COURIER]['name'],
                            'permissions' => Permissions::ROLES[Role::COURIER]['permissions'],
                        ],
                    );
                    $user->role()->associate($role);
                }

                $user->save();
                $profile = $user->courierProfile()->firstOrNew();
                $profile->national_id = $this->courier['national_id'] ?: null;
                $profile->vehicle_plate = $this->courier['vehicle_plate'] ?: null;
                $profile->driving_license_number = $this->courier['driving_license_number'] ?: null;

                foreach ($this->courierDocumentUploads() as $column => $upload) {
                    if (! $upload) {
                        continue;
                    }

                    $replacedPaths[] = $profile->{$column};
                    $path = $upload->storeAs(
                        "courier-documents/{$user->id}",
                        Str::random(40).'.'.$upload->extension(),
                        'private_documents',
                    );

                    if (! $path) {
                        throw new \RuntimeException('تعذر حفظ صورة وثائق المندوب.');
                    }

                    $storedPaths[] = $path;
                    $profile->{$column} = $path;
                }

                $profile->save();
            });
        } catch (\Throwable $exception) {
            if ($storedPaths) {
                Storage::disk('private_documents')->delete($storedPaths);
            }

            report($exception);
            $this->dispatch('notify', message: 'تعذر حفظ بيانات المندوب أو صوره. حاول مرة أخرى.', type: 'error');

            return;
        }

        if ($replacedPaths) {
            Storage::disk('private_documents')->delete(array_filter($replacedPaths));
        }

        $this->resetCourier();
        $this->dispatch('notify', message: 'تم حفظ حساب المندوب', type: 'success');
    }

    /** @return array<string, ?TemporaryUploadedFile> */
    private function courierDocumentUploads(): array
    {
        return [
            'national_id_image_path' => $this->nationalIdImage,
            'vehicle_registration_image_path' => $this->vehicleRegistrationImage,
            'driving_license_image_path' => $this->drivingLicenseImage,
        ];
    }

    public function removeCourierDocument(string $document): void
    {
        $this->authorize('manage-shipping');
        abort_unless($this->courierId, 404);

        $column = match ($document) {
            'national-id' => 'national_id_image_path',
            'vehicle-registration' => 'vehicle_registration_image_path',
            'driving-license' => 'driving_license_image_path',
            default => abort(404),
        };

        $profile = $this->findCourier($this->courierId)->courierProfile;
        abort_unless($profile, 404);

        $path = $profile->{$column};
        $profile->{$column} = null;
        $profile->save();

        if ($path) {
            Storage::disk('private_documents')->delete($path);
        }

        $this->courierDocumentExists[$document] = false;
        $this->dispatch('notify', message: 'تم حذف صورة الوثيقة.', type: 'success');
    }

    public function toggleCourier(int $id): void
    {
        $this->authorize('manage-shipping');
        $courier = $this->findCourier($id);
        $courier->is_active = ! $courier->is_active;
        $courier->save();
        $this->dispatch('notify', message: $courier->is_active ? 'تم تفعيل حساب المندوب' : 'تم إيقاف حساب المندوب', type: 'success');
    }

    private function findCourier(int $id): User
    {
        return User::query()->whereHas('role', fn ($query) => $query->where('slug', Role::COURIER))
            ->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.admin.shipping', [
            'methods' => ShippingMethod::with('company')->orderBy('sort_order')->get(),
            'companies' => ShippingCompany::withCount('methods')->get(),
            'couriers' => User::query()->whereHas('role', fn ($query) => $query->where('slug', Role::COURIER))
                ->orderBy('name')->get(),
        ])->layout('components.layouts.admin', ['title' => 'الشحن']);
    }
}
