{{-- Author: Tan Huei Qing --}}
@extends('layouts.app')

@section('title', 'All Payments - Dental Clinic')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-credit-card me-2"></i>All Payments</h2>
    <div>
        <a href="{{ route('admin.revenue') }}" class="btn btn-success">
            <i class="fas fa-chart-line me-2"></i>Revenue Report
        </a>
    </div>
</div>

@if($payments->count() > 0)
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>Service</th>
                            <th>Appointment Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $payment)
                            <tr>
                                <td>{{ $payment->id }}</td>
                                <td>{{ $payment->appointment->patient->name }}</td>
                                <td>{{ $payment->appointment->service->name }}</td>
                                <td>{{ $payment->appointment->appointment_date->format('M d, Y H:i') }}</td>
                                <td><strong>RM{{ number_format($payment->amount, 2) }}</strong></td>
                                <td>
                                    <span class="badge bg-{{
                                        $payment->status === 'paid' ? 'success' :
                                        ($payment->status === 'failed' ? 'danger' : 'warning')
                                    }}">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </td>
                                <td>{{ $payment->created_at->format('M d, Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('appointments.show', $payment->appointment) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       title="View Appointment">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-credit-card fa-2x text-primary mb-2"></i>
                    <h5 class="card-title">{{ $payments->count() }}</h5>
                    <p class="card-text">Total Payments</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <h5 class="card-title">{{ $payments->where('status', 'paid')->count() }}</h5>
                    <p class="card-text">Paid</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                    <h5 class="card-title">{{ $payments->where('status', 'pending')->count() }}</h5>
                    <p class="card-text">Pending</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-coins fa-2x text-info mb-2"></i>
                    <h5 class="card-title">RM{{ number_format($payments->where('status', 'paid')->sum('amount'), 2) }}</h5>
                    <p class="card-text">Total Revenue</p>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="text-center py-5">
        <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
        <h4 class="text-muted">No payments found</h4>
        <p class="text-muted">No payments have been processed yet.</p>
    </div>
@endif
@endsection
