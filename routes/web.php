<?php

/// Author: Tan Huei Qing, Yuen Yun Jia, Foo Tek Sian, Pooi Wai Guan

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DoctorController;

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Protected routes (require authentication)
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Profile routes
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');

    // Dashboard routes (role-based)
    Route::get('/patient/dashboard', [DashboardController::class, 'patient'])->name('patient.dashboard')->middleware('role:patient');
    Route::get('/doctor/dashboard', [DashboardController::class, 'doctor'])->name('doctor.dashboard')->middleware('role:doctor');
    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])->name('admin.dashboard')->middleware('role:admin');

    // Appointment routes
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');
    Route::get('/my-appointments', [AppointmentController::class, 'myAppointments'])->name('appointments.my');
    Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');

    // Payment routes
    Route::get('/payments/demo', function () {
        return view('payments.demo');
    })->name('payments.demo');
    Route::get('/payments/{appointment}', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('/payments/{payment}/process', [PaymentController::class, 'process'])->name('payments.process');
    Route::post('/payments/confirm', [PaymentController::class, 'confirmPayment'])->name('payments.confirm');

    // Admin-only routes
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/payments', [PaymentController::class, 'index'])->name('admin.payments');
        Route::get('/admin/revenue-report', [PaymentController::class, 'revenueReport'])->name('admin.revenue');
        
        // Doctor management routes
        Route::get('/admin/doctors/create', [DoctorController::class, 'create'])->name('admin.doctors.create');
        Route::post('/admin/doctors', [DoctorController::class, 'store'])->name('admin.doctors.store');
        Route::get('/admin/doctors', [DoctorController::class, 'index'])->name('admin.doctors.index');
        Route::get('/admin/doctors/{doctor}', [DoctorController::class, 'show'])->name('admin.doctors.show');
        Route::get('/admin/doctors/{doctor}/edit', [DoctorController::class, 'edit'])->name('admin.doctors.edit');
        Route::put('/admin/doctors/{doctor}', [DoctorController::class, 'update'])->name('admin.doctors.update');
        Route::delete('/admin/doctors/{doctor}', [DoctorController::class, 'destroy'])->name('admin.doctors.destroy');
    });

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
});
