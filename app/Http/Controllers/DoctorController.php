<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DoctorService;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DoctorController extends Controller
{
    protected $doctorService;

    public function __construct(DoctorService $doctorService)
    {
        $this->doctorService = $doctorService;
    }
    /**
     * Show the form for creating a new doctor
     */
    public function create()
    {
        return view('doctors.create');
    }

    /**
     * Store a newly created doctor in storage
     */
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'required|string|max:20',
            'specialization' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->except('password', 'password_confirmation'));
        }

        $result = $this->doctorService->createDoctor($request->all());

        if ($result['success']) {
            return redirect()->route('admin.dashboard')
                ->with('success', $result['message']);
        } else {
            return redirect()->back()
                ->with('error', $result['error'])
                ->withInput($request->except('password', 'password_confirmation'));
        }
    }

    /**
     * Display a listing of doctors
     */
    public function index()
    {
        try {
            $result = $this->doctorService->getAllDoctors(10);
            
            if ($result['success']) {
                $doctors = $result['doctors'];
            } else {
                $doctors = collect();
            }
            
            return view('doctors.index', compact('doctors'));
        } catch (\Exception $e) {
            \Log::error('Doctor Index Error: ' . $e->getMessage());
            return redirect()->route('admin.dashboard')
                ->with('error', 'Failed to load doctors list.');
        }
    }

    /**
     * Display the specified doctor
     */
    public function show(User $doctor)
    {
        if ($doctor->role !== 'doctor') {
            abort(404);
        }
        
        return view('doctors.show', compact('doctor'));
    }

    /**
     * Show the form for editing the specified doctor
     */
    public function edit(User $doctor)
    {
        if ($doctor->role !== 'doctor') {
            abort(404);
        }
        
        return view('doctors.edit', compact('doctor'));
    }

    /**
     * Update the specified doctor in storage
     */
    public function update(Request $request, User $doctor)
    {
        if ($doctor->role !== 'doctor') {
            abort(404);
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $doctor->id,
            'phone' => 'required|string|max:20',
            'specialization' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $result = $this->doctorService->updateDoctor($doctor, $request->all());

        if ($result['success']) {
            return redirect()->route('admin.doctors.show', $doctor)
                ->with('success', $result['message']);
        } else {
            return redirect()->back()
                ->with('error', $result['error'])
                ->withInput();
        }
    }

    /**
     * Remove the specified doctor from storage
     */
    public function destroy(User $doctor)
    {
        if ($doctor->role !== 'doctor') {
            abort(404);
        }

        $result = $this->doctorService->deleteDoctor($doctor);

        if ($result['success']) {
            return redirect()->route('admin.dashboard')
                ->with('success', $result['message']);
        } else {
            return redirect()->back()
                ->with('error', $result['error']);
        }
    }

    // ==================== API METHODS ====================

    /**
     * API: Display a listing of doctors
     */
    public function apiIndex(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        
        try {
            $result = $this->doctorService->getAllDoctors($perPage);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => UserResource::collection($result['doctors']),
                    'meta' => [
                        'current_page' => $result['doctors']->currentPage(),
                        'last_page' => $result['doctors']->lastPage(),
                        'per_page' => $result['doctors']->perPage(),
                        'total' => $result['doctors']->total(),
                    ]
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['error']
                ], 400);
            }
        } catch (\Exception $e) {
            \Log::error('Doctor Index API Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load doctors list.'
            ], 500);
        }
    }

    /**
     * API: Store a newly created doctor
     */
    public function apiStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'required|string|max:20',
            'specialization' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->doctorService->createDoctor($request->all());

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => new UserResource($result['doctor'])
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], 400);
    }

    /**
     * API: Display the specified doctor
     */
    public function apiShow(User $doctor)
    {
        if ($doctor->role !== 'doctor') {
            return response()->json([
                'success' => false,
                'message' => 'Doctor not found.'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => new UserResource($doctor)
        ], 200);
    }

    /**
     * API: Update the specified doctor
     */
    public function apiUpdate(Request $request, User $doctor)
    {
        if ($doctor->role !== 'doctor') {
            return response()->json([
                'success' => false,
                'message' => 'Doctor not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $doctor->id,
            'phone' => 'required|string|max:20',
            'specialization' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->doctorService->updateDoctor($doctor, $request->all());

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => new UserResource($doctor->fresh())
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], 400);
    }

    /**
     * API: Remove the specified doctor
     */
    public function apiDestroy(User $doctor)
    {
        if ($doctor->role !== 'doctor') {
            return response()->json([
                'success' => false,
                'message' => 'Doctor not found.'
            ], 404);
        }

        $result = $this->doctorService->deleteDoctor($doctor);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message']
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], 400);
    }
}
