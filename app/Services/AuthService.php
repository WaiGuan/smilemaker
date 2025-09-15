<?php

/// Author: Pooi Wai Guan

namespace App\Services;

use App\Models\User;
use App\Events\UserLoggedIn;
use App\Events\UserRegistered;
use App\Events\UserLoggedOut;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthService
{
    /**
     * Authenticate user login
     */
    public function login(array $credentials, bool $remember = false): array
    {
        try {
            // Log the login attempt for debugging
            Log::info('Login attempt', [
                'email' => $credentials['email'],
                'remember' => $remember
            ]);

            if (Auth::attempt($credentials, $remember)) {
                $user = Auth::user();
                
                // Dispatch UserLoggedIn event instead of direct logging
                event(new UserLoggedIn($user, request()->ip(), request()->userAgent()));
                
                return [
                    'success' => true,
                    'user' => $user,
                    'redirect_url' => $this->getRedirectUrl($user)
                ];
            }

            // Log failed login
            Log::warning('Login failed', [
                'email' => $credentials['email'],
                'reason' => 'Invalid credentials'
            ]);

            return [
                'success' => false,
                'error' => 'The provided credentials do not match our records.'
            ];

        } catch (\Exception $e) {
            Log::error('Authentication Error: ' . $e->getMessage(), [
                'email' => $credentials['email'] ?? 'unknown',
                'exception' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => 'Authentication failed. Please try again.'
            ];
        }
    }

    /**
     * Register a new patient
     */
    public function register(array $data): array
    {
        try {
            // Create the user (patients can register themselves)
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'patient', // Only patients can register
                'phone' => $data['phone'] ?? null,
            ]);

            // Dispatch UserRegistered event
            event(new UserRegistered($user));

            // Log the user in
            Auth::login($user);

            return [
                'success' => true,
                'user' => $user,
                'message' => 'Registration successful! Welcome to our dental clinic.'
            ];

        } catch (\Exception $e) {
            Log::error('Registration Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Registration failed. Please try again.'
            ];
        }
    }

    /**
     * Logout user
     */
    public function logout(): array
    {
        try {
            $user = Auth::user();
            
            // Log logout attempt
            if ($user) {
                Log::info('Logout attempt', [
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                    'user_email' => $user->email
                ]);
            }
            
            Auth::logout();
            
            // Dispatch UserLoggedOut event if user was logged in
            if ($user) {
                event(new UserLoggedOut($user));
            }
            
            Log::info('Logout successful');
            
            return [
                'success' => true,
                'message' => 'You have been logged out successfully.'
            ];

        } catch (\Exception $e) {
            Log::error('Logout Error: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => 'Logout failed. Please try again.'
            ];
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile(User $user, array $data): array
    {
        try {
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? $user->phone,
            ]);

            return [
                'success' => true,
                'user' => $user,
                'message' => 'Profile updated successfully.'
            ];

        } catch (\Exception $e) {
            Log::error('Profile Update Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to update profile. Please try again.'
            ];
        }
    }

    /**
     * Change user password
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): array
    {
        try {
            // Verify current password
            if (!Hash::check($currentPassword, $user->password)) {
                return [
                    'success' => false,
                    'error' => 'Current password is incorrect.'
                ];
            }

            // Update password
            $user->update([
                'password' => Hash::make($newPassword)
            ]);

            return [
                'success' => true,
                'message' => 'Password changed successfully.'
            ];

        } catch (\Exception $e) {
            Log::error('Password Change Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to change password. Please try again.'
            ];
        }
    }

    /**
     * Get redirect URL based on user role
     */
    private function getRedirectUrl(User $user): string
    {
        if ($user->isAdmin()) {
            return '/admin/dashboard';
        } elseif ($user->isDoctor()) {
            return '/doctor/dashboard';
        } else {
            return '/patient/dashboard';
        }
    }

    /**
     * Check if user can access a specific route
     */
    public function canAccessRoute(User $user, string $route): bool
    {
        $adminRoutes = ['admin.dashboard', 'admin.doctors', 'admin.appointments', 'admin.payments'];
        $doctorRoutes = ['doctor.dashboard', 'doctor.appointments'];
        $patientRoutes = ['patient.dashboard', 'appointments.create', 'appointments.my-appointments'];

        if ($user->isAdmin()) {
            return true; // Admin can access all routes
        } elseif ($user->isDoctor()) {
            return in_array($route, array_merge($doctorRoutes, $patientRoutes));
        } else {
            return in_array($route, $patientRoutes);
        }
    }

    /**
     * Get user dashboard data
     */
    public function getDashboardData(User $user): array
    {
        try {
            if ($user->isAdmin()) {
                return $this->getAdminDashboardData($user);
            } elseif ($user->isDoctor()) {
                return $this->getDoctorDashboardData($user);
            } else {
                return $this->getPatientDashboardData($user);
            }

        } catch (\Exception $e) {
            Log::error('Dashboard Data Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to load dashboard data.'
            ];
        }
    }

    /**
     * Get admin dashboard data
     */
    private function getAdminDashboardData(User $user): array
    {
        $totalPatients = User::where('role', 'patient')->count();
        $totalDoctors = User::where('role', 'doctor')->count();
        $totalAppointments = \App\Models\Appointment::count();
        $totalRevenue = \App\Models\Payment::where('status', 'paid')->sum('amount');

        return [
            'success' => true,
            'data' => [
                'totalPatients' => $totalPatients,
                'totalDoctors' => $totalDoctors,
                'totalAppointments' => $totalAppointments,
                'totalRevenue' => $totalRevenue,
            ]
        ];
    }

    /**
     * Get doctor dashboard data
     */
    private function getDoctorDashboardData(User $user): array
    {
        $todayAppointments = $user->doctorAppointments()
            ->with(['service', 'patient'])
            ->whereDate('appointment_date', today())
            ->where('status', '!=', 'cancelled')
            ->orderBy('appointment_date')
            ->get();

        $upcomingAppointments = $user->doctorAppointments()
            ->with(['service', 'patient'])
            ->where('appointment_date', '>', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('appointment_date')
            ->limit(5)
            ->get();

        return [
            'success' => true,
            'data' => [
                'todayAppointments' => $todayAppointments,
                'upcomingAppointments' => $upcomingAppointments,
            ]
        ];
    }

    /**
     * Get patient dashboard data
     */
    private function getPatientDashboardData(User $user): array
    {
        $upcomingAppointments = $user->patientAppointments()
            ->with(['service', 'doctor'])
            ->where('appointment_date', '>', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('appointment_date')
            ->limit(5)
            ->get();

        $recentAppointments = $user->patientAppointments()
            ->with(['service', 'doctor'])
            ->orderBy('appointment_date', 'desc')
            ->limit(5)
            ->get();

        return [
            'success' => true,
            'data' => [
                'upcomingAppointments' => $upcomingAppointments,
                'recentAppointments' => $recentAppointments,
            ]
        ];
    }

    /**
     * Validate user permissions for an action
     */
    public function validatePermission(User $user, string $action, $resource = null): array
    {
        try {
            switch ($action) {
                case 'view_appointment':
                    if ($resource && !$this->canViewAppointment($user, $resource)) {
                        return [
                            'success' => false,
                            'error' => 'Unauthorized access to appointment.'
                        ];
                    }
                    break;

                case 'cancel_appointment':
                    if ($resource && !$this->canCancelAppointment($user, $resource)) {
                        return [
                            'success' => false,
                            'error' => 'Unauthorized to cancel this appointment.'
                        ];
                    }
                    break;

                case 'update_appointment':
                    if (!$user->isAdmin() && !$user->isDoctor()) {
                        return [
                            'success' => false,
                            'error' => 'Unauthorized to update appointment.'
                        ];
                    }
                    break;

                case 'manage_doctors':
                    if (!$user->isAdmin()) {
                        return [
                            'success' => false,
                            'error' => 'Unauthorized to manage doctors.'
                        ];
                    }
                    break;
            }

            return [
                'success' => true
            ];

        } catch (\Exception $e) {
            Log::error('Permission Validation Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Permission validation failed.'
            ];
        }
    }

    /**
     * Check if user can view appointment
     */
    private function canViewAppointment(User $user, $appointment): bool
    {
        return $user->isAdmin() || 
               $appointment->patient_id === $user->id || 
               $appointment->doctor_id === $user->id;
    }

    /**
     * Check if user can cancel appointment
     */
    private function canCancelAppointment(User $user, $appointment): bool
    {
        return $user->isAdmin() || $appointment->patient_id === $user->id;
    }

    /**
     * Get user by email
     */
    public function getUserByEmail(string $email): array
    {
        try {
            $user = User::where('email', $email)->first();

            if (!$user) {
                return [
                    'success' => false,
                    'error' => 'User not found.'
                ];
            }

            return [
                'success' => true,
                'user' => $user
            ];

        } catch (\Exception $e) {
            Log::error('Get User By Email Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to retrieve user: ' . $e->getMessage()
            ];
        }
    }
}
