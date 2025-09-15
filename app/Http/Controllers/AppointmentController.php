<?php

/// Author: Yuen Yun Jia & Foo Tek Sian

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use App\Models\Payment;
use App\Models\Notification;
use App\Services\AppointmentService;
use App\Services\ServiceService;
use App\Http\Resources\AppointmentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    protected $appointmentService;
    protected $serviceService;

    public function __construct(AppointmentService $appointmentService, ServiceService $serviceService)
    {
        $this->appointmentService = $appointmentService;
        $this->serviceService = $serviceService;
    }
    /**
     * Show available services for booking
     */
    public function index()
    {
        $result = $this->serviceService->getAllServices();
        
        if ($result['success']) {
            $services = $result['services'];
        } else {
            $services = collect();
        }
        
        return view('appointments.index', compact('services'));
    }

    /**
     * Show the appointment booking form
     */
    public function create(Request $request)
    {
        $serviceId = $request->get('service_id');
        
        $serviceResult = $this->serviceService->getServiceById($serviceId);
        if (!$serviceResult['success']) {
            abort(404, 'Service not found.');
        }
        $service = $serviceResult['service'];
        
        $doctorsResult = $this->serviceService->getAvailableDoctors();
        if ($doctorsResult['success']) {
            $doctors = $doctorsResult['doctors'];
        } else {
            $doctors = collect();
        }

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
            'appointment_date' => [
                'required',
                'date',
                'after:' . now()->addHour()->format('Y-m-d H:i:s'),
                'before:' . now()->addMonths(3)->format('Y-m-d H:i:s'),
                function ($attribute, $value, $fail) {
                    $appointmentDate = Carbon::parse($value);
                    
                    // Check business hours (8 AM to 6 PM) - applies to all days including weekends
                    $hour = $appointmentDate->hour;
                    if ($hour < 8 || $hour >= 18) {
                        $fail('Appointments can only be booked between 8:00 AM and 6:00 PM.');
                        return;
                    }
                    
                    // Check if appointment is at least 1 hour from now
                    if ($appointmentDate->isPast() || now()->diffInHours($appointmentDate) < 1) {
                        $fail('Appointments must be booked at least 1 hour in advance.');
                        return;
                    }
                    
                    // Check if appointment is not more than 3 months in advance
                    if ($appointmentDate->diffInMonths(now()) > 3) {
                        $fail('Appointments cannot be booked more than 3 months in advance.');
                        return;
                    }
                }
            ],
            'notes' => 'nullable|string|max:1000',
        ], [
            'appointment_date.after' => 'Appointment must be at least 1 hour from now.',
            'appointment_date.before' => 'Appointment cannot be more than 3 months in advance.',
            'service_id.required' => 'Please select a service.',
            'service_id.exists' => 'Selected service is invalid.',
            'doctor_id.exists' => 'Selected doctor is invalid.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Additional validation for doctor availability
        if ($request->doctor_id) {
            $doctorAvailability = $this->appointmentService->checkDoctorAvailability(
                $request->doctor_id, 
                $request->appointment_date
            );
            
            if (!$doctorAvailability['available']) {
                return redirect()->back()
                    ->with('error', $doctorAvailability['message'])
                    ->withInput();
            }
        }

        $patient = Auth::user();
        $result = $this->appointmentService->createAppointment($request->all(), $patient);

        if ($result['success']) {
            return redirect()->route('appointments.show', $result['appointment'])
                ->with('success', $result['message']);
        } else {
            return redirect()->back()
                ->with('error', $result['error'])
                ->withInput();
        }
    }

    /**
     * Show a specific appointment
     */
    public function show(Appointment $appointment)
    {
        $user = Auth::user();
        
        if (!$this->appointmentService->canViewAppointment($appointment, $user)) {
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
        $result = $this->appointmentService->getUserAppointments($user);

        if ($result['success']) {
            $appointments = $result['appointments'];
        } else {
            $appointments = collect();
        }

        return view('appointments.my-appointments', compact('appointments'));
    }

    /**
     * Cancel an appointment
     */
    public function cancel(Appointment $appointment)
    {
        $user = Auth::user();
        $result = $this->appointmentService->cancelAppointment($appointment, $user);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        } else {
            return redirect()->back()->with('error', $result['error']);
        }
    }

    /**
     * Update appointment status and notes (for doctors and admin)
     */
    public function update(Request $request, Appointment $appointment)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,confirmed,cancelled,completed,rescheduled',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $result = $this->appointmentService->updateAppointment($appointment, $request->all(), $user);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        } else {
            return redirect()->back()->with('error', $result['error']);
        }
    }

    // ==================== API METHODS ====================

    /**
     * API: Display a listing of appointments
     */
    public function apiIndex(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->get('per_page', 15);
        
        if ($user->isAdmin()) {
            $appointments = Appointment::with(['patient', 'doctor', 'service', 'payment'])
                ->orderBy('appointment_date', 'desc')
                ->paginate($perPage);
        } elseif ($user->isDoctor()) {
            $appointments = Appointment::with(['patient', 'doctor', 'service', 'payment'])
                ->where('doctor_id', $user->id)
                ->orderBy('appointment_date', 'desc')
                ->paginate($perPage);
        } else {
            $appointments = Appointment::with(['patient', 'doctor', 'service', 'payment'])
                ->where('patient_id', $user->id)
                ->orderBy('appointment_date', 'desc')
                ->paginate($perPage);
        }

        return response()->json([
            'success' => true,
            'data' => AppointmentResource::collection($appointments),
            'meta' => [
                'current_page' => $appointments->currentPage(),
                'last_page' => $appointments->lastPage(),
                'per_page' => $appointments->perPage(),
                'total' => $appointments->total(),
            ]
        ], 200);
    }

    /**
     * API: Store a newly created appointment
     */
    public function apiStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id',
            'doctor_id' => 'nullable|exists:users,id',
            'appointment_date' => [
                'required',
                'date',
                'after:' . now()->addHour()->format('Y-m-d H:i:s'),
                'before:' . now()->addMonths(3)->format('Y-m-d H:i:s'),
                function ($attribute, $value, $fail) {
                    $appointmentDate = Carbon::parse($value);
                    
                    $hour = $appointmentDate->hour;
                    if ($hour < 8 || $hour >= 18) {
                        $fail('Appointments can only be booked between 8:00 AM and 6:00 PM.');
                        return;
                    }
                    
                    if ($appointmentDate->isPast() || now()->diffInHours($appointmentDate) < 1) {
                        $fail('Appointments must be booked at least 1 hour in advance.');
                        return;
                    }
                    
                    if ($appointmentDate->diffInMonths(now()) > 3) {
                        $fail('Appointments cannot be booked more than 3 months in advance.');
                        return;
                    }
                }
            ],
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->doctor_id) {
            $doctorAvailability = $this->appointmentService->checkDoctorAvailability(
                $request->doctor_id, 
                $request->appointment_date
            );
            
            if (!$doctorAvailability['available']) {
                return response()->json([
                    'success' => false,
                    'message' => $doctorAvailability['message']
                ], 400);
            }
        }

        $patient = Auth::user();
        $result = $this->appointmentService->createAppointment($request->all(), $patient);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => new AppointmentResource($result['appointment']->load(['patient', 'doctor', 'service', 'payment']))
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], 400);
    }

    /**
     * API: Display the specified appointment
     */
    public function apiShow(Appointment $appointment)
    {
        $user = Auth::user();
        
        if (!$this->appointmentService->canViewAppointment($appointment, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to appointment.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new AppointmentResource($appointment->load(['patient', 'doctor', 'service', 'payment']))
        ], 200);
    }

    /**
     * API: Update the specified appointment
     */
    public function apiUpdate(Request $request, Appointment $appointment)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,confirmed,cancelled,completed,rescheduled',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->appointmentService->updateAppointment($appointment, $request->all(), $user);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => new AppointmentResource($appointment->fresh()->load(['patient', 'doctor', 'service', 'payment']))
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], 400);
    }

    /**
     * API: Remove the specified appointment
     */
    public function apiDestroy(Appointment $appointment)
    {
        $user = Auth::user();
        
        if (!$user->isAdmin() && $appointment->patient_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this appointment.'
            ], 403);
        }

        $appointment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Appointment deleted successfully.'
        ], 200);
    }

    /**
     * API: Get user's appointments
     */
    public function apiMyAppointments(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status');
        
        $result = $this->appointmentService->getUserAppointments($user, $perPage, $status);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => AppointmentResource::collection($result['appointments']),
                'meta' => [
                    'current_page' => $result['appointments']->currentPage(),
                    'last_page' => $result['appointments']->lastPage(),
                    'per_page' => $result['appointments']->perPage(),
                    'total' => $result['appointments']->total(),
                ]
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], 400);
    }

    /**
     * API: Cancel an appointment
     */
    public function apiCancel(Appointment $appointment)
    {
        $user = Auth::user();
        $result = $this->appointmentService->cancelAppointment($appointment, $user);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => new AppointmentResource($appointment->fresh()->load(['patient', 'doctor', 'service', 'payment']))
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], 400);
    }

    /**
     * API: Get appointment with payment information from Payment API
     */
    public function apiGetAppointmentWithPaymentInfo(Appointment $appointment)
    {
        $user = Auth::user();
        
        // Check authorization
        if (!$user->isAdmin() && $appointment->patient_id !== $user->id && $appointment->doctor_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to appointment information.'
            ], 403);
        }

        $result = $this->appointmentService->getAppointmentWithPaymentInfo($appointment->id);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * API: Verify payment eligibility for appointment
     */
    public function apiVerifyPaymentEligibility(Request $request, Appointment $appointment)
    {
        $user = Auth::user();
        
        // Check authorization
        if (!$user->isAdmin() && $appointment->patient_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to verify payment eligibility for this appointment.'
            ], 403);
        }

        $result = $this->appointmentService->verifyAppointmentPaymentEligibility($user->id, $appointment->id);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * API: Reschedule an appointment
     */
    public function apiReschedule(Request $request, Appointment $appointment)
    {
        $user = Auth::user();

        if (!$user->isPatient() || $appointment->patient_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to reschedule this appointment.'
            ], 403);
        }

        if (!$appointment->canBeRescheduled()) {
            return response()->json([
                'success' => false,
                'message' => 'This appointment cannot be rescheduled.'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'new_appointment_date' => 'required|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $newDate = Carbon::parse($request->new_appointment_date);

        if ($newDate->hour < 8 || $newDate->hour >= 18) {
            return response()->json([
                'success' => false,
                'message' => 'Appointments can only be scheduled between 8:00 AM and 6:00 PM.'
            ], 400);
        }

        if (now()->diffInHours($newDate) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Appointments must be booked at least 1 hour in advance.'
            ], 400);
        }

        if ($newDate->diffInDays(now()) > 90) {
            return response()->json([
                'success' => false,
                'message' => 'Appointments can only be booked up to 3 months in advance.'
            ], 400);
        }

        $conflictResult = $this->appointmentService->checkConflictingAppointment(
            $appointment->doctor_id, 
            $newDate, 
            $appointment->id
        );

        if ($conflictResult['success'] && $conflictResult['has_conflict']) {
            return response()->json([
                'success' => false,
                'message' => 'The selected time slot is not available. Please choose another time.'
            ], 400);
        }

        $appointment->update([
            'appointment_date' => $newDate,
            'status' => 'rescheduled',
            'notes' => $appointment->notes . "\n\nRescheduled on " . now()->format('Y-m-d H:i:s') . " to " . $newDate->format('Y-m-d H:i:s'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Appointment has been successfully rescheduled.',
            'data' => new AppointmentResource($appointment->fresh()->load(['patient', 'doctor', 'service', 'payment']))
        ], 200);
    }

}
