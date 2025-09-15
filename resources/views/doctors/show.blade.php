{{-- Author: Pooi Wai Guan --}}
@extends('layouts.app')

@section('title', 'Doctor Details - Dental Clinic')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-user-md me-2"></i>Doctor Details</h2>
    <div>
        <a href="{{ route('admin.doctors.edit', $doctor) }}" class="btn btn-warning me-2">
            <i class="fas fa-edit me-2"></i>Edit Doctor
        </a>
        <a href="{{ route('admin.doctors.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Doctors
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-user me-2"></i>Doctor Information</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <i class="fas fa-user-md fa-4x text-primary"></i>
                </div>
                
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Name:</strong></td>
                        <td>Dr. {{ $doctor->name }}</td>
                    </tr>
                    <tr>
                        <td><strong>Email:</strong></td>
                        <td>{{ $doctor->email }}</td>
                    </tr>
                    <tr>
                        <td><strong>Phone:</strong></td>
                        <td>{{ $doctor->phone }}</td>
                    </tr>
                    <tr>
                        <td><strong>Specialization:</strong></td>
                        <td>
                            @if($doctor->specialization)
                                <span class="badge bg-info">{{ $doctor->specialization }}</span>
                            @else
                                <span class="text-muted">Not specified</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>License Number:</strong></td>
                        <td>
                            @if($doctor->license_number)
                                {{ $doctor->license_number }}
                            @else
                                <span class="text-muted">Not provided</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Joined:</strong></td>
                        <td>{{ $doctor->created_at->format('M d, Y') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-calendar-alt me-2"></i>Recent Appointments</h5>
                <span class="badge bg-primary">{{ $doctor->doctorAppointments()->count() }} Total</span>
            </div>
            <div class="card-body">
                @if($doctor->doctorAppointments()->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Service</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($doctor->doctorAppointments()->latest()->take(10)->get() as $appointment)
                                    <tr>
                                        <td>{{ $appointment->patient->name }}</td>
                                        <td>{{ $appointment->service->name }}</td>
                                        <td>{{ $appointment->appointment_date->format('M d, Y H:i') }}</td>
                                        <td>
                                            <span class="badge bg-{{
                                                $appointment->status === 'completed' ? 'success' :
                                                ($appointment->status === 'cancelled' ? 'danger' :
                                                ($appointment->status === 'confirmed' ? 'info' : 
                                                ($appointment->status === 'pending' ? 'warning' : 'secondary')))
                                            }}">
                                                {{ ucfirst($appointment->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-2x text-muted mb-3"></i>
                        <p class="text-muted">No appointments found for this doctor.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
