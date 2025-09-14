<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@clinic.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '123-456-7890',
        ]);

        // Create sample doctor
        User::create([
            'name' => 'Smith',
            'email' => 'doctor@clinic.com',
            'password' => Hash::make('password'),
            'role' => 'doctor',
            'phone' => '123-456-7891',
            'specialization' => 'General Dentistry',
            'license_number' => 'DENT123456',
        ]);

        // Create sample patient
        User::create([
            'name' => 'John Doe',
            'email' => 'patient@example.com',
            'password' => Hash::make('password'),
            'role' => 'patient',
            'phone' => '123-456-7892',
        ]);
    }
}
