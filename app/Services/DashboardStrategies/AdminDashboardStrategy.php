<?php

/// Author: Foo Tek Sian

namespace App\Services\DashboardStrategies;

use App\Models\User;
use App\Services\DashboardService;

class AdminDashboardStrategy implements DashboardStrategyInterface
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get admin dashboard data
     */
    public function getDashboardData(User $user): array
    {
        return $this->dashboardService->getAdminDashboard($user);
    }

    /**
     * Get the view name for admin dashboard
     */
    public function getViewName(): string
    {
        return 'dashboard.admin';
    }

    /**
     * Get fallback data when dashboard service fails
     */
    public function getFallbackData(): array
    {
        return [
            'totalPatients' => 0,
            'totalDoctors' => 0,
            'totalAppointments' => 0,
            'totalRevenue' => 0,
            'todayAppointments' => collect(),
            'recentAppointments' => collect(),
            'pendingPayments' => collect(),
            'unreadNotifications' => 0,
            'revenueStats' => [],
            'appointmentStats' => []
        ];
    }
}
