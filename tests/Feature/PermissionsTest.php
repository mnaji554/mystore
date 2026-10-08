<?php

namespace Tests\Feature;

use App\Livewire\Account\Addresses;
use App\Livewire\Admin;
use App\Models\Address;
use App\Models\Coupon;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    public function test_guests_are_sent_to_login_and_customers_are_forbidden_from_admin(): void
    {
        foreach (['/admin', '/admin/products', '/admin/orders', '/admin/settings', '/admin/customers'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }

        $this->actingAs($this->customer());
        foreach (['/admin', '/admin/products', '/admin/orders', '/admin/settings', '/admin/customers', '/admin/categories'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_every_role_has_the_expected_permissions(): void
    {
        $matrix = [
            'super_admin' => Permissions::keys(),
            'admin' => array_diff(Permissions::keys(), ['manage-staff']),
            'manager' => ['access-admin', 'manage-products', 'manage-categories', 'manage-orders', 'manage-reviews'],
            'customer' => [],
        ];

        foreach ($matrix as $role => $allowed) {
            $user = $this->staff($role === 'customer' ? 'admin' : $role);

            if ($role === 'customer') {
                $user = $this->customer();
            }

            foreach (Permissions::keys() as $permission) {
                $this->assertSame(in_array($permission, $allowed, true), Gate::forUser($user)->allows($permission), "{$role} / {$permission}");
            }
        }
    }

    public function test_manager_can_manage_catalog_and_orders_but_not_settings_customers_or_coupons(): void
    {
        $this->actingAs($this->staff('manager'));

        $this->get('/admin')->assertOk();
        $this->get('/admin/products')->assertOk();
        $this->get('/admin/categories')->assertOk();
        $this->get('/admin/orders')->assertOk();
        $this->get('/admin/reviews')->assertOk();
        $this->get('/admin/settings')->assertForbidden();
        $this->get('/admin/customers')->assertForbidden();
        $this->get('/admin/coupons')->assertForbidden();
        $this->get('/admin/shipping')->assertForbidden();
    }

    public function test_admin_and_super_admin_can_open_every_admin_page(): void
    {
        $customer = $this->customer();

        foreach (['admin', 'super_admin'] as $role) {
            $this->actingAs($this->staff($role));

            foreach (['/admin', '/admin/products', '/admin/products/create', '/admin/categories', '/admin/orders', '/admin/customers',
                '/admin/customers/'.$customer->id, '/admin/coupons', '/admin/shipping', '/admin/reviews', '/admin/messages', '/admin/settings', '/admin/notifications'] as $url) {
                $this->get($url)->assertOk();
            }
        }
    }

    public function test_livewire_admin_actions_are_authorized_not_just_routes(): void
    {
        $manager = $this->staff('manager');
        $product = $this->product();

        // a manager may edit products but not delete them (needs delete-records)
        Livewire::actingAs($manager)->test(Admin\ProductIndex::class)->call('delete', $product->id)->assertForbidden();
        $this->assertNotNull($product->fresh());

        // a customer cannot even mount admin components or call actions
        Livewire::actingAs($this->customer())->test(Admin\ProductIndex::class)->assertForbidden();
        Livewire::actingAs($this->customer())->test(Admin\Settings::class)->assertForbidden();
        Livewire::actingAs($manager)->test(Admin\Settings::class)->assertForbidden();

        Livewire::actingAs($this->staff('admin'))->test(Admin\ProductIndex::class)->call('delete', $product->id);
        $this->assertSoftDeleted($product);
    }

    public function test_only_super_admin_can_assign_roles_and_nobody_can_demote_themselves(): void
    {
        $admin = $this->staff('admin');
        $super = $this->staff('super_admin');
        $target = $this->customer();

        $this->assertFalse(Gate::forUser($admin)->allows('assignRole', $target));
        $this->assertTrue(Gate::forUser($super)->allows('assignRole', $target));
        $this->assertFalse(Gate::forUser($super)->allows('assignRole', $super));

        Livewire::actingAs($admin)->test(Admin\CustomerShow::class, ['user' => $target])
            ->set('roleSlug', Role::ADMIN)->call('save');
        $this->assertSame(Role::CUSTOMER, $target->fresh()->role->slug);

        Livewire::actingAs($super)->test(Admin\CustomerShow::class, ['user' => $target])
            ->set('roleSlug', Role::MANAGER)->call('save');
        $this->assertSame(Role::MANAGER, $target->fresh()->role->slug);
    }

    public function test_admin_cannot_modify_or_delete_a_super_admin_and_cannot_delete_self(): void
    {
        $admin = $this->staff('admin');
        $super = $this->staff('super_admin');

        $this->assertFalse(Gate::forUser($admin)->allows('update', $super));
        $this->assertFalse(Gate::forUser($admin)->allows('delete', $super));
        $this->assertFalse(Gate::forUser($admin)->allows('delete', $admin));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $this->customer()));
    }

    public function test_addresses_belong_to_their_owner(): void
    {
        $owner = $this->customer();
        $other = $this->customer();
        $address = Address::create(['user_id' => $owner->id, 'full_name' => 'x', 'phone' => '0500000000', 'city' => 'c', 'street' => 's']);

        $this->assertTrue(Gate::forUser($owner)->allows('update', $address));
        $this->assertFalse(Gate::forUser($other)->allows('update', $address));
        $this->assertFalse(Gate::forUser($other)->allows('delete', $address));

        Livewire::actingAs($other)->test(Addresses::class)->call('delete', $address->id)->assertForbidden();
        $this->assertNotNull($address->fresh());
    }

    public function test_disabled_staff_cannot_use_admin(): void
    {
        $admin = $this->staff('admin');
        $admin->forceFill(['is_active' => false])->save();

        $this->actingAs($admin)->get('/admin')->assertRedirect(route('login'));
    }

    public function test_coupon_policy_requires_manage_coupons(): void
    {
        $coupon = Coupon::factory()->create();

        $this->assertFalse(Gate::forUser($this->staff('manager'))->allows('update', $coupon));
        $this->assertTrue(Gate::forUser($this->staff('admin'))->allows('update', $coupon));
    }
}
