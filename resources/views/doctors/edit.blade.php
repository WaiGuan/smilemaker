{{-- Author: Pooi Wai Guan --}}
@extends('layouts.app')

@section('title', 'Edit Doctor - Dental Clinic')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-user-edit me-2"></i>Edit Doctor</h4>
                <a href="{{ route('admin.doctors.show', $doctor) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Doctor
                </a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.doctors.update', $doctor) }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $doctor->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" value="{{ old('email', $doctor->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" name="phone" value="{{ old('phone', $doctor->phone) }}" required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="specialization" class="form-label">Specialization</label>
                                <select class="form-select @error('specialization') is-invalid @enderror" 
                                        id="specialization" name="specialization">
                                    <option value="">Select Specialization</option>
                                    <option value="General Dentistry" {{ old('specialization', $doctor->specialization) == 'General Dentistry' ? 'selected' : '' }}>General Dentistry</option>
                                    <option value="Orthodontics" {{ old('specialization', $doctor->specialization) == 'Orthodontics' ? 'selected' : '' }}>Orthodontics</option>
                                    <option value="Oral Surgery" {{ old('specialization', $doctor->specialization) == 'Oral Surgery' ? 'selected' : '' }}>Oral Surgery</option>
                                    <option value="Periodontics" {{ old('specialization', $doctor->specialization) == 'Periodontics' ? 'selected' : '' }}>Periodontics</option>
                                    <option value="Endodontics" {{ old('specialization', $doctor->specialization) == 'Endodontics' ? 'selected' : '' }}>Endodontics</option>
                                    <option value="Prosthodontics" {{ old('specialization', $doctor->specialization) == 'Prosthodontics' ? 'selected' : '' }}>Prosthodontics</option>
                                    <option value="Pediatric Dentistry" {{ old('specialization', $doctor->specialization) == 'Pediatric Dentistry' ? 'selected' : '' }}>Pediatric Dentistry</option>
                                    <option value="Oral Medicine" {{ old('specialization', $doctor->specialization) == 'Oral Medicine' ? 'selected' : '' }}>Oral Medicine</option>
                                </select>
                                @error('specialization')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="license_number" class="form-label">License Number</label>
                                <input type="text" class="form-control @error('license_number') is-invalid @enderror" 
                                       id="license_number" name="license_number" value="{{ old('license_number', $doctor->license_number) }}">
                                @error('license_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="{{ route('admin.doctors.show', $doctor) }}" class="btn btn-secondary me-md-2">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Doctor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
