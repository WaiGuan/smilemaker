<?php

/// Author: Yuen Yun Jia

namespace App\Services;

use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ServiceService
{
    /**
     * Get all services
     */
    public function getAllServices(): array
    {
        try {
            $services = Service::all();

            return [
                'success' => true,
                'services' => $services
            ];

        } catch (\Exception $e) {
            Log::error('Service Service Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to retrieve services: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get service by ID
     */
    public function getServiceById(int $id): array
    {
        try {
            $service = Service::findOrFail($id);

            return [
                'success' => true,
                'service' => $service
            ];

        } catch (\Exception $e) {
            Log::error('Get Service Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Service not found.'
            ];
        }
    }

    /**
     * Get all doctors
     */
    public function getAllDoctors(): array
    {
        try {
            $doctors = User::where('role', 'doctor')->get();

            return [
                'success' => true,
                'doctors' => $doctors
            ];

        } catch (\Exception $e) {
            Log::error('Get Doctors Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to retrieve doctors: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get available doctors for appointment booking
     */
    public function getAvailableDoctors(): array
    {
        try {
            $doctors = User::where('role', 'doctor')
                ->orderBy('name')
                ->get();

            return [
                'success' => true,
                'doctors' => $doctors
            ];

        } catch (\Exception $e) {
            Log::error('Get Available Doctors Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to retrieve available doctors: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create a new service
     */
    public function createService(array $data): array
    {
        try {
            $service = Service::create($data);

            return [
                'success' => true,
                'service' => $service,
                'message' => 'Service created successfully!'
            ];

        } catch (\Exception $e) {
            Log::error('Create Service Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to create service: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update a service
     */
    public function updateService(Service $service, array $data): array
    {
        try {
            $service->update($data);

            return [
                'success' => true,
                'service' => $service,
                'message' => 'Service updated successfully!'
            ];

        } catch (\Exception $e) {
            Log::error('Update Service Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to update service: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a service
     */
    public function deleteService(Service $service): array
    {
        try {
            // Check if service has appointments
            if ($service->appointments()->count() > 0) {
                return [
                    'success' => false,
                    'error' => 'Cannot delete service with existing appointments.'
                ];
            }

            $service->delete();

            return [
                'success' => true,
                'message' => 'Service deleted successfully!'
            ];

        } catch (\Exception $e) {
            Log::error('Delete Service Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to delete service: ' . $e->getMessage()
            ];
        }
    }
}
