<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'tenant_id' => 'gadzila',
            'type'      => 'phone',
            'role'      => 'user',
            'phone'     => '2077363677',
            'name'      => 'TENG',
            'gender'    => 'M',
            'dob'       => '2023-12-23',
            'province'  => 'VT',
            'district'  => 'ໄຊເສດຖາ',
            'village'   => 'ວຽງຈະເລີນ',
        ]);

        auth()->loginUsingId($user->id);
    }
}
