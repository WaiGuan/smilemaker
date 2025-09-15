{{-- Author: Pooi Wai Guan --}}
@extends('layouts.app')

@section('title', 'Login - Dental Clinic')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-header text-center">
                <h4><i class="fas fa-tooth me-2"></i>Dental Clinic</h4>
                <p class="mb-0">Please sign in to your account</p>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                               id="email" name="email" value="{{ old('email') }}" required autofocus>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                               id="password" name="password" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">
                            Remember me
                        </label>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">Don't have an account?
                    <a href="{{ route('register') }}" class="text-decoration-none">Register here</a>
                </p>
            </div>
        </div>

        <!-- Demo Accounts -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Demo Accounts</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 mb-2">
                        <strong>Admin:</strong> admin@clinic.com / password
                    </div>
                    <div class="col-12 mb-2">
                        <strong>Doctor:</strong> doctor@clinic.com / password
                    </div>
                    <div class="col-12">
                        <strong>Patient:</strong> patient@example.com / password
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
