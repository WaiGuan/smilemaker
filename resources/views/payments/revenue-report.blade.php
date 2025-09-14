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
    <!-- Revenue by Service -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Daily Revenue</h5>
                <form method="GET" action="{{ route('admin.revenue') }}" class="d-flex align-items-center">
                    <label for="service_date_filter" class="form-label me-2 mb-0">Date:</label>
                    <input type="date" class="form-control form-control-sm" id="service_date_filter" 
                           name="service_date" value="{{ request('service_date', now()->format('Y-m-d')) }}" 
                           style="width: auto;" onchange="this.form.submit()">
                    <!-- Preserve other existing parameters -->
                    @if(request('start_date'))
                        <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                    @endif
                    @if(request('end_date'))
                        <input type="hidden" name="end_date" value="{{ request('end_date') }}">
                    @endif
                    @if(request('month_filter'))
                        <input type="hidden" name="month_filter" value="{{ request('month_filter') }}">
                    @endif
                </form>
            </div>
            <div class="card-body">
                @if($serviceRevenueByDate->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($serviceRevenueByDate as $revenue)
                                    <tr>
                                        <td>
                                            <span class="badge bg-info me-1">{{ substr($revenue->service_name, 0, 1) }}</span>
                                            {{ $revenue->service_name }}
                                        </td>
                                        <td><strong>RM{{ number_format($revenue->total, 2) }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center">No service revenue data available for selected date</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Monthly Revenue by Service -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Monthly Revenue</h5>
                <form method="GET" action="{{ route('admin.revenue') }}" class="d-flex align-items-center">
                    <label for="month_filter" class="form-label me-2 mb-0">Month:</label>
                    <select class="form-select form-select-sm" id="month_filter" name="month_filter" 
                            style="width: auto;" onchange="this.form.submit()">
                        <option value="">All Months</option>
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ request('month_filter') == $i ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create(null, $i, 1)->format('F') }}
                            </option>
                        @endfor
                    </select>
                    <!-- Preserve other existing parameters -->
                    @if(request('start_date'))
                        <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                    @endif
                    @if(request('end_date'))
                        <input type="hidden" name="end_date" value="{{ request('end_date') }}">
                    @endif
                    @if(request('service_date'))
                        <input type="hidden" name="service_date" value="{{ request('service_date') }}">
                    @endif
                </form>
            </div>
            <div class="card-body">
                @if($monthlyServiceRevenue->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Month</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($monthlyServiceRevenue as $revenue)
                                    <tr>
                                        <td>
                                            <span class="badge bg-success me-1">{{ substr($revenue->service_name, 0, 1) }}</span>
                                            {{ $revenue->service_name }}
                                        </td>
                                        <td>{{ \Carbon\Carbon::create($revenue->year, $revenue->month, 1)->format('M Y') }}</td>
                                        <td><strong>RM{{ number_format($revenue->total, 2) }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center">No monthly service revenue data available</p>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Date filter using form submission approach');
    
    // Simple logging for debugging
    const serviceDateFilter = document.getElementById('service_date_filter');
    if (serviceDateFilter) {
        console.log('Date filter element found, current value:', serviceDateFilter.value);
    }
});
</script>
@endpush
