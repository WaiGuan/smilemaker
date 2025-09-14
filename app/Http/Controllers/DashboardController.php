<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Show patient dashboard
     */
    public function patient()
    {
        $user = Auth::user();

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

        return view('dashboard.patient', compact('upcomingAppointments', 'recentAppointments', 'unreadNotifications'));
    }

    /**
     * Show doctor dashboard
     */
    public function doctor()
    {
        $user = Auth::user();

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

        return view('dashboard.doctor', compact('todayAppointments', 'upcomingAppointments', 'unreadNotifications'));
    }

    /**
     * Show admin dashboard
     */
    public function admin()
    {
        $user = Auth::user();

        // Get statistics
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

        // Get recent appointments
        $recentAppointments = Appointment::with(['service', 'patient', 'doctor'])
            ->orderBy('created_at', 'desc')
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

        return view('dashboard.admin', compact(
            'totalPatients',
            'totalDoctors',
            'totalAppointments',
            'totalRevenue',
            'todayAppointments',
            'recentAppointments',
            'pendingPayments',
            'unreadNotifications'
        ));
    }
}
