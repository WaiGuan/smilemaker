@extends('layouts.app')

@section('title', 'Book Appointment - Dental Clinic')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-calendar-plus me-2"></i>Book Appointment</h4>
            </div>
            <div class="card-body">
                <!-- Service Information -->
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>Service Details</h6>
                    <p class="mb-1"><strong>{{ $service->name }}</strong></p>
                    @if($service->description)
                        <p class="mb-1">{{ $service->description }}</p>
                    @endif
                    <p class="mb-0">
                        <strong>Price: </strong>
                        @if($service->isFree())
                            <span class="text-success">Free (Walk-in)</span>
                        @else
                            <span class="text-primary">RM{{ number_format($service->price, 2) }}</span>
                        @endif
                    </p>
                </div>

                <form method="POST" action="{{ route('appointments.store') }}">
                    @csrf
                    <input type="hidden" name="service_id" value="{{ $service->id }}">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="appointment_date" class="form-label">Appointment Date & Time</label>
                            <input type="datetime-local"
                                   class="form-control @error('appointment_date') is-invalid @enderror"
                                   id="appointment_date"
                                   name="appointment_date"
                                   value="{{ old('appointment_date') }}"
                                   min="{{ now()->format('Y-m-d\TH:i') }}"
                                   required>
                            @error('appointment_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Please select a date and time at least 1 hour from now.</div>
                        </div>

                        @if($service->name !== 'Consultation')
                            <div class="col-md-6 mb-3">
                                <label for="doctor_id" class="form-label">Select Doctor</label>
                                <select class="form-select @error('doctor_id') is-invalid @enderror"
                                        id="doctor_id" name="doctor_id">
                                    <option value="">Choose a doctor...</option>
                                    @foreach($doctors as $doctor)
                                        <option value="{{ $doctor->id }}" {{ old('doctor_id') == $doctor->id ? 'selected' : '' }}>
                                            Dr. {{ $doctor->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('doctor_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">For consultation, a doctor will be automatically assigned.</div>
                            </div>
                        @else
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Doctor Assignment</label>
                                <div class="form-control-plaintext">
                                    <i class="fas fa-user-md me-2 text-info"></i>
                                    <span class="text-muted">Doctor will be automatically assigned for consultation</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes (Optional)</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror"
                                  id="notes"
                                  name="notes"
                                  rows="3"
                                  placeholder="Any specific requirements or concerns...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Payment Information -->
                    @if(!$service->isFree())
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-credit-card me-2"></i>Payment Required</h6>
                            <p class="mb-0">Payment of <strong>RM{{ number_format($service->price, 2) }}</strong> will be required upon booking confirmation.</p>
                        </div>
                    @endif

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('appointments.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Services
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calendar-check me-2"></i>Book Appointment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Set minimum date to current time + 1 hour
    document.addEventListener('DOMContentLoaded', function() {
        const now = new Date();
        now.setHours(now.getHours() + 1);
        const minDateTime = now.toISOString().slice(0, 16);
        document.getElementById('appointment_date').min = minDateTime;
    });
</script>
@endpush
@endsection
