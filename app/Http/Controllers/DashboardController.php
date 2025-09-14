<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }
    /**
     * Show patient dashboard
     */
    public function patient()
    {
        $user = Auth::user();
        $result = $this->dashboardService->getPatientDashboard($user);

        if ($result['success']) {
            $data = $result['data'];
            return view('dashboard.patient', $data);
        } else {
            return view('dashboard.patient', [
                'upcomingAppointments' => collect(),
                'recentAppointments' => collect(),
                'unreadNotifications' => 0,
                'appointmentStats' => []
            ]);
        }
    }

    /**
     * Show doctor dashboard
     */
    public function doctor()
    {
        $user = Auth::user();
        $result = $this->dashboardService->getDoctorDashboard($user);

        if ($result['success']) {
            $data = $result['data'];
            return view('dashboard.doctor', $data);
        } else {
            return view('dashboard.doctor', [
                'todayAppointments' => collect(),
                'upcomingAppointments' => collect(),
                'unreadNotifications' => 0,
                'doctorStats' => []
            ]);
        }
    }

    /**
     * Show admin dashboard
     */
    public function admin()
    {
        $user = Auth::user();
        $result = $this->dashboardService->getAdminDashboard($user);

        if ($result['success']) {
            $data = $result['data'];
            return view('dashboard.admin', $data);
        } else {
            return view('dashboard.admin', [
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
            ]);
        }
    }
}
