<?php

/// Author: Foo Tek Sian

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\DashboardStrategies\DashboardStrategyFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected $dashboardService;
    protected $strategyFactory;

    public function __construct(DashboardService $dashboardService, DashboardStrategyFactory $strategyFactory)
    {
        $this->dashboardService = $dashboardService;
        $this->strategyFactory = $strategyFactory;
    }
    /**
     * Show patient welcome page
     */
    public function patient()
    {
        return view('patient.welcome');
    }

    /**
     * Show doctor dashboard
     */
    public function doctor()
    {
        $user = Auth::user();
        $dashboardView = $this->strategyFactory->getDashboardView($user);
        
        return view($dashboardView['view'], $dashboardView['data']);
    }

    /**
     * Show admin dashboard
     */
    public function admin()
    {
        $user = Auth::user();
        $dashboardView = $this->strategyFactory->getDashboardView($user);
        
        return view($dashboardView['view'], $dashboardView['data']);
    }

    /**
     * Generic dashboard method that automatically determines the user's role
     * and shows the appropriate dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $dashboardView = $this->strategyFactory->getDashboardView($user);
        
        return view($dashboardView['view'], $dashboardView['data']);
    }

    // ==================== API METHODS ====================

    /**
     * API: Get dashboard data based on user role
     */
    public function apiIndex()
    {
        $user = Auth::user();
        $dashboardData = $this->strategyFactory->getDashboardData($user);
        
        return response()->json([
            'success' => true,
            'data' => $dashboardData
        ], 200);
    }

    /**
     * API: Get patient dashboard data
     */
    public function apiPatient()
    {
        $user = Auth::user();
        
        if (!$user->isPatient()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to patient dashboard.'
            ], 403);
        }

        $dashboardData = $this->strategyFactory->getDashboardData($user);
        
        return response()->json([
            'success' => true,
            'data' => $dashboardData
        ], 200);
    }

    /**
     * API: Get doctor dashboard data
     */
    public function apiDoctor()
    {
        $user = Auth::user();
        
        if (!$user->isDoctor()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to doctor dashboard.'
            ], 403);
        }

        $dashboardData = $this->strategyFactory->getDashboardData($user);
        
        return response()->json([
            'success' => true,
            'data' => $dashboardData
        ], 200);
    }

    /**
     * API: Get admin dashboard data
     */
    public function apiAdmin()
    {
        $user = Auth::user();
        
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to admin dashboard.'
            ], 403);
        }

        $dashboardData = $this->strategyFactory->getDashboardData($user);
        
        return response()->json([
            'success' => true,
            'data' => $dashboardData
        ], 200);
    }
}
