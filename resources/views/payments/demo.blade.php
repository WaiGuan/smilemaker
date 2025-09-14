@extends('layouts.app')

@section('title', 'Payment Demo - Dental Clinic')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header text-center">
                <h4><i class="fas fa-credit-card me-2"></i>Payment Gateway Demo</h4>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>How the Payment System Works</h6>
                    <p class="mb-0">This system supports both Stripe integration and demo mode for testing.</p>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <h6><i class="fas fa-shield-alt me-2"></i>Stripe Integration</h6>
                            </div>
                            <div class="card-body">
                                <h6>When Stripe is configured:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success me-2"></i>Real payment processing</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Secure card handling</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Payment confirmation</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Transaction tracking</li>
                                </ul>

                                <div class="mt-3">
                                    <strong>Required Environment Variables:</strong>
                                    <pre class="bg-light p-2 mt-2"><code>STRIPE_KEY=pk_test_your_key_here
STRIPE_SECRET=sk_test_your_secret_here
PAYMENT_CURRENCY=usd</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h6><i class="fas fa-flask me-2"></i>Demo Mode</h6>
                            </div>
                            <div class="card-body">
                                <h6>When Stripe is not configured:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-warning me-2"></i>Simulated payments</li>
                                    <li><i class="fas fa-check text-warning me-2"></i>90% success rate</li>
                                    <li><i class="fas fa-check text-warning me-2"></i>No real charges</li>
                                    <li><i class="fas fa-check text-warning me-2"></i>Perfect for testing</li>
                                </ul>

                                <div class="mt-3">
                                    <strong>Demo Features:</strong>
                                    <ul class="small">
                                        <li>Simulates payment processing delay</li>
                                        <li>Random success/failure for testing</li>
                                        <li>Updates payment status accordingly</li>
                                        <li>Maintains all payment records</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <h6><i class="fas fa-code me-2"></i>Implementation Details</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>PaymentService Features:</h6>
                            <ul class="small">
                                <li><code>createPaymentIntent()</code> - Creates Stripe payment intent</li>
                                <li><code>confirmPayment()</code> - Confirms payment status</li>
                                <li><code>simulatePayment()</code> - Demo payment simulation</li>
                                <li><code>isStripeConfigured()</code> - Checks configuration</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Database Fields:</h6>
                            <ul class="small">
                                <li><code>stripe_payment_intent_id</code> - Stripe transaction ID</li>
                                <li><code>paid_at</code> - Payment completion timestamp</li>
                                <li><code>status</code> - pending/paid/failed</li>
                                <li><code>amount</code> - Payment amount</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <a href="{{ route('appointments.index') }}" class="btn btn-primary">
                        <i class="fas fa-calendar-plus me-2"></i>Try Booking an Appointment
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
