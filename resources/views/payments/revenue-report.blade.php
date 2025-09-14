@extends('layouts.app')

@section('title', 'Revenue Report - Dental Clinic')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-chart-line me-2"></i>Revenue Report</h2>
    <div>
        <a href="{{ route('admin.payments') }}" class="btn btn-outline-primary">
            <i class="fas fa-credit-card me-2"></i>View All Payments
        </a>
    </div>
</div>

<!-- Total Revenue Card -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-coins fa-3x text-success mb-3"></i>
                <h2 class="text-success">RM{{ number_format($totalRevenue, 2) }}</h2>
                <h5>Total Revenue</h5>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Daily Revenue -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-calendar-day me-2"></i>Daily Revenue</h5>
            </div>
            <div class="card-body">
                @if($dailyRevenue->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dailyRevenue as $revenue)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($revenue->date)->format('M d, Y') }}</td>
                                        <td><strong>RM{{ number_format($revenue->total, 2) }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center">No daily revenue data available</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Monthly Revenue -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-calendar-alt me-2"></i>Monthly Revenue</h5>
            </div>
            <div class="card-body">
                @if($monthlyRevenue->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($monthlyRevenue as $revenue)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::create($revenue->year, $revenue->month, 1)->format('M Y') }}</td>
                                        <td><strong>RM{{ number_format($revenue->total, 2) }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center">No monthly revenue data available</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Revenue Summary -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-bar me-2"></i>Revenue Summary</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <h6>Total Days with Revenue</h6>
                        <h4 class="text-primary">{{ $dailyRevenue->count() }}</h4>
                    </div>
                    <div class="col-md-3">
                        <h6>Total Months with Revenue</h6>
                        <h4 class="text-primary">{{ $monthlyRevenue->count() }}</h4>
                    </div>
                    <div class="col-md-3">
                        <h6>Average Daily Revenue</h6>
                        <h4 class="text-success">RM{{ $dailyRevenue->count() > 0 ? number_format($dailyRevenue->avg('total'), 2) : '0.00' }}</h4>
                    </div>
                    <div class="col-md-3">
                        <h6>Average Monthly Revenue</h6>
                        <h4 class="text-success">RM{{ $monthlyRevenue->count() > 0 ? number_format($monthlyRevenue->avg('total'), 2) : '0.00' }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
