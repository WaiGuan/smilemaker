@extends('layouts.app')

@section('title', 'Payment - Dental Clinic')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header text-center">
                <h4><i class="fas fa-credit-card me-2"></i>Payment</h4>
            </div>
            <div class="card-body">
                <!-- Appointment Details -->
                <div class="alert alert-info">
                    <h6><i class="fas fa-calendar-check me-2"></i>Appointment Details</h6>
                    <p class="mb-1"><strong>{{ $appointment->service->name }}</strong></p>
                    <p class="mb-1">
                        <i class="fas fa-calendar me-1"></i>
                        {{ $appointment->appointment_date->format('M d, Y H:i') }}
                    </p>
                    @if($appointment->doctor)
                        <p class="mb-0">
                            <i class="fas fa-user-md me-1"></i>
                            Dr. {{ $appointment->doctor->name }}
                        </p>
                    @endif
                </div>

                <!-- Payment Information -->
                <div class="text-center mb-4">
                    <h5>Amount to Pay</h5>
                    <h2 class="text-primary">RM{{ number_format($payment->amount, 2) }}</h2>
                    <p class="text-muted">Status:
                        <span class="badge bg-{{
                            $payment->status === 'paid' ? 'success' :
                            ($payment->status === 'failed' ? 'danger' : 'warning')
                        }}">
                            {{ ucfirst($payment->status) }}
                        </span>
                    </p>
                </div>

                @if($payment->status === 'pending' || $payment->status === 'failed')
                    @if($payment->status === 'failed')
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Payment Failed:</strong> Your previous payment attempt was unsuccessful. Please try again with a different card or check your card details.
                        </div>
                    @endif

                    @if($isStripeConfigured)
                        <!-- Stripe Payment Form -->
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Secure Payment:</strong> Powered by Stripe
                        </div>

                        <!-- Demo Card Information -->
                        <div class="alert alert-info mb-3">
                            <h6><i class="fas fa-credit-card me-2"></i>Test Cards</h6>
                            <p class="mb-2"><small class="text-muted">For testing purposes, use these test cards:</small></p>
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Success:</strong><br>
                                    <code>4242 4242 4242 4242</code>
                                </div>
                                <div class="col-md-4">
                                    <strong>Decline:</strong><br>
                                    <code>4000 0000 0000 0002</code>
                                </div>
                                <div class="col-md-4">
                                    <strong>Insufficient Funds:</strong><br>
                                    <code>4000 0000 0000 9995</code>
                                </div>
                            </div>
                            <p class="mb-0 mt-2"><small class="text-muted">Use any future expiry date and any 3-digit CVC</small></p>
                        </div>

                        <!-- Manual Card Form (CSP-friendly) -->
                        <form method="POST" action="{{ route('payments.process', $payment) }}">
                            @csrf
                            <input type="hidden" name="stripe_mode" value="1">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="card_number" class="form-label">Card Number</label>
                                    <input type="text" class="form-control" id="card_number" name="card_number"
                                           placeholder="4242 4242 4242 4242" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="expiry" class="form-label">Expiry</label>
                                    <input type="text" class="form-control" id="expiry" name="expiry"
                                           placeholder="12/25" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="cvc" class="form-label">CVC</label>
                                    <input type="text" class="form-control" id="cvc" name="cvc"
                                           placeholder="123" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="cardholder_name" class="form-label">Cardholder Name</label>
                                    <input type="text" class="form-control" id="cardholder_name" name="cardholder_name"
                                           placeholder="John Doe" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="{{ auth()->user()->email }}" readonly>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-{{ $payment->status === 'failed' ? 'danger' : 'success' }} btn-lg">
                                    <i class="fas fa-credit-card me-2"></i>
                                    {{ $payment->status === 'failed' ? 'Retry Payment' : 'Pay' }} RM{{ number_format($payment->amount, 2) }}
                                </button>
                            </div>
                        </form>

                    @else
                        <!-- Demo Payment Mode -->
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Demo Mode:</strong> Stripe is not configured. This is a simulation.
                        </div>

                        <form method="POST" action="{{ route('payments.process', $payment) }}">
                            @csrf
                            <div class="d-grid">
                                <button type="submit" class="btn btn-{{ $payment->status === 'failed' ? 'danger' : 'success' }} btn-lg">
                                    <i class="fas fa-credit-card me-2"></i>
                                    {{ $payment->status === 'failed' ? 'Retry Simulation' : 'Simulate Payment' }}
                                </button>
                            </div>
                            <p class="text-muted text-center mt-2">
                                <small>This will simulate a payment with 90% success rate.</small>
                            </p>
                        </form>
                    @endif
                @elseif($payment->status === 'paid')
                    <div class="alert alert-success text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h5>Payment Completed!</h5>
                        <p class="mb-0">Your payment has been processed successfully.</p>
                        @if($payment->paid_at)
                            <small class="text-muted">Paid on {{ $payment->paid_at->format('M d, Y H:i') }}</small>
                        @endif
                    </div>
                @endif
            </div>
            <div class="card-footer text-center">
                <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Appointment
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@if($isStripeConfigured && $payment->status === 'pending')
@push('scripts')
<script>
    // Simple form validation (CSP-friendly)
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form[action*="payments.process"]');
        if (form) {
            form.addEventListener('submit', function(e) {
                const cardNumber = document.getElementById('card_number').value;
                const expiry = document.getElementById('expiry').value;
                const cvc = document.getElementById('cvc').value;

                // Basic validation
                if (!cardNumber || cardNumber.length < 13) {
                    e.preventDefault();
                    alert('Please enter a valid card number');
                    return false;
                }

                if (!expiry || !expiry.includes('/')) {
                    e.preventDefault();
                    alert('Please enter expiry in MM/YY format');
                    return false;
                }

                if (!cvc || cvc.length < 3) {
                    e.preventDefault();
                    alert('Please enter a valid CVC');
                    return false;
                }

                // Show loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            });
        }
    });
</script>
@endpush
@endif
