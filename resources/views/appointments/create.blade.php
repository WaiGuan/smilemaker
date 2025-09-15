{{-- Author: Yuen Yun Jia & Foo Tek Sian --}}
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
                                   min="{{ now()->addHour()->format('Y-m-d\TH:i') }}"
                                   max="{{ now()->addMonths(3)->format('Y-m-d\TH:i') }}"
                                   step="1800"
                                   lang="en"
                                   required>
                            @error('appointment_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Available: 7 days a week, 8:00 AM - 6:00 PM<br>
                                <small class="text-muted">Minimum 1 hour advance booking, maximum 3 months ahead</small>
                            </div>
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
    document.addEventListener('DOMContentLoaded', function() {
        const appointmentDateInput = document.getElementById('appointment_date');
        const form = document.querySelector('form');
        
        // Set minimum date to current time + 1 hour
        const now = new Date();
        now.setHours(now.getHours() + 1);
        const minDateTime = now.toISOString().slice(0, 16);
        appointmentDateInput.min = minDateTime;
        
        // Set maximum date to 3 months from now
        const maxDate = new Date();
        maxDate.setMonth(maxDate.getMonth() + 3);
        const maxDateTime = maxDate.toISOString().slice(0, 16);
        appointmentDateInput.max = maxDateTime;
        
        // Set step to 30 minutes (1800 seconds)
        appointmentDateInput.step = 1800;
        
        // Client-side validation
        appointmentDateInput.addEventListener('change', function() {
            validateAppointmentDateTime(this.value);
        });
        
        // Form submission validation
        form.addEventListener('submit', function(e) {
            const appointmentDate = appointmentDateInput.value;
            if (!validateAppointmentDateTime(appointmentDate)) {
                e.preventDefault();
                return false;
            }
        });
        
        function validateAppointmentDateTime(dateTimeString) {
            if (!dateTimeString) return true; // Let required validation handle empty values
            
            const appointmentDate = new Date(dateTimeString);
            const now = new Date();
            const hour = appointmentDate.getHours();
            
            // Check business hours (8 AM to 6 PM) - applies to all days including weekends
            if (hour < 8 || hour >= 18) {
                showValidationError('Appointments can only be booked between 8:00 AM and 6:00 PM.');
                return false;
            }
            
            // Check if appointment is at least 1 hour from now
            const timeDiff = appointmentDate.getTime() - now.getTime();
            const hoursDiff = timeDiff / (1000 * 60 * 60);
            if (timeDiff < 0 || hoursDiff < 1) {
                showValidationError('Appointments must be booked at least 1 hour in advance.');
                return false;
            }
            
            // Check if appointment is not more than 3 months in advance
            const monthsDiff = (appointmentDate.getFullYear() - now.getFullYear()) * 12 + 
                              (appointmentDate.getMonth() - now.getMonth());
            if (monthsDiff > 3) {
                showValidationError('Appointments cannot be booked more than 3 months in advance.');
                return false;
            }
            
            clearValidationError();
            return true;
        }
        
        function showValidationError(message) {
            // Remove existing error
            clearValidationError();
            
            // Add error class
            appointmentDateInput.classList.add('is-invalid');
            
            // Create error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            errorDiv.textContent = message;
            errorDiv.id = 'datetime-validation-error';
            
            // Insert after the input
            appointmentDateInput.parentNode.insertBefore(errorDiv, appointmentDateInput.nextSibling);
        }
        
        function clearValidationError() {
            appointmentDateInput.classList.remove('is-invalid');
            const existingError = document.getElementById('datetime-validation-error');
            if (existingError) {
                existingError.remove();
            }
        }
    });
</script>
@endpush
@endsection
