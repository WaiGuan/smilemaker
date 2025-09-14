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
            'appointment_date' => 'required|date|after:now',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
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
