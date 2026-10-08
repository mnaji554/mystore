<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const SUPER_ADMIN = 'super_admin';

    public const ADMIN = 'admin';

    public const MANAGER = 'manager';

    public const CUSTOMER = 'customer';

    protected $fillable = ['slug', 'name', 'permissions'];

    protected function casts(): array
    {
        return ['permissions' => 'array'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function can(string $permission): bool
    {
        $permissions = $this->permissions ?? [];

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }
}
