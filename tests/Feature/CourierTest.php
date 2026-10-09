<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Livewire\Admin;
use App\Livewire\Courier\Orders as CourierOrders;
use App\Models\Order;
use App\Models\Role;
use App\Models\ShippingCompany;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\ShippingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CourierTest extends TestCase
{
    private function courier(array $attributes = []): User
    {
        return User::factory()->courier()->create($attributes);
    }

    private function order(): Order
    {
        $customer = $this->customer();
        $cart = app(CartService::class)->resolve(true, $customer);
        app(CartService::class)->add($cart, $this->product(['price' => 100, 'stock' => 5])->id);

        return app(OrderService::class)->createFromCart(
            $cart,
            $this->checkoutData($this->shipping(['price' => 25])),
            $customer,
        );
    }

    public function test_admin_can_create_and_manage_courier_accounts(): void
    {
        $this->actingAs($this->staff('admin'));

        Livewire::test(Admin\Shipping::class)
            ->call('newCourier')
            ->set('courier.name', 'مندوب الرياض')
            ->set('courier.email', 'courier@example.com')
            ->set('courier.phone', '0501234567')
            ->set('courier.password', 'CourierPass123')
            ->set('courier.password_confirmation', 'CourierPass123')
            ->call('saveCourier')
            ->assertHasNoErrors();

        $courier = User::where('email', 'courier@example.com')->firstOrFail();
        $this->assertTrue($courier->hasRole(Role::COURIER));
        $this->assertTrue(Hash::check('CourierPass123', $courier->password));

        Livewire::test(Admin\Shipping::class)
            ->call('toggleCourier', $courier->id);
        $this->assertFalse($courier->fresh()->is_active);

        Livewire::test(Admin\Shipping::class)
            ->call('toggleCourier', $courier->id);
        $this->assertTrue($courier->fresh()->is_active);
    }

    public function test_admin_saves_encrypted_courier_identifiers_and_private_documents(): void
    {
        Storage::fake('private_documents');
        $this->actingAs($this->staff('admin'));

        Livewire::test(Admin\Shipping::class)
            ->call('newCourier')
            ->set('courier.name', 'مندوب الرياض')
            ->set('courier.email', 'courier-docs@example.com')
            ->set('courier.password', 'CourierPass123')
            ->set('courier.password_confirmation', 'CourierPass123')
            ->set('courier.national_id', '1234567890')
            ->set('courier.vehicle_plate', 'ABC 1234')
            ->set('courier.driving_license_number', 'DL-12345')
            ->set('nationalIdImage', UploadedFile::fake()->image('identity.png'))
            ->set('vehicleRegistrationImage', UploadedFile::fake()->image('registration.jpg'))
            ->set('drivingLicenseImage', UploadedFile::fake()->image('license.png'))
            ->call('saveCourier')
            ->assertHasNoErrors();

        $courier = User::where('email', 'courier-docs@example.com')->firstOrFail();
        $profile = $courier->courierProfile;
        $this->assertSame('1234567890', $profile->national_id);
        $this->assertSame('ABC 1234', $profile->vehicle_plate);
        $this->assertSame('DL-12345', $profile->driving_license_number);
        $this->assertNotSame('1234567890', DB::table('courier_profiles')->where('user_id', $courier->id)->value('national_id'));
        $this->assertArrayNotHasKey('national_id', $profile->toArray());

        foreach ([
            $profile->national_id_image_path,
            $profile->vehicle_registration_image_path,
            $profile->driving_license_image_path,
        ] as $path) {
            Storage::disk('private_documents')->assertExists($path);
            Storage::disk('public')->assertMissing($path);
        }

        $documentResponse = $this->get(route('admin.couriers.documents.download', ['user' => $courier, 'document' => 'national-id']));
        $documentResponse->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Disposition', 'attachment; filename=national-id.png');
        $this->assertStringContainsString('private', $documentResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $documentResponse->headers->get('Cache-Control'));

        $this->actingAs($this->staff('manager'))
            ->get(route('admin.couriers.documents.download', ['user' => $courier, 'document' => 'national-id']))
            ->assertForbidden();
    }

    public function test_courier_identity_and_documents_are_validated(): void
    {
        $this->actingAs($this->staff('admin'));

        Livewire::test(Admin\Shipping::class)
            ->call('newCourier')
            ->set('courier.name', 'مندوب')
            ->set('courier.email', 'invalid-docs@example.com')
            ->set('courier.password', 'CourierPass123')
            ->set('courier.password_confirmation', 'CourierPass123')
            ->set('courier.national_id', '12345')
            ->set('nationalIdImage', UploadedFile::fake()->create('identity.pdf', 20, 'application/pdf'))
            ->call('saveCourier')
            ->assertHasErrors(['courier.national_id', 'nationalIdImage']);

        $this->assertDatabaseMissing('users', ['email' => 'invalid-docs@example.com']);
    }

    public function test_courier_login_opens_the_delivery_dashboard(): void
    {
        $courier = $this->courier(['email' => 'courier-login@example.com', 'password' => 'CourierPass123']);

        $this->post(route('login'), ['email' => $courier->email, 'password' => 'CourierPass123'])
            ->assertRedirect(route('courier.orders'));

        $this->get(route('courier.orders'))->assertOk();
    }

    public function test_admin_can_assign_processing_order_and_courier_can_complete_delivery(): void
    {
        $order = $this->order();
        $courier = $this->courier();
        $orders = app(OrderService::class);
        $orders->changeStatus($order, OrderStatus::Confirmed);
        $orders->changeStatus($order->fresh(), OrderStatus::Processing);

        $this->actingAs($this->staff('admin'));
        Livewire::test(Admin\OrderShow::class, ['order' => $order->fresh()])
            ->set('courierId', (string) $courier->id)
            ->call('assignCourier')
            ->assertHasNoErrors();

        $order->refresh();
        $this->assertSame($courier->id, $order->courier_id);

        Livewire::actingAs($courier)->test(CourierOrders::class)
            ->assertSee($order->order_number)
            ->assertSee('رسوم التوصيل')
            ->call('advance', $order->id);

        $this->assertSame(OrderStatus::Shipped, $order->fresh()->status);

        Livewire::actingAs($courier)->test(CourierOrders::class)
            ->call('advance', $order->id);

        $order->refresh();
        $this->assertSame(OrderStatus::Delivered, $order->status);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertEquals(25, $order->shipping_cost);
    }

    public function test_admin_cannot_assign_an_inactive_or_non_courier_account(): void
    {
        $order = $this->order();
        $orders = app(OrderService::class);
        $orders->changeStatus($order, OrderStatus::Confirmed);
        $orders->changeStatus($order->fresh(), OrderStatus::Processing);
        $inactiveCourier = $this->courier(['is_active' => false]);
        $customer = $this->customer();

        $this->actingAs($this->staff('admin'));
        Livewire::test(Admin\OrderShow::class, ['order' => $order->fresh()])
            ->set('courierId', (string) $inactiveCourier->id)
            ->call('assignCourier')
            ->assertHasErrors('courierId');

        Livewire::test(Admin\OrderShow::class, ['order' => $order->fresh()])
            ->set('courierId', (string) $customer->id)
            ->call('assignCourier')
            ->assertHasErrors('courierId');

        $this->assertNull($order->fresh()->courier_id);
    }

    public function test_courier_only_sees_orders_assigned_to_their_account(): void
    {
        $assigned = $this->order();
        $other = $this->order();
        $courier = $this->courier();
        $assigned->update(['courier_id' => $courier->id, 'status' => OrderStatus::Shipped]);
        $other->update(['status' => OrderStatus::Shipped]);

        Livewire::actingAs($courier)->test(CourierOrders::class)
            ->assertSee($assigned->order_number)
            ->assertDontSee($other->order_number);
    }

    public function test_shipping_method_is_hidden_when_its_company_is_inactive(): void
    {
        $company = ShippingCompany::create(['name' => 'شركة تجريبية', 'is_active' => false]);
        $method = $this->shipping(['shipping_company_id' => $company->id, 'is_active' => true]);

        $this->assertFalse(app(ShippingService::class)->availableMethods()->contains('id', $method->id));

        $company->update(['is_active' => true]);
        $this->assertTrue(app(ShippingService::class)->availableMethods()->contains('id', $method->id));
    }
}
