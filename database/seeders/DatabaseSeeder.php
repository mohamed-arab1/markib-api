<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@nileboats.com',
            'password' => Hash::make('password'),
            'phone' => '01000000000',
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'مستخدم تجريبي',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'phone' => '01012345678',
            'role' => 'user',
        ]);

        $this->call([
            VesselTypeSeeder::class,
            VesselSeeder::class,
            LocationSeeder::class,
            LocationImageSeeder::class,
            TripSeeder::class,
        ]);
    }
}
