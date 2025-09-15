{{-- Author: Foo Tek Sian --}}
@extends('layouts.app')

@section('title', 'Admin Dashboard - Dental Clinic')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-cogs me-2"></i>Admin Dashboard</h2>
    <div>
        <a href="{{ route('admin.doctors.index') }}" class="btn btn-primary me-2">
            <i class="fas fa-user-md me-2"></i>Manage Doctors
        </a>
        <a href="{{ route('admin.revenue') }}" class="btn btn-success">
            <i class="fas fa-chart-line me-2"></i>Revenue Report
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                <h5 class="card-title">{{ $totalPatients }}</h5>
                <p class="card-text">Total Patients</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-user-md fa-2x text-success mb-2"></i>
                <h5 class="card-title">{{ $totalDoctors }}</h5>
                <p class="card-text">Total Doctors</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-calendar-alt fa-2x text-info mb-2"></i>
                <h5 class="card-title">{{ $totalAppointments }}</h5>
                <p class="card-text">Total Appointments</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-coins fa-2x text-warning mb-2"></i>
                <h5 class="card-title">RM{{ number_format($totalRevenue, 2) }}</h5>
                <p class="card-text">Total Revenue</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Today's Appointments -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-calendar-day me-2"></i>Today's Appointments</h5>
                <span class="badge bg-primary">{{ $todayAppointments->count() }}</span>
            </div>
            <div class="card-body">
                @if($todayAppointments->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($todayAppointments as $appointment)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">{{ $appointment->service->name }}</h6>
                                        <p class="mb-1">
                                            <i class="fas fa-user me-1"></i>{{ $appointment->patient->name }}
                                            @if($appointment->doctor)
                                                <i class="fas fa-user-md ms-2 me-1"></i>Dr. {{ $appointment->doctor->name }}
                                            @endif
                                        </p>
                                        <p class="mb-1">
                                            <i class="fas fa-clock me-1"></i>
                                            {{ $appointment->appointment_date->format('H:i') }}
                                        </p>
                                    </div>
                                    <div>
                                        <span class="badge bg-{{
                                            $appointment->status === 'completed' ? 'success' :
                                            ($appointment->status === 'cancelled' ? 'danger' :
                                            ($appointment->status === 'confirmed' ? 'info' : 
                                            ($appointment->status === 'pending' ? 'warning' : 'secondary')))
                                        }}">
                                            {{ ucfirst($appointment->status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-center">No appointments scheduled for today</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Pending Payments -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-credit-card me-2"></i>Pending Payments</h5>
                <a href="{{ route('admin.payments') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                @if($pendingPayments->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($pendingPayments as $payment)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">{{ $payment->appointment->service->name }}</h6>
                                        <p class="mb-1">
                                            <i class="fas fa-user me-1"></i>{{ $payment->appointment->patient->name }}
                                        </p>
                                        <p class="mb-1">
                                            <i class="fas fa-calendar me-1"></i>
                                            {{ $payment->appointment->appointment_date->format('M d, Y H:i') }}
                                        </p>
                                    </div>
                                    <div class="text-end">
                                        <h6 class="text-warning">RM{{ number_format($payment->amount, 2) }}</h6>
                                        <span class="badge bg-warning">Pending</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-center">No pending payments</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Pending Appointments -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-clock me-2"></i>Pending Appointments</h5>
                <a href="{{ route('appointments.my') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                @if($recentAppointments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Service</th>
                                    <th>Doctor</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentAppointments as $appointment)
                                    <tr>
                                        <td>{{ $appointment->patient->name }}</td>
                                        <td>{{ $appointment->service->name }}</td>
                                        <td>{{ $appointment->doctor ? 'Dr. ' . $appointment->doctor->name : 'Auto-assigned' }}</td>
                                        <td>{{ $appointment->appointment_date->format('M d, Y H:i') }}</td>
                                        <td>
                                            <span class="badge bg-{{
                                                $appointment->status === 'completed' ? 'success' :
                                                ($appointment->status === 'cancelled' ? 'danger' : 'warning')
                                            }}">
                                                {{ ucfirst($appointment->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center">No pending appointments</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
