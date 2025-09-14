@extends('layouts.app')

@section('title', auth()->user()->isAdmin() ? 'Appointments - Dental Clinic' : 'My Appointments - Dental Clinic')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-calendar-alt me-2"></i>{{ auth()->user()->isAdmin() ? 'Appointments' : 'My Appointments' }}</h2>
    @if(auth()->user()->isPatient())
        <div>
            <a href="{{ route('appointments.index') }}" class="btn btn-primary">
                <i class="fas fa-calendar-plus me-2"></i>Book New Appointment
            </a>
        </div>
    @endif
</div>

<!-- Filter Tabs for All Users -->
<div class="row mb-4">
    <div class="col-12">
        <ul class="nav nav-tabs" id="appointmentTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button" role="tab">
                    <i class="fas fa-clock me-2"></i>Upcoming Appointments
                    <span class="badge bg-primary ms-2">{{ $appointments->filter(function($appointment) { return $appointment->appointment_date >= now() && $appointment->status !== 'completed' && $appointment->status !== 'cancelled'; })->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="past-tab" data-bs-toggle="tab" data-bs-target="#past" type="button" role="tab">
                    <i class="fas fa-history me-2"></i>Past Appointments
                    <span class="badge bg-secondary ms-2">{{ $appointments->filter(function($appointment) { return $appointment->appointment_date < now() || $appointment->status === 'completed' || $appointment->status === 'cancelled'; })->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                    <i class="fas fa-list me-2"></i>All Appointments
                    <span class="badge bg-info ms-2">{{ $appointments->count() }}</span>
                </button>
            </li>
        </ul>
    </div>
</div>

<!-- Tab Content for All Users -->
<div class="tab-content" id="appointmentTabsContent">
    <!-- Upcoming Appointments Tab -->
    <div class="tab-pane fade show active" id="upcoming" role="tabpanel">
        @php 
            $upcomingAppointments = $appointments->filter(function($appointment) { 
                return $appointment->appointment_date >= now() && 
                       $appointment->status !== 'completed' && 
                       $appointment->status !== 'cancelled'; 
            })->sortBy('appointment_date'); 
        @endphp
        @if($upcomingAppointments->count() > 0)
            <div class="row">
                @foreach($upcomingAppointments as $appointment)
                    @include('appointments.partials.appointment-card', ['appointment' => $appointment])
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No upcoming appointments</h4>
                @if(auth()->user()->isPatient())
                    <p class="text-muted">You don't have any upcoming appointments scheduled.</p>
                    <a href="{{ route('appointments.index') }}" class="btn btn-primary">
                        <i class="fas fa-calendar-plus me-2"></i>Book New Appointment
                    </a>
                @elseif(auth()->user()->isDoctor())
                    <p class="text-muted">You don't have any upcoming appointments scheduled.</p>
                @elseif(auth()->user()->isAdmin())
                    <p class="text-muted">No upcoming appointments have been scheduled.</p>
                @endif
            </div>
        @endif
    </div>

    <!-- Past Appointments Tab -->
    <div class="tab-pane fade" id="past" role="tabpanel">
        @php 
            $pastAppointments = $appointments->filter(function($appointment) { 
                return $appointment->appointment_date < now() || 
                       $appointment->status === 'completed' || 
                       $appointment->status === 'cancelled'; 
            })->sortByDesc('appointment_date'); 
        @endphp
        @if($pastAppointments->count() > 0)
            <div class="row">
                @foreach($pastAppointments as $appointment)
                    @include('appointments.partials.appointment-card', ['appointment' => $appointment])
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No past appointments</h4>
                @if(auth()->user()->isPatient())
                    <p class="text-muted">You haven't had any appointments yet.</p>
                @elseif(auth()->user()->isDoctor())
                    <p class="text-muted">You haven't completed any appointments yet.</p>
                @elseif(auth()->user()->isAdmin())
                    <p class="text-muted">No appointments have been completed yet.</p>
                @endif
            </div>
        @endif
    </div>

    <!-- All Appointments Tab -->
    <div class="tab-pane fade" id="all" role="tabpanel">
        @if($appointments->count() > 0)
            <div class="row">
                @foreach($appointments->sortBy('appointment_date') as $appointment)
                    @include('appointments.partials.appointment-card', ['appointment' => $appointment])
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No appointments found</h4>
                @if(auth()->user()->isPatient())
                    <p class="text-muted">You haven't booked any appointments yet.</p>
                    <a href="{{ route('appointments.index') }}" class="btn btn-primary">
                        <i class="fas fa-calendar-plus me-2"></i>Book Your First Appointment
                    </a>
                @elseif(auth()->user()->isDoctor())
                    <p class="text-muted">You don't have any appointments scheduled yet.</p>
                @elseif(auth()->user()->isAdmin())
                    <p class="text-muted">No appointments have been booked yet.</p>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection