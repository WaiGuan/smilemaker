@extends('layouts.app')

@section('title', 'Doctor Dashboard - Dental Clinic')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-user-md me-2"></i>Doctor Dashboard</h2>
    <div>
        <a href="{{ route('appointments.my') }}" class="btn btn-primary">
            <i class="fas fa-calendar-alt me-2"></i>View All Appointments
        </a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-calendar-day fa-2x text-primary mb-2"></i>
                <h5 class="card-title">{{ $todayAppointments->count() }}</h5>
                <p class="card-text">Today's Appointments</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-calendar-check fa-2x text-success mb-2"></i>
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
                <i class="fas fa-check-circle fa-2x text-info mb-2"></i>
                <h5 class="card-title">{{ $todayAppointments->where('status', 'completed')->count() }}</h5>
                <p class="card-text">Completed Today</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
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
                                        </p>
                                        <p class="mb-1">
                                            <i class="fas fa-clock me-1"></i>
                                            {{ $appointment->appointment_date->format('H:i') }}
                                        </p>
                                        @if($appointment->notes)
                                            <small class="text-muted">
                                                <i class="fas fa-sticky-note me-1"></i>{{ Str::limit($appointment->notes, 50) }}
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
                                <div class="mt-2">
                                    <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </a>
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
                                            <i class="fas fa-user me-1"></i>{{ $appointment->patient->name }}
                                        </p>
                                        <p class="mb-1">
                                            <i class="fas fa-calendar me-1"></i>
                                            {{ $appointment->appointment_date->format('M d, Y') }}
                                            <i class="fas fa-clock ms-2 me-1"></i>
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
                    <p class="text-muted text-center">No upcoming appointments</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
