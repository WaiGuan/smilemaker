<?php

namespace App\Services\DashboardStrategies;

use App\Models\User;
use App\Services\DashboardService;

class DoctorDashboardStrategy implements DashboardStrategyInterface
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get doctor dashboard data
     */
    public function getDashboardData(User $user): array
    {
        return $this->dashboardService->getDoctorDashboard($user);
    }

    /**
     * Get the view name for doctor dashboard
     */
    public function getViewName(): string
    {
        return 'dashboard.doctor';
    }

    /**
     * Get fallback data when dashboard service fails
     */
    public function getFallbackData(): array
    {
        return [
            'todayAppointments' => collect(),
            'upcomingAppointments' => collect(),
            'unreadNotifications' => 0,
            'doctorStats' => []
        ];
    }
}
