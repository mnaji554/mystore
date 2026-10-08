<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Permissions::ROLES as $slug => $definition) {
            Role::updateOrCreate(['slug' => $slug], [
                'name' => $definition['name'],
                'permissions' => $definition['permissions'],
            ]);
        }
    }
}
