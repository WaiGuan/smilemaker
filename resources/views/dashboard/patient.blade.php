{{-- Author: Foo Tek Sian --}}
@extends('layouts.app')

@section('title', 'Patient Dashboard - Dental Clinic')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-home me-2"></i>Patient Dashboard</h2>
    <div>
        <a href="{{ route('appointments.index') }}" class="btn btn-primary">
            <i class="fas fa-calendar-plus me-2"></i>Book New Appointment
        </a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-calendar-check fa-2x text-primary mb-2"></i>
                <h5 class="card-title">{{ $upcomingAppointments->count() }}</h5>
                <p class="card-text">Upcoming Appointments</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-bell fa-2x text-warning mb-2"></i>
                <h5 class="card-title">{{ $unreadNotifications }}</h5>
                <p class="card-text">Unread Notifications</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-user-md fa-2x text-success mb-2"></i>
                <h5 class="card-title">{{ $recentAppointments->count() }}</h5>
                <p class="card-text">Total Appointments</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-clock fa-2x text-info mb-2"></i>
                <h5 class="card-title">{{ $recentAppointments->where('status', 'completed')->count() }}</h5>
                <p class="card-text">Completed</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-calendar-alt me-2"></i>Upcoming Appointments</h5>
                <a href="{{ route('appointments.my') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                @if($upcomingAppointments->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($upcomingAppointments as $appointment)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">{{ $appointment->service->name }}</h6>
                                        <p class="mb-1">
                                            <i class="fas fa-calendar me-1"></i>
                                            {{ $appointment->appointment_date->format('M d, Y') }}
                                            <i class="fas fa-clock ms-2 me-1"></i>
                                            {{ $appointment->appointment_date->format('H:i') }}
                                        </p>
                                        @if($appointment->doctor)
                                            <small class="text-muted">
                                                <i class="fas fa-user-md me-1"></i>Dr. {{ $appointment->doctor->name }}
                                            </small>
                                        @endif
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
                    <p class="text-muted text-center">No upcoming appointments</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-history me-2"></i>Recent Appointments</h5>
                <a href="{{ route('appointments.my') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                @if($recentAppointments->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($recentAppointments->take(5) as $appointment)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">{{ $appointment->service->name }}</h6>
                                        <p class="mb-1">
                                            <i class="fas fa-calendar me-1"></i>
                                            {{ $appointment->appointment_date->format('M d, Y H:i') }}
                                        </p>
                                        @if($appointment->doctor)
                                            <small class="text-muted">
                                                <i class="fas fa-user-md me-1"></i>Dr. {{ $appointment->doctor->name }}
                                            </small>
                                        @endif
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
                    <p class="text-muted text-center">No recent appointments</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
