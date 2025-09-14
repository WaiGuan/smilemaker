<div class="col-12 mb-4">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <!-- Left Side - Service and Details -->
                <div class="col-md-8">
                    <!-- Service Name -->
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title mb-0">{{ $appointment->service->name }}</h5>
                    </div>
                    
                    <!-- Details Below Service Name -->
                    <div class="row">
                        <!-- Date and Time -->
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-calendar me-2 text-primary"></i>
                                <strong>{{ $appointment->appointment_date->format('M d, Y') }}</strong>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-clock me-2 text-primary"></i>
                                {{ $appointment->appointment_date->format('H:i') }}
                            </div>
                        </div>

                        <!-- Doctor/Patient Info -->
                        <div class="col-md-6">
                            @if(auth()->user()->isPatient() && $appointment->doctor)
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-user-md me-2 text-primary"></i>
                                    <div>
                                        <small class="text-muted">Doctor</small><br>
                                        <strong>Dr. {{ $appointment->doctor->name }}</strong>
                                    </div>
                                </div>
                            @elseif(auth()->user()->isDoctor() && $appointment->patient)
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-user me-2 text-primary"></i>
                                    <div>
                                        <small class="text-muted">Patient</small><br>
                                        <strong>{{ $appointment->patient->name }}</strong>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Notes -->
                    @if($appointment->notes)
                        <div class="d-flex align-items-start mt-2">
                            <i class="fas fa-sticky-note me-2 text-primary mt-1"></i>
                            <div>
                                <small class="text-muted">Notes:</small><br>
                                <span class="text-muted">{{ Str::limit($appointment->notes, 80) }}</span>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Right Side - Status and Payment -->
                <div class="col-md-4">
                    <div class="d-flex flex-column align-items-end">
                        <!-- Appointment Status -->
                        <span class="badge bg-{{
                            $appointment->status === 'completed' ? 'success' :
                            ($appointment->status === 'cancelled' ? 'danger' :
                            ($appointment->status === 'confirmed' ? 'primary' : 'warning'))
                        }} fs-6 mb-3">
                            {{ ucfirst($appointment->status) }}
                        </span>

                        <!-- Payment Status -->
                        @if($appointment->payment)
                            <div class="text-end mb-2">
                                <span class="badge bg-{{ $appointment->payment->status === 'paid' ? 'success' : 'warning' }} mb-1">
                                    {{ ucfirst($appointment->payment->status) }}
                                </span>
                                <div class="text-primary fw-bold">RM{{ number_format($appointment->payment->amount, 2) }}</div>
                            </div>
                        @else
                            <div class="text-muted text-end mb-2">
                                <small>No payment</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row mt-3">
                <div class="col-12">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-outline-primary">
                            <i class="fas fa-eye me-1"></i>View Details
                        </a>

                        @if($appointment->canBeCancelled() && auth()->user()->isPatient())
                            <form method="POST" action="{{ route('appointments.cancel', $appointment) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger"
                                        onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </button>
                            </form>
                        @endif

                        @if($appointment->payment && $appointment->payment->status === 'pending' && auth()->user()->isPatient())
                            <a href="{{ route('payments.show', $appointment) }}" class="btn btn-success">
                                <i class="fas fa-credit-card me-1"></i>Pay Now
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
