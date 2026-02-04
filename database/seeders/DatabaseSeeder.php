<?php

namespace Database\Seeders;

use App\Models\SystemConfig;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AnimalSpeciesSeeder::class,
            AnimalGenotypeCategorySeeder::class,
            FeedSeeder::class,
        ]);

        $userRole = Role::findOrCreate('user');
        $adminRole = Role::findOrCreate('admin');

        $admin = User::query()->updateOrCreate([
            'email' => env('SEED_ADMIN_EMAIL', 'admin@dziennik.local'),
        ], [
            'name' => env('SEED_ADMIN_NAME', 'Admin'),
            'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'Admin12345!')),
            'email_verified_at' => now(),
            'is_blocked' => false,
        ]);
        $admin->syncRoles([$adminRole]);

        $sampleUser = User::query()->updateOrCreate([
            'email' => 'user@dziennik.local',
        ], [
            'name' => 'Uzytkownik testowy',
            'password' => Hash::make('User12345!'),
            'email_verified_at' => now(),
        ]);
        $sampleUser->syncRoles([$userRole]);

        SystemConfig::setValue('apiDziennik', env('HODOWLA_API_TOKEN'));
        SystemConfig::setValue('global_message', 'Witamy w dzienniku hodowlanym.');
    }
}
