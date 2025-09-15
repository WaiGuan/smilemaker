{{-- Author: Pooi Wai Guan --}}
@extends('layouts.app')

@section('title', 'Manage Doctors - Dental Clinic')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-user-md me-2"></i>Manage Doctors</h2>
    <div>
        <a href="{{ route('admin.doctors.create') }}" class="btn btn-primary">
            <i class="fas fa-user-plus me-2"></i>Add New Doctor
        </a>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($doctors->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Specialization</th>
                            <th>License Number</th>
                            <th>Appointments</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($doctors as $doctor)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-md text-primary me-2"></i>
                                        Dr. {{ $doctor->name }}
                                    </div>
                                </td>
                                <td>{{ $doctor->email }}</td>
                                <td>{{ $doctor->phone }}</td>
                                <td>
                                    @if($doctor->specialization)
                                        <span class="badge bg-info">{{ $doctor->specialization }}</span>
                                    @else
                                        <span class="text-muted">Not specified</span>
                                    @endif
                                </td>
                                <td>
                                    @if($doctor->license_number)
                                        {{ $doctor->license_number }}
                                    @else
                                        <span class="text-muted">Not provided</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $doctor->doctorAppointments()->count() }}</span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.doctors.show', $doctor) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.doctors.edit', $doctor) }}" class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.doctors.destroy', $doctor) }}" class="d-inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this doctor?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            @if($doctors->hasPages())
                <div class="d-flex justify-content-center">
                    {{ $doctors->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-5">
                <i class="fas fa-user-md fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No doctors found</h5>
                <p class="text-muted">Get started by adding your first doctor to the system.</p>
                <a href="{{ route('admin.doctors.create') }}" class="btn btn-primary">
                    <i class="fas fa-user-plus me-2"></i>Add First Doctor
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
