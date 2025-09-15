<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use App\Models\Payment;
use App\Models\Notification;
use App\Services\PaymentApiService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AppointmentService
{
    protected $paymentApiService;

    public function __construct(PaymentApiService $paymentApiService)
    {
        $this->paymentApiService = $paymentApiService;
    }

    /**
     * Create a new appointment
     */
    public function createAppointment(array $data, User $patient): array
    {
        try {
            $service = Service::findOrFail($data['service_id']);

            // Use Factory Method Pattern to create appointment
            $appointment = $this->createAppointmentByService($service, $patient, $data);

            // Create payment if service is not free
            if (!$service->isFree()) {
                $this->createPayment($appointment, $service->price);
            }

            // Send notifications
            $this->sendAppointmentNotifications($appointment);

            return [
                'success' => true,
                'appointment' => $appointment,
                'message' => 'Appointment booked successfully!'
            ];

        } catch (\Exception $e) {
            Log::error('Appointment Service Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to create appointment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Factory Method Pattern: Create appointment based on service type
     */
    private function createAppointmentByService(Service $service, User $patient, array $data): Appointment
    {
        $appointmentData = [
            'patient_id' => $patient->id,
            'service_id' => $service->id,
            'appointment_date' => $data['appointment_date'],
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
        ];

        // For consultation services, auto-assign available doctor
        if ($service->name === 'Consultation' && empty($data['doctor_id'])) {
            $availableDoctor = $this->getAvailableDoctor($data['appointment_date']);
            $appointmentData['doctor_id'] = $availableDoctor ? $availableDoctor->id : null;
        } else {
            $appointmentData['doctor_id'] = $data['doctor_id'] ?? null;
        }

        return Appointment::create($appointmentData);
    }

    /**
     * Check for conflicting appointments
     */
    public function checkConflictingAppointment(int $doctorId, string $appointmentDate, int $excludeAppointmentId = null): array
    {
        try {
            $query = Appointment::where('doctor_id', $doctorId)
                ->where('appointment_date', $appointmentDate)
                ->where('status', '!=', 'cancelled');

            if ($excludeAppointmentId) {
                $query->where('id', '!=', $excludeAppointmentId);
            }

            $conflictingAppointment = $query->first();

            return [
                'success' => true,
                'has_conflict' => $conflictingAppointment !== null,
                'conflicting_appointment' => $conflictingAppointment
            ];

        } catch (\Exception $e) {
            Log::error('Check Conflicting Appointment Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to check for conflicting appointments: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if a specific doctor is available at the given time
     */
    public function checkDoctorAvailability(int $doctorId, string $appointmentDate): array
    {
        try {
            $doctor = User::find($doctorId);
            
            if (!$doctor || !$doctor->isDoctor()) {
                return [
                    'available' => false,
                    'message' => 'Doctor not found or invalid.'
                ];
            }

            // Check if doctor has any appointments at this time
            $hasAppointment = $doctor->doctorAppointments()
                ->where('appointment_date', $appointmentDate)
                ->where('status', '!=', 'cancelled')
                ->exists();

            if ($hasAppointment) {
                return [
                    'available' => false,
                    'message' => 'Doctor is not available at the selected time. Please choose a different time slot.'
                ];
            }

            return [
                'available' => true,
                'message' => 'Doctor is available at the selected time.'
            ];

        } catch (\Exception $e) {
            Log::error('Doctor Availability Check Error: ' . $e->getMessage());
            return [
                'available' => false,
                'message' => 'Unable to check doctor availability. Please try again.'
            ];
        }
    }

    /**
     * Get an available doctor for the given time slot
     */
    private function getAvailableDoctor(string $appointmentDate): ?User
    {
        $doctors = User::where('role', 'doctor')->get();

        foreach ($doctors as $doctor) {
            // Check if doctor has any appointments at this time
            $hasAppointment = $doctor->doctorAppointments()
                ->where('appointment_date', $appointmentDate)
                ->where('status', '!=', 'cancelled')
                ->exists();

            if (!$hasAppointment) {
                return $doctor;
            }
        }

        return null; // No available doctor found
    }

    /**
     * Create payment for paid services
     */
    private function createPayment(Appointment $appointment, float $amount): Payment
    {
        return Payment::create([
            'appointment_id' => $appointment->id,
            'amount' => $amount,
            'status' => 'pending',
        ]);
    }

    /**
     * Cancel an appointment
     */
    public function cancelAppointment(Appointment $appointment, User $user): array
    {
        try {
            // Check if user can cancel this appointment
            if (!$user->isAdmin() && $appointment->patient_id !== $user->id) {
                return [
                    'success' => false,
                    'error' => 'Unauthorized to cancel this appointment.'
                ];
            }

            // Check if appointment can be cancelled (at least 1 day before)
            if (!$appointment->canBeCancelled()) {
                return [
                    'success' => false,
                    'error' => 'Appointments can only be cancelled at least 1 day before the scheduled time.'
                ];
            }

            $appointment->update(['status' => 'cancelled']);

            // Send cancellation notifications
            $this->sendCancellationNotifications($appointment);

            return [
                'success' => true,
                'message' => 'Appointment cancelled successfully.'
            ];

        } catch (\Exception $e) {
            Log::error('Appointment Cancellation Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to cancel appointment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update appointment status and notes
     */
    public function updateAppointment(Appointment $appointment, array $data, User $user): array
    {
        try {
            // Only doctors and admin can update appointment details
            if (!$user->isAdmin() && !$user->isDoctor()) {
                return [
                    'success' => false,
                    'error' => 'Unauthorized to update appointment.'
                ];
            }

            $appointment->update([
                'status' => $data['status'],
                'notes' => $data['notes'] ?? $appointment->notes,
            ]);

            // Send status update notifications
            $this->sendStatusUpdateNotifications($appointment);

            return [
                'success' => true,
                'message' => 'Appointment updated successfully.'
            ];

        } catch (\Exception $e) {
            Log::error('Appointment Update Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to update appointment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get appointment with payment information from Payment API
     */
    public function getAppointmentWithPaymentInfo(int $appointmentId): array
    {
        try {
            // Get payment information from Payment Management API
            $paymentResult = $this->paymentApiService->getPaymentByAppointment($appointmentId);
            
            if (!$paymentResult['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to retrieve payment information: ' . $paymentResult['error']
                ];
            }

            // Get appointment details
            $appointment = Appointment::with(['service', 'patient', 'doctor'])->find($appointmentId);
            
            if (!$appointment) {
                return [
                    'success' => false,
                    'error' => 'Appointment not found'
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'appointment' => $appointment,
                    'payment' => $paymentResult['data'],
                    'payment_from_api' => true
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Get Appointment with Payment Info Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to retrieve appointment with payment information: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify payment eligibility for appointment
     */
    public function verifyAppointmentPaymentEligibility(int $userId, int $appointmentId): array
    {
        try {
            // Use Payment API to verify eligibility
            $eligibilityResult = $this->paymentApiService->verifyPaymentEligibility($userId, $appointmentId);
            
            if (!$eligibilityResult['success']) {
                return [
                    'success' => false,
                    'error' => 'Payment eligibility verification failed: ' . $eligibilityResult['error']
                ];
            }

            return [
                'success' => true,
                'data' => $eligibilityResult['data'],
                'message' => 'Payment eligibility verified successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Verify Appointment Payment Eligibility Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to verify payment eligibility: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user's appointments based on role
     */
    public function getUserAppointments(User $user): array
    {
        try {
            if ($user->isPatient()) {
                $appointments = $user->patientAppointments()->with(['service', 'doctor', 'payment'])->get();
            } elseif ($user->isDoctor()) {
                $appointments = $user->doctorAppointments()->with(['service', 'patient', 'payment'])->get();
            } else {
                // Admin can see all appointments
                $appointments = Appointment::with(['service', 'patient', 'doctor', 'payment'])->get();
            }

            return [
                'success' => true,
                'appointments' => $appointments
            ];

        } catch (\Exception $e) {
            Log::error('Get User Appointments Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to retrieve appointments: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if user can view appointment
     */
    public function canViewAppointment(Appointment $appointment, User $user): bool
    {
        return $user->isAdmin() || 
               $appointment->patient_id === $user->id || 
               $appointment->doctor_id === $user->id;
    }

    /**
     * Get appointment statistics
     */
    public function getAppointmentStats(): array
    {
        try {
            $totalAppointments = Appointment::count();
            $todayAppointments = Appointment::whereDate('appointment_date', today())->count();
            $pendingAppointments = Appointment::where('status', 'pending')->count();
            $completedAppointments = Appointment::where('status', 'completed')->count();
            $cancelledAppointments = Appointment::where('status', 'cancelled')->count();

            return [
                'success' => true,
                'stats' => [
                    'total' => $totalAppointments,
                    'today' => $todayAppointments,
                    'pending' => $pendingAppointments,
                    'completed' => $completedAppointments,
                    'cancelled' => $cancelledAppointments,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Get Appointment Stats Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to retrieve appointment statistics: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send notifications when appointment is booked
     */
    private function sendAppointmentNotifications(Appointment $appointment): void
    {
        $message = "New appointment booked for {$appointment->service->name} on {$appointment->appointment_date->format('M d, Y H:i')}";

        // Notify patient
        Notification::create([
            'user_id' => $appointment->patient_id,
            'message' => "Your appointment for {$appointment->service->name} has been booked for {$appointment->appointment_date->format('M d, Y H:i')}",
        ]);

        // Notify doctor if assigned
        if ($appointment->doctor_id) {
            Notification::create([
                'user_id' => $appointment->doctor_id,
                'message' => "You have a new appointment with {$appointment->patient->name} for {$appointment->service->name} on {$appointment->appointment_date->format('M d, Y H:i')}",
            ]);
        }

        // Notify admin
        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            Notification::create([
                'user_id' => $admin->id,
                'message' => $message,
            ]);
        }
    }

    /**
     * Send notifications when appointment is cancelled
     */
    private function sendCancellationNotifications(Appointment $appointment): void
    {
        $message = "Appointment cancelled for {$appointment->service->name} on {$appointment->appointment_date->format('M d, Y H:i')}";

        // Notify patient
        Notification::create([
            'user_id' => $appointment->patient_id,
            'message' => "Your appointment for {$appointment->service->name} on {$appointment->appointment_date->format('M d, Y H:i')} has been cancelled",
        ]);

        // Notify doctor if assigned
        if ($appointment->doctor_id) {
            Notification::create([
                'user_id' => $appointment->doctor_id,
                'message' => "Appointment with {$appointment->patient->name} for {$appointment->service->name} on {$appointment->appointment_date->format('M d, Y H:i')} has been cancelled",
            ]);
        }

        // Notify admin
        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            Notification::create([
                'user_id' => $admin->id,
                'message' => $message,
            ]);
        }
    }

    /**
     * Send notifications when appointment status is updated
     */
    private function sendStatusUpdateNotifications(Appointment $appointment): void
    {
        $message = "Appointment status updated to {$appointment->status} for {$appointment->service->name} on {$appointment->appointment_date->format('M d, Y H:i')}";

        // Notify patient
        Notification::create([
            'user_id' => $appointment->patient_id,
            'message' => "Your appointment for {$appointment->service->name} on {$appointment->appointment_date->format('M d, Y H:i')} status has been updated to {$appointment->status}",
        ]);
    }
}
