<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public const DEV_SUPER_ADMIN_EMAIL = 'pele@bizgital.com';
    public const DEV_SUPER_ADMIN_PASSWORD = 'ChangeMe!AdminM0';

    public function run(): void
    {
        Admin::query()->updateOrCreate(
            ['email' => self::DEV_SUPER_ADMIN_EMAIL],
            [
                'name' => 'Pele BIZGITAL',
                'password' => Hash::make(self::DEV_SUPER_ADMIN_PASSWORD),
                'role' => 'super',
                'tenant_id' => null,
                'status' => 'active',
            ]
        );
    }
}
