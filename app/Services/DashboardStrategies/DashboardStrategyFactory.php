<?php

namespace App\Services\DashboardStrategies;

use App\Models\User;
use App\Services\DashboardService;

class DashboardStrategyFactory
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Create the appropriate dashboard strategy based on user role
     */
    public function createStrategy(User $user): DashboardStrategyInterface
    {
        return match ($user->role) {
            'admin' => new AdminDashboardStrategy($this->dashboardService),
            'doctor' => new DoctorDashboardStrategy($this->dashboardService),
            'patient' => new PatientDashboardStrategy($this->dashboardService),
            default => throw new \InvalidArgumentException("Unsupported user role: {$user->role}")
        };
    }

    /**
     * Get dashboard data using the appropriate strategy
     */
    public function getDashboardData(User $user): array
    {
        $strategy = $this->createStrategy($user);
        return $strategy->getDashboardData($user);
    }

    /**
     * Get dashboard view using the appropriate strategy
     */
    public function getDashboardView(User $user): array
    {
        $strategy = $this->createStrategy($user);
        $result = $strategy->getDashboardData($user);

        if ($result['success']) {
            return [
                'view' => $strategy->getViewName(),
                'data' => $result['data']
            ];
        } else {
            return [
                'view' => $strategy->getViewName(),
                'data' => $strategy->getFallbackData()
            ];
        }
    }
}
