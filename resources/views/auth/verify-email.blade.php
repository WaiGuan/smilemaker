@extends('layouts.app')

@section('title', 'Verify Email')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card">
            <div class="card-header">Verify Your Email</div>
            <div class="card-body">
                @if (session('status') === 'verification-link-sent')
                    <div class="alert alert-success">
                        A new verification link has been sent to your email address.
                    </div>
                @endif
                <p>Before continuing, please check your email for a verification link.</p>
                <p>If you did not receive the email, you can request another:</p>
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button class="btn btn-primary">Resend Verification Email</button>
                </form>
            </div>
        </div>
    </div>
    </div>
@endsection



