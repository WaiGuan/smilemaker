<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use App\Models\Payment;
use App\Models\Notification;
use App\Services\AppointmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    protected $appointmentService;

    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }
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

}
