<?php

/// Author: Foo Tek Sian

namespace App\Services\DashboardStrategies;

use App\Models\User;
use App\Services\DashboardService;

class PatientDashboardStrategy implements DashboardStrategyInterface
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get patient dashboard data
     */
    public function getDashboardData(User $user): array
    {
        return $this->dashboardService->getPatientDashboard($user);
    }

    /**
     * Get the view name for patient dashboard
     */
    public function getViewName(): string
    {
        return 'dashboard.patient';
    }

    /**
     * Get fallback data when dashboard service fails
     */
    public function getFallbackData(): array
    {
        return [
            'upcomingAppointments' => collect(),
            'recentAppointments' => collect(),
            'unreadNotifications' => 0,
            'appointmentStats' => []
        ];
    }
}
