<?php

namespace App\Services\DashboardStrategies;

use App\Models\User;

interface DashboardStrategyInterface
{
    /**
     * Get dashboard data for the specific user type
     */
    public function getDashboardData(User $user): array;

    /**
     * Get the view name for this dashboard type
     */
    public function getViewName(): string;

    /**
     * Get fallback data when dashboard service fails
     */
    public function getFallbackData(): array;
}
