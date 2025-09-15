<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DoctorController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public API routes (no authentication required)
Route::prefix('v1')->group(function () {
    // Authentication routes
    Route::post('/auth/login', [AuthController::class, 'apiLogin']);
    Route::post('/auth/register', [AuthController::class, 'apiRegister']);
    Route::post('/auth/logout', [AuthController::class, 'apiLogout']);
    
    // Health check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'message' => 'Dental Clinic API is running',
            'timestamp' => now()->toISOString()
        ]);
    });
});

// Protected API routes (authentication required)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // User profile
    Route::get('/user/profile', [AuthController::class, 'apiProfile']);
    Route::put('/user/profile', [AuthController::class, 'apiUpdateProfile']);
    
    // Dashboard routes
    Route::get('/dashboard', [DashboardController::class, 'apiIndex']);
    Route::get('/dashboard/patient', [DashboardController::class, 'apiPatient']);
    Route::get('/dashboard/doctor', [DashboardController::class, 'apiDoctor']);
    Route::get('/dashboard/admin', [DashboardController::class, 'apiAdmin']);
    
    // Appointment routes
    Route::get('/appointments', [AppointmentController::class, 'apiIndex']);
    Route::post('/appointments', [AppointmentController::class, 'apiStore']);
    Route::get('/appointments/{appointment}', [AppointmentController::class, 'apiShow']);
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'apiUpdate']);
    Route::delete('/appointments/{appointment}', [AppointmentController::class, 'apiDestroy']);
    Route::get('/appointments/my', [AppointmentController::class, 'apiMyAppointments']);
    Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'apiCancel']);
    Route::post('/appointments/{appointment}/reschedule', [AppointmentController::class, 'apiReschedule']);
    
    // Payment routes
    Route::get('/payments', [PaymentController::class, 'apiIndex']);
    Route::get('/payments/{appointment}', [PaymentController::class, 'apiShow']);
    Route::post('/payments/{payment}/process', [PaymentController::class, 'apiProcess']);
    Route::post('/payments/confirm', [PaymentController::class, 'apiConfirmPayment']);
    Route::get('/payments/revenue-report', [PaymentController::class, 'apiRevenueReport']);
    
    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'apiIndex']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'apiMarkAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'apiMarkAllAsRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'apiDestroy']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'apiUnreadCount']);
    
    // Doctor routes (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/doctors', [DoctorController::class, 'apiIndex']);
        Route::post('/doctors', [DoctorController::class, 'apiStore']);
        Route::get('/doctors/{doctor}', [DoctorController::class, 'apiShow']);
        Route::put('/doctors/{doctor}', [DoctorController::class, 'apiUpdate']);
        Route::delete('/doctors/{doctor}', [DoctorController::class, 'apiDestroy']);
    });
});

// Fallback route for undefined API endpoints
Route::fallback(function () {
    return response()->json([
        'error' => 'API endpoint not found',
        'message' => 'The requested API endpoint does not exist',
        'available_endpoints' => [
            'GET /api/v1/health',
            'POST /api/v1/auth/login',
            'POST /api/v1/auth/register',
            'GET /api/v1/dashboard',
            'GET /api/v1/appointments',
            'GET /api/v1/notifications',
        ]
    ], 404);
});
