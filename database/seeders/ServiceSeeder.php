<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default services
        $services = [
            [
                'name' => 'Consultation',
                'description' => 'General dental consultation and examination',
                'price' => 0, // Free service
            ],
            [
                'name' => 'Teeth Cleaning',
                'description' => 'Professional teeth cleaning and scaling',
                'price' => 100.00,
            ],
            [
                'name' => 'Filling',
                'description' => 'Dental filling for cavities',
                'price' => 150.00,
            ],
            [
                'name' => 'Whitening',
                'description' => 'Professional teeth whitening treatment',
                'price' => 200.00,
            ],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
