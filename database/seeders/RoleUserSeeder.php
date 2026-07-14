<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleUserSeeder extends Seeder
{
    /**
     * Seed one account per role plus a second employee, all with password "password".
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'System Admin', 'role' => 'admin', 'password' => Hash::make('password')]
        );

        $manager = User::updateOrCreate(
            ['email' => 'manager@example.com'],
            ['name' => 'Tanaka Manager', 'role' => 'manager', 'password' => Hash::make('password')]
        );

        User::updateOrCreate(
            ['email' => 'employee@example.com'],
            ['name' => 'Yamada Taro', 'role' => 'employee', 'manager_id' => $manager->id, 'password' => Hash::make('password')]
        );

        User::updateOrCreate(
            ['email' => 'employee2@example.com'],
            ['name' => 'Suzuki Hanako', 'role' => 'employee', 'manager_id' => $manager->id, 'password' => Hash::make('password')]
        );
    }
}
