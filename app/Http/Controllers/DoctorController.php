<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DoctorController extends Controller
{
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

        // Create the doctor
        $doctor = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'doctor',
            'phone' => $request->phone,
            'specialization' => $request->specialization,
            'license_number' => $request->license_number,
        ]);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Doctor registered successfully!');
    }

    /**
     * Display a listing of doctors
     */
    public function index()
    {
        $doctors = User::where('role', 'doctor')->paginate(10);
        return view('doctors.index', compact('doctors'));
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

        // Update the doctor
        $doctor->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'specialization' => $request->specialization,
            'license_number' => $request->license_number,
        ]);

        return redirect()->route('admin.doctors.show', $doctor)
            ->with('success', 'Doctor updated successfully!');
    }

    /**
     * Remove the specified doctor from storage
     */
    public function destroy(User $doctor)
    {
        if ($doctor->role !== 'doctor') {
            abort(404);
        }

        // Check if doctor has any appointments
        if ($doctor->doctorAppointments()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Cannot delete doctor with existing appointments.');
        }

        $doctor->delete();

        return redirect()->route('doctors.index')
            ->with('success', 'Doctor deleted successfully!');
    }
}
