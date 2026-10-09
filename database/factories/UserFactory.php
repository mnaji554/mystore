<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '05'.fake()->numerify('########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Password123'),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    private function withRole(string $slug): static
    {
        return $this->state(fn () => [
            'role_id' => Role::firstOrCreate(
                ['slug' => $slug],
                ['name' => Permissions::ROLES[$slug]['name'], 'permissions' => Permissions::ROLES[$slug]['permissions']],
            )->id,
        ]);
    }

    public function customer(): static
    {
        return $this->withRole(Role::CUSTOMER);
    }

    public function manager(): static
    {
        return $this->withRole(Role::MANAGER);
    }

    public function courier(): static
    {
        return $this->withRole(Role::COURIER);
    }

    public function admin(): static
    {
        return $this->withRole(Role::ADMIN);
    }

    public function superAdmin(): static
    {
        return $this->withRole(Role::SUPER_ADMIN);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
