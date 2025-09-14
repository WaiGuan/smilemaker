<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Get patient dashboard data
     */
    public function getPatientDashboard(User $user): array
    {
        try {
            // Get upcoming appointments
            $upcomingAppointments = $user->patientAppointments()
                ->with(['service', 'doctor'])
                ->where('appointment_date', '>', now())
                ->where('status', '!=', 'cancelled')
                ->orderBy('appointment_date')
                ->limit(5)
                ->get();

            // Get recent appointments
            $recentAppointments = $user->patientAppointments()
                ->with(['service', 'doctor'])
                ->orderBy('appointment_date', 'desc')
                ->limit(5)
                ->get();

            // Get unread notifications count
            $unreadNotifications = $user->notifications()->where('is_read', false)->count();

            // Get appointment statistics
            $appointmentStats = $this->getPatientAppointmentStats($user);

            return [
                'success' => true,
                'data' => [
                    'upcomingAppointments' => $upcomingAppointments,
                    'recentAppointments' => $recentAppointments,
                    'unreadNotifications' => $unreadNotifications,
                    'appointmentStats' => $appointmentStats,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Patient Dashboard Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to load patient dashboard data.'
            ];
        }
    }

    /**
     * Get doctor dashboard data
     */
    public function getDoctorDashboard(User $user): array
    {
        try {
            // Get today's appointments
            $todayAppointments = $user->doctorAppointments()
                ->with(['service', 'patient'])
                ->whereDate('appointment_date', today())
                ->where('status', '!=', 'cancelled')
                ->orderBy('appointment_date')
                ->get();

            // Get upcoming appointments
            $upcomingAppointments = $user->doctorAppointments()
                ->with(['service', 'patient'])
                ->where('appointment_date', '>', now())
                ->where('status', '!=', 'cancelled')
                ->orderBy('appointment_date')
                ->limit(5)
                ->get();

            // Get unread notifications count
            $unreadNotifications = $user->notifications()->where('is_read', false)->count();

            // Get doctor statistics
            $doctorStats = $this->getDoctorStats($user);

            return [
                'success' => true,
                'data' => [
                    'todayAppointments' => $todayAppointments,
                    'upcomingAppointments' => $upcomingAppointments,
                    'unreadNotifications' => $unreadNotifications,
                    'doctorStats' => $doctorStats,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Doctor Dashboard Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to load doctor dashboard data.'
            ];
        }
    }

    /**
     * Get admin dashboard data
     */
    public function getAdminDashboard(User $user): array
    {
        try {
            // Get basic statistics
            $totalPatients = User::where('role', 'patient')->count();
            $totalDoctors = User::where('role', 'doctor')->count();
            $totalAppointments = Appointment::count();
            $totalRevenue = Payment::where('status', 'paid')->sum('amount');

            // Get today's appointments
            $todayAppointments = Appointment::with(['service', 'patient', 'doctor'])
                ->whereDate('appointment_date', today())
                ->where('status', '!=', 'cancelled')
                ->orderBy('appointment_date')
                ->get();

            // Get pending appointments (sorted by nearest appointment date)
            $recentAppointments = Appointment::with(['service', 'patient', 'doctor'])
                ->where('status', 'pending')
                ->orderBy('appointment_date', 'asc')
                ->limit(10)
                ->get();

            // Get pending payments
            $pendingPayments = Payment::with(['appointment.patient', 'appointment.service'])
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Get unread notifications count
            $unreadNotifications = $user->notifications()->where('is_read', false)->count();

            // Get revenue statistics
            $revenueStats = $this->getRevenueStats();

            // Get appointment statistics
            $appointmentStats = $this->getAppointmentStats();

            return [
                'success' => true,
                'data' => [
                    'totalPatients' => $totalPatients,
                    'totalDoctors' => $totalDoctors,
                    'totalAppointments' => $totalAppointments,
                    'totalRevenue' => $totalRevenue,
                    'todayAppointments' => $todayAppointments,
                    'recentAppointments' => $recentAppointments,
                    'pendingPayments' => $pendingPayments,
                    'unreadNotifications' => $unreadNotifications,
                    'revenueStats' => $revenueStats,
                    'appointmentStats' => $appointmentStats,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Admin Dashboard Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to load admin dashboard data.'
            ];
        }
    }

    /**
     * Get patient appointment statistics
     */
    private function getPatientAppointmentStats(User $user): array
    {
        $totalAppointments = $user->patientAppointments()->count();
        $upcomingAppointments = $user->patientAppointments()
            ->where('appointment_date', '>', now())
            ->where('status', '!=', 'cancelled')
            ->count();
        $completedAppointments = $user->patientAppointments()
            ->where('status', 'completed')
            ->count();
        $cancelledAppointments = $user->patientAppointments()
            ->where('status', 'cancelled')
            ->count();

        return [
            'total' => $totalAppointments,
            'upcoming' => $upcomingAppointments,
            'completed' => $completedAppointments,
            'cancelled' => $cancelledAppointments,
        ];
    }

    /**
     * Get doctor statistics
     */
    private function getDoctorStats(User $user): array
    {
        $totalAppointments = $user->doctorAppointments()->count();
        $todayAppointments = $user->doctorAppointments()
            ->whereDate('appointment_date', today())
            ->count();
        $pendingAppointments = $user->doctorAppointments()
            ->where('status', 'pending')
            ->count();
        $completedAppointments = $user->doctorAppointments()
            ->where('status', 'completed')
            ->count();

        return [
            'total' => $totalAppointments,
            'today' => $todayAppointments,
            'pending' => $pendingAppointments,
            'completed' => $completedAppointments,
        ];
    }

    /**
     * Get revenue statistics
     */
    private function getRevenueStats(): array
    {
        $totalRevenue = Payment::where('status', 'paid')->sum('amount');
        $todayRevenue = Payment::where('status', 'paid')
            ->whereDate('paid_at', today())
            ->sum('amount');
        $monthlyRevenue = Payment::where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');
        $pendingRevenue = Payment::where('status', 'pending')->sum('amount');

        return [
            'total' => $totalRevenue,
            'today' => $todayRevenue,
            'monthly' => $monthlyRevenue,
            'pending' => $pendingRevenue,
        ];
    }

    /**
     * Get appointment statistics
     */
    private function getAppointmentStats(): array
    {
        $totalAppointments = Appointment::count();
        $todayAppointments = Appointment::whereDate('appointment_date', today())->count();
        $pendingAppointments = Appointment::where('status', 'pending')->count();
        $completedAppointments = Appointment::where('status', 'completed')->count();
        $cancelledAppointments = Appointment::where('status', 'cancelled')->count();

        return [
            'total' => $totalAppointments,
            'today' => $todayAppointments,
            'pending' => $pendingAppointments,
            'completed' => $completedAppointments,
            'cancelled' => $cancelledAppointments,
        ];
    }

    /**
     * Get chart data for revenue
     */
    public function getRevenueChartData(int $days = 30): array
    {
        try {
            $data = [];
            $labels = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $revenue = Payment::where('status', 'paid')
                    ->whereDate('paid_at', $date)
                    ->sum('amount');

                $data[] = $revenue;
                $labels[] = $date->format('M d');
            }

            return [
                'success' => true,
                'data' => [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'Daily Revenue',
                            'data' => $data,
                            'borderColor' => 'rgb(75, 192, 192)',
                            'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                        ]
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Revenue Chart Data Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to load revenue chart data.'
            ];
        }
    }

    /**
     * Get chart data for appointments
     */
    public function getAppointmentChartData(int $days = 30): array
    {
        try {
            $data = [];
            $labels = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $appointments = Appointment::whereDate('created_at', $date)->count();

                $data[] = $appointments;
                $labels[] = $date->format('M d');
            }

            return [
                'success' => true,
                'data' => [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'Daily Appointments',
                            'data' => $data,
                            'borderColor' => 'rgb(54, 162, 235)',
                            'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                        ]
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Appointment Chart Data Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to load appointment chart data.'
            ];
        }
    }

    /**
     * Get service popularity data
     */
    public function getServicePopularityData(): array
    {
        try {
            $services = Service::withCount('appointments')
                ->orderBy('appointments_count', 'desc')
                ->get();

            $labels = $services->pluck('name')->toArray();
            $data = $services->pluck('appointments_count')->toArray();

            return [
                'success' => true,
                'data' => [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'Appointments',
                            'data' => $data,
                            'backgroundColor' => [
                                'rgba(255, 99, 132, 0.8)',
                                'rgba(54, 162, 235, 0.8)',
                                'rgba(255, 205, 86, 0.8)',
                                'rgba(75, 192, 192, 0.8)',
                                'rgba(153, 102, 255, 0.8)',
                            ]
                        ]
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Service Popularity Data Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to load service popularity data.'
            ];
        }
    }

    /**
     * Get recent activity data
     */
    public function getRecentActivity(int $limit = 10): array
    {
        try {
            $activities = [];

            // Recent appointments
            $recentAppointments = Appointment::with(['service', 'patient', 'doctor'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($recentAppointments as $appointment) {
                $activities[] = [
                    'type' => 'appointment',
                    'message' => "New appointment booked for {$appointment->service->name} by {$appointment->patient->name}",
                    'timestamp' => $appointment->created_at,
                    'user' => $appointment->patient->name,
                ];
            }

            // Recent payments
            $recentPayments = Payment::with(['appointment.patient', 'appointment.service'])
                ->where('status', 'paid')
                ->orderBy('paid_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($recentPayments as $payment) {
                $activities[] = [
                    'type' => 'payment',
                    'message' => "Payment of RM" . number_format($payment->amount, 2) . " received for {$payment->appointment->service->name}",
                    'timestamp' => $payment->paid_at,
                    'user' => $payment->appointment->patient->name,
                ];
            }

            // Sort by timestamp
            usort($activities, function ($a, $b) {
                return $b['timestamp'] <=> $a['timestamp'];
            });

            return [
                'success' => true,
                'activities' => array_slice($activities, 0, $limit)
            ];

        } catch (\Exception $e) {
            Log::error('Recent Activity Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to load recent activity data.'
            ];
        }
    }
}
