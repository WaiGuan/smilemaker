<?php

/// Author: Pooi Wai Guan

namespace App\Services;

use App\Models\User;
use App\Models\Appointment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DoctorService
{
    /**
     * Create a new doctor
     */
    public function createDoctor(array $data): array
    {
        try {
            $doctor = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'doctor',
                'phone' => $data['phone'],
                'specialization' => $data['specialization'] ?? null,
                'license_number' => $data['license_number'] ?? null,
            ]);

            return [
                'success' => true,
                'doctor' => $doctor,
                'message' => 'Doctor registered successfully!'
            ];

        } catch (\Exception $e) {
            Log::error('Doctor Creation Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to create doctor: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update doctor information
     */
    public function updateDoctor(User $doctor, array $data): array
    {
        try {
            if ($doctor->role !== 'doctor') {
                return [
                    'success' => false,
                    'error' => 'User is not a doctor.'
                ];
            }

            $doctor->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'specialization' => $data['specialization'] ?? $doctor->specialization,
                'license_number' => $data['license_number'] ?? $doctor->license_number,
            ]);

            return [
                'success' => true,
                'doctor' => $doctor,
                'message' => 'Doctor updated successfully!'
            ];

        } catch (\Exception $e) {
            Log::error('Doctor Update Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to update doctor: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a doctor
     */
    public function deleteDoctor(User $doctor): array
    {
        try {
            if ($doctor->role !== 'doctor') {
                return [
                    'success' => false,
                    'error' => 'User is not a doctor.'
                ];
            }

            // Check if doctor has any appointments
            if ($doctor->doctorAppointments()->count() > 0) {
                return [
                    'success' => false,
                    'error' => 'Cannot delete doctor with existing appointments.'
                ];
            }

            $doctor->delete();

            return [
                'success' => true,
                'message' => 'Doctor deleted successfully!'
            ];

        } catch (\Exception $e) {
            Log::error('Doctor Deletion Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to delete doctor: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all doctors with pagination
     */
    public function getAllDoctors(int $perPage = 10): array
    {
        try {
            $doctors = User::where('role', 'doctor')->paginate($perPage);

            return [
                'success' => true,
                'doctors' => $doctors
            ];

        } catch (\Exception $e) {
            Log::error('Get Doctors Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to retrieve doctors: ' . $e->getMessage(),
                'doctors' => collect()
            ];
        }
    }

    /**
     * Get doctor by ID
     */
    public function getDoctorById(int $id): array
    {
        try {
            $doctor = User::where('role', 'doctor')->find($id);

            if (!$doctor) {
                return [
                    'success' => false,
                    'error' => 'Doctor not found.'
                ];
            }

            return [
                'success' => true,
                'doctor' => $doctor
            ];

        } catch (\Exception $e) {
            Log::error('Get Doctor Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to retrieve doctor: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get doctor's appointments
     */
    public function getDoctorAppointments(User $doctor, string $status = null, int $limit = null): array
    {
        try {
            if ($doctor->role !== 'doctor') {
                return [
                    'success' => false,
                    'error' => 'User is not a doctor.'
                ];
            }

            $query = $doctor->doctorAppointments()->with(['service', 'patient', 'payment']);

            if ($status) {
                $query->where('status', $status);
            }

            if ($limit) {
                $query->limit($limit);
            }

            $appointments = $query->orderBy('appointment_date', 'desc')->get();

            return [
                'success' => true,
                'appointments' => $appointments
            ];

        } catch (\Exception $e) {
            Log::error('Get Doctor Appointments Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to retrieve doctor appointments: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get doctor's today appointments
     */
    public function getTodayAppointments(User $doctor): array
    {
        try {
            if ($doctor->role !== 'doctor') {
                return [
                    'success' => false,
                    'error' => 'User is not a doctor.'
                ];
            }

            $appointments = $doctor->doctorAppointments()
                ->with(['service', 'patient'])
                ->whereDate('appointment_date', today())
                ->where('status', '!=', 'cancelled')
                ->orderBy('appointment_date')
                ->get();

            return [
                'success' => true,
                'appointments' => $appointments
            ];

        } catch (\Exception $e) {
            Log::error('Get Today Appointments Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to retrieve today appointments: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get doctor's upcoming appointments
     */
    public function getUpcomingAppointments(User $doctor, int $limit = 5): array
    {
        try {
            if ($doctor->role !== 'doctor') {
                return [
                    'success' => false,
                    'error' => 'User is not a doctor.'
                ];
            }

            $appointments = $doctor->doctorAppointments()
                ->with(['service', 'patient'])
                ->where('appointment_date', '>', now())
                ->where('status', '!=', 'cancelled')
                ->orderBy('appointment_date')
                ->limit($limit)
                ->get();

            return [
                'success' => true,
                'appointments' => $appointments
            ];

        } catch (\Exception $e) {
            Log::error('Get Upcoming Appointments Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to retrieve upcoming appointments: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get doctor statistics
     */
    public function getDoctorStats(User $doctor): array
    {
        try {
            if ($doctor->role !== 'doctor') {
                return [
                    'success' => false,
                    'error' => 'User is not a doctor.'
                ];
            }

            $totalAppointments = $doctor->doctorAppointments()->count();
            $todayAppointments = $doctor->doctorAppointments()
                ->whereDate('appointment_date', today())
                ->count();
            $pendingAppointments = $doctor->doctorAppointments()
                ->where('status', 'pending')
                ->count();
            $completedAppointments = $doctor->doctorAppointments()
                ->where('status', 'completed')
                ->count();

            return [
                'success' => true,
                'stats' => [
                    'total' => $totalAppointments,
                    'today' => $todayAppointments,
                    'pending' => $pendingAppointments,
                    'completed' => $completedAppointments,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Get Doctor Stats Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to retrieve doctor statistics: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get available doctors for a specific time slot
     */
    public function getAvailableDoctors(string $appointmentDate): array
    {
        try {
            $doctors = User::where('role', 'doctor')->get();
            $availableDoctors = [];

            foreach ($doctors as $doctor) {
                // Check if doctor has any appointments at this time
                $hasAppointment = $doctor->doctorAppointments()
                    ->where('appointment_date', $appointmentDate)
                    ->where('status', '!=', 'cancelled')
                    ->exists();

                if (!$hasAppointment) {
                    $availableDoctors[] = $doctor;
                }
            }

            return [
                'success' => true,
                'doctors' => $availableDoctors
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
     * Search doctors by specialization or name
     */
    public function searchDoctors(string $query): array
    {
        try {
            $doctors = User::where('role', 'doctor')
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('specialization', 'like', "%{$query}%");
                })
                ->get();

            return [
                'success' => true,
                'doctors' => $doctors
            ];

        } catch (\Exception $e) {
            Log::error('Search Doctors Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to search doctors: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update doctor's password
     */
    public function updateDoctorPassword(User $doctor, string $newPassword): array
    {
        try {
            if ($doctor->role !== 'doctor') {
                return [
                    'success' => false,
                    'error' => 'User is not a doctor.'
                ];
            }

            $doctor->update([
                'password' => Hash::make($newPassword)
            ]);

            return [
                'success' => true,
                'message' => 'Doctor password updated successfully!'
            ];

        } catch (\Exception $e) {
            Log::error('Update Doctor Password Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to update doctor password: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate doctor data
     */
    public function validateDoctorData(array $data, bool $isUpdate = false): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'specialization' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:100',
        ];

        if (!$isUpdate) {
            $rules['email'] = 'required|string|email|max:255|unique:users';
            $rules['password'] = 'required|string|min:6|confirmed';
        } else {
            $rules['email'] = 'required|string|email|max:255';
        }

        $validator = \Validator::make($data, $rules);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors()
            ];
        }

        return [
            'success' => true
        ];
    }
}
