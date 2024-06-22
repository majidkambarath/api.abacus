<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Roles to be seeded
        $roles = ['admin', 'manager', 'executive'];

        // Create roles if they don't exist
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // Admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('1234'),
            ]
        );

        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole && !$adminUser->roles->contains($adminRole->id)) {
            $adminUser->roles()->attach($adminRole);
        }

        // Manager user
        $managerUser = User::firstOrCreate(
            ['email' => 'manager@test.com'],
            [
                'name' => 'Manager',
                'password' => Hash::make('1234'),
            ]
        );

        $managerRole = Role::where('name', 'manager')->first();
        if ($managerRole && !$managerUser->roles->contains($managerRole->id)) {
            $managerUser->roles()->attach($managerRole);
        }

        // Executive user
        $executiveUser = User::firstOrCreate(
            ['email' => 'executive@test.com'],
            [
                'name' => 'Executive',
                'password' => Hash::make('1234'),
            ]
        );

        $executiveRole = Role::where('name', 'executive')->first();
        if ($executiveRole && !$executiveUser->roles->contains($executiveRole->id)) {
            $executiveUser->roles()->attach($executiveRole);
        }
    }
}
