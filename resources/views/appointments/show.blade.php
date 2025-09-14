@extends('layouts.app')

@section('title', 'Appointment Details - Dental Clinic')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-calendar-check me-2"></i>Appointment Details</h4>
                <span class="badge bg-{{
                    $appointment->status === 'completed' ? 'success' :
                    ($appointment->status === 'cancelled' ? 'danger' :
                    ($appointment->status === 'confirmed' ? 'primary' : 'warning'))
                }} fs-6">
                    {{ ucfirst($appointment->status) }}
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-tooth me-2 text-primary"></i>Service</h6>
                        <p class="mb-3">{{ $appointment->service->name }}</p>

                        @if($appointment->service->description)
                            <h6><i class="fas fa-info-circle me-2 text-primary"></i>Description</h6>
                            <p class="mb-3">{{ $appointment->service->description }}</p>
                        @endif

                        <h6><i class="fas fa-calendar me-2 text-primary"></i>Date & Time</h6>
                        <p class="mb-3">
                            {{ $appointment->appointment_date->format('l, F d, Y') }}<br>
                            {{ $appointment->appointment_date->format('H:i') }}
                        </p>
                    </div>

                    <div class="col-md-6">
                        <h6><i class="fas fa-user me-2 text-primary"></i>Patient</h6>
                        <p class="mb-3">{{ $appointment->patient->name }}</p>

                        @if($appointment->doctor)
                            <h6><i class="fas fa-user-md me-2 text-primary"></i>Doctor</h6>
                            <p class="mb-3">Dr. {{ $appointment->doctor->name }}</p>
                        @else
                            <h6><i class="fas fa-user-md me-2 text-primary"></i>Doctor</h6>
                            <p class="mb-3"><span class="text-muted">To be assigned</span></p>
                        @endif

                        <h6><i class="fas fa-clock me-2 text-primary"></i>Created</h6>
                        <p class="mb-3">{{ $appointment->created_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>

                @if($appointment->notes)
                    <div class="mt-3">
                        <h6><i class="fas fa-sticky-note me-2 text-primary"></i>Notes</h6>
                        <div class="alert alert-light">
                            {{ $appointment->notes }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Payment Information -->
        @if($appointment->payment)
            <div class="card mb-3">
                <div class="card-header">
                    <h6><i class="fas fa-credit-card me-2"></i>Payment Information</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Amount:</span>
                        <strong>RM{{ number_format($appointment->payment->amount, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Status:</span>
                        <span class="badge bg-{{
                            $appointment->payment->status === 'paid' ? 'success' :
                            ($appointment->payment->status === 'failed' ? 'danger' : 'warning')
                        }}">
                            {{ ucfirst($appointment->payment->status) }}
                        </span>
                    </div>

                    @if(($appointment->payment->status === 'pending' || $appointment->payment->status === 'failed') && auth()->user()->isPatient())
                        <a href="{{ route('payments.show', $appointment) }}" class="btn btn-{{ $appointment->payment->status === 'failed' ? 'danger' : 'success' }} w-100">
                            <i class="fas fa-credit-card me-2"></i>
                            {{ $appointment->payment->status === 'failed' ? 'Retry Payment' : 'Make Payment' }}
                        </a>
                    @endif
                </div>
            </div>
        @endif

        <!-- Actions -->
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-cogs me-2"></i>Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('appointments.my') }}" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Appointments
                    </a>

                    @if($appointment->canBeCancelled() && auth()->user()->isPatient())
                        <form method="POST" action="{{ route('appointments.cancel', $appointment) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger w-100"
                                    onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                <i class="fas fa-times me-2"></i>Cancel Appointment
                            </button>
                        </form>
                    @endif

                    @if((auth()->user()->isDoctor() || auth()->user()->isAdmin()) && $appointment->status !== 'cancelled')
                        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#updateModal">
                            <i class="fas fa-edit me-2"></i>Update Status
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
@if((auth()->user()->isDoctor() || auth()->user()->isAdmin()) && $appointment->status !== 'cancelled')
    <div class="modal fade" id="updateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Appointment Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('appointments.update', $appointment) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="pending" {{ $appointment->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="confirmed" {{ $appointment->status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                <option value="completed" {{ $appointment->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="rescheduled" {{ $appointment->status === 'rescheduled' ? 'selected' : '' }}>Rescheduled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3">{{ $appointment->notes }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection
