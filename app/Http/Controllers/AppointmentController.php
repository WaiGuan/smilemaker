<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use App\Models\Payment;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    /**
     * Show available services for booking
     */
    public function index()
    {
        $services = Service::all();
        return view('appointments.index', compact('services'));
    }

    /**
     * Show the appointment booking form
     */
    public function create(Request $request)
    {
        $serviceId = $request->get('service_id');
        $service = Service::findOrFail($serviceId);
        $doctors = User::where('role', 'doctor')->get();

        return view('appointments.create', compact('service', 'doctors'));
    }

    /**
     * Store a new appointment
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id',
            'doctor_id' => 'nullable|exists:users,id',
            'appointment_date' => 'required|date|after:now',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $service = Service::findOrFail($request->service_id);
        $patient = Auth::user();

        // Use Factory Method Pattern to create appointment
        $appointment = $this->createAppointment($service, $patient, $request);

        // Create payment if service is not free
        if (!$service->isFree()) {
            $this->createPayment($appointment, $service->price);
        }

        // Send notifications
        $this->sendAppointmentNotifications($appointment);

        return redirect()->route('appointments.show', $appointment)
            ->with('success', 'Appointment booked successfully!');
    }

    /**
     * Show a specific appointment
     */
    public function show(Appointment $appointment)
    {
        // Check if user can view this appointment
        $user = Auth::user();
        if (!$user->isAdmin() && $appointment->patient_id !== $user->id && $appointment->doctor_id !== $user->id) {
            abort(403, 'Unauthorized access to appointment.');
        }

        return view('appointments.show', compact('appointment'));
    }

    /**
     * Show user's appointments
     */
    public function myAppointments()
    {
        $user = Auth::user();

        if ($user->isPatient()) {
            $appointments = $user->patientAppointments()->with(['service', 'doctor', 'payment'])->get();
        } elseif ($user->isDoctor()) {
            $appointments = $user->doctorAppointments()->with(['service', 'patient', 'payment'])->get();
        } else {
            // Admin can see all appointments
            $appointments = Appointment::with(['service', 'patient', 'doctor', 'payment'])->get();
        }

        return view('appointments.my-appointments', compact('appointments'));
    }

    /**
     * Cancel an appointment
     */
    public function cancel(Appointment $appointment)
    {
        $user = Auth::user();

        // Check if user can cancel this appointment
        if (!$user->isAdmin() && $appointment->patient_id !== $user->id) {
            abort(403, 'Unauthorized to cancel this appointment.');
        }

        // Check if appointment can be cancelled (at least 1 day before)
        if (!$appointment->canBeCancelled()) {
            return redirect()->back()
                ->with('error', 'Appointments can only be cancelled at least 1 day before the scheduled time.');
        }

        $appointment->update(['status' => 'cancelled']);

        // Send cancellation notifications
        $this->sendCancellationNotifications($appointment);

        return redirect()->back()
            ->with('success', 'Appointment cancelled successfully.');
    }

    /**
     * Update appointment status and notes (for doctors and admin)
     */
    public function update(Request $request, Appointment $appointment)
    {
        $user = Auth::user();

        // Only doctors and admin can update appointment details
        if (!$user->isAdmin() && !$user->isDoctor()) {
            abort(403, 'Unauthorized to update appointment.');
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,confirmed,cancelled,completed,rescheduled',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator);
        }

        $appointment->update([
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

        // Send status update notifications
        $this->sendStatusUpdateNotifications($appointment);

        return redirect()->back()
            ->with('success', 'Appointment updated successfully.');
    }

    /**
     * Factory Method Pattern: Create appointment based on service type
     */
    private function createAppointment($service, $patient, $request)
    {
        $appointmentData = [
            'patient_id' => $patient->id,
            'service_id' => $service->id,
            'appointment_date' => $request->appointment_date,
            'notes' => $request->notes,
            'status' => 'pending',
        ];

        // For consultation services, auto-assign available doctor
        if ($service->name === 'Consultation' && !$request->doctor_id) {
            $availableDoctor = $this->getAvailableDoctor($request->appointment_date);
            $appointmentData['doctor_id'] = $availableDoctor ? $availableDoctor->id : null;
        } else {
            $appointmentData['doctor_id'] = $request->doctor_id;
        }

        return Appointment::create($appointmentData);
    }

    /**
     * Get an available doctor for the given time slot
     */
    private function getAvailableDoctor($appointmentDate)
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
    private function createPayment($appointment, $amount)
    {
        Payment::create([
            'appointment_id' => $appointment->id,
            'amount' => $amount,
            'status' => 'pending',
        ]);
    }

    /**
     * Send notifications when appointment is booked
     */
    private function sendAppointmentNotifications($appointment)
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
    private function sendCancellationNotifications($appointment)
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
    private function sendStatusUpdateNotifications($appointment)
    {
        $message = "Appointment status updated to {$appointment->status} for {$appointment->service->name} on {$appointment->appointment_date->format('M d, Y H:i')}";

        // Notify patient
        Notification::create([
            'user_id' => $appointment->patient_id,
            'message' => "Your appointment for {$appointment->service->name} on {$appointment->appointment_date->format('M d, Y H:i')} status has been updated to {$appointment->status}",
        ]);
    }
}
