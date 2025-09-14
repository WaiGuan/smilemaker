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

@if($appointments->count() > 0)
    <div class="row">
        @foreach($appointments as $appointment)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">{{ $appointment->service->name }}</h6>
                        <span class="badge bg-{{
                            $appointment->status === 'completed' ? 'success' :
                            ($appointment->status === 'cancelled' ? 'danger' :
                            ($appointment->status === 'confirmed' ? 'primary' : 'warning'))
                        }}">
                            {{ ucfirst($appointment->status) }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <i class="fas fa-calendar me-2 text-primary"></i>
                            <strong>{{ $appointment->appointment_date->format('M d, Y') }}</strong>
                        </div>
                        <div class="mb-2">
                            <i class="fas fa-clock me-2 text-primary"></i>
                            {{ $appointment->appointment_date->format('H:i') }}
                        </div>

                        @if(auth()->user()->isPatient() && $appointment->doctor)
                            <div class="mb-2">
                                <i class="fas fa-user-md me-2 text-primary"></i>
                                Dr. {{ $appointment->doctor->name }}
                            </div>
                        @elseif(auth()->user()->isDoctor() && $appointment->patient)
                            <div class="mb-2">
                                <i class="fas fa-user me-2 text-primary"></i>
                                {{ $appointment->patient->name }}
                            </div>
                        @endif

                        @if($appointment->notes)
                            <div class="mb-2">
                                <i class="fas fa-sticky-note me-2 text-primary"></i>
                                <small class="text-muted">{{ Str::limit($appointment->notes, 50) }}</small>
                            </div>
                        @endif

                        @if($appointment->payment)
                            <div class="mb-2">
                                <i class="fas fa-credit-card me-2 text-primary"></i>
                                <span class="badge bg-{{ $appointment->payment->status === 'paid' ? 'success' : 'warning' }}">
                                    {{ ucfirst($appointment->payment->status) }} - RM{{ number_format($appointment->payment->amount, 2) }}
                                </span>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>View
                            </a>

                            @if($appointment->canBeCancelled() && auth()->user()->isPatient())
                                <form method="POST" action="{{ route('appointments.cancel', $appointment) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </button>
                                </form>
                            @endif

                            @if($appointment->payment && $appointment->payment->status === 'pending' && auth()->user()->isPatient())
                                <a href="{{ route('payments.show', $appointment) }}" class="btn btn-sm btn-success">
                                    <i class="fas fa-credit-card me-1"></i>Pay
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="text-center py-5">
        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
        <h4 class="text-muted">No appointments found</h4>
        @if(auth()->user()->isAdmin())
            <p class="text-muted">No appointments have been booked yet.</p>
        @elseif(auth()->user()->isDoctor())
            <p class="text-muted">You don't have any appointments scheduled yet.</p>
        @else
            <p class="text-muted">You haven't booked any appointments yet.</p>
            <a href="{{ route('appointments.index') }}" class="btn btn-primary">
                <i class="fas fa-calendar-plus me-2"></i>Book Your First Appointment
            </a>
        @endif
    </div>
@endif
@endsection
