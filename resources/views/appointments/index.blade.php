@extends('layouts.app')

@section('title', 'Book Appointment - Dental Clinic')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-calendar-plus me-2"></i>Book an Appointment</h2>
    <div>
        <a href="{{ route('appointments.my') }}" class="btn btn-outline-primary">
            <i class="fas fa-calendar-alt me-2"></i>My Appointments
        </a>
    </div>
</div>

<div class="row">
    @foreach($services as $service)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">
                        <i class="fas fa-{{ $service->name === 'Consultation' ? 'stethoscope' : 'tooth' }} me-2"></i>
                        {{ $service->name }}
                    </h5>

                    @if($service->description)
                        <p class="card-text text-muted">{{ $service->description }}</p>
                    @endif

                    <div class="mt-auto">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="h5 mb-0">
                                @if($service->isFree())
                                    <span class="text-success">Free</span>
                                @else
                                    <span class="text-primary">RM{{ number_format($service->price, 2) }}</span>
                                @endif
                            </span>
                            @if($service->isFree())
                                <span class="badge bg-success">Walk-in</span>
                            @else
                                <span class="badge bg-primary">Paid Service</span>
                            @endif
                        </div>

                        <a href="{{ route('appointments.create', ['service_id' => $service->id]) }}"
                           class="btn btn-primary w-100">
                            <i class="fas fa-calendar-plus me-2"></i>Book Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

@if($services->count() === 0)
    <div class="text-center py-5">
        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
        <h4 class="text-muted">No services available</h4>
        <p class="text-muted">Please contact the clinic for available services.</p>
    </div>
@endif
@endsection
