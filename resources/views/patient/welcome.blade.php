@extends('layouts.app')

@section('title', 'Welcome to Smilemaker Dental Clinic')

@section('content')
<div class="container-fluid">
    <!-- Hero Section -->
    <div class="row bg-primary text-white py-4 mb-4 position-relative overflow-hidden">
        <div class="col-12 text-center position-relative" style="z-index: 2;">
            <h1 class="display-4 fw-bold mb-3">
                <i class="fas fa-tooth me-3"></i>Welcome to Smilemaker Dental Clinic
            </h1>
            <p class="lead">Your smile is our passion. Experience exceptional dental care in a comfortable, modern environment.</p>
        </div>
        <!-- Background Image -->
        <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(rgba(0,123,255,0.8), rgba(0,123,255,0.8)), url('https://images.unsplash.com/photo-1606811841689-23dfddceeee3?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80') center/cover; z-index: 1;"></div>
    </div>

    <!-- About Section with Clinic Photo -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card shadow-lg border-0 overflow-hidden" style="border-radius: 20px;">
                <div class="row g-0">
                    <div class="col-md-6">
                        <img src="https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" 
                             class="img-fluid" 
                             style="object-fit: cover; height: 400px; width: 100%;" 
                             alt="Modern dental clinic interior with professional equipment">
                    </div>
                    <div class="col-md-6">
                        <div class="card-body p-3 d-flex flex-column justify-content-center" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); height: 400px;">
                            <div class="text-center mb-3">
                                <h2 class="text-primary mb-2" style="font-weight: 700; font-size: 1.6rem;">
                                    <i class="fas fa-heart me-2" style="color: #e74c3c;"></i>About Smilemaker
                                </h2>
                            </div>
                            
                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="d-flex align-items-start p-2 rounded-3" style="background: rgba(255,255,255,0.9); backdrop-filter: blur(10px);">
                                        <div class="me-3 mt-1">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: linear-gradient(135deg, #007bff, #0056b3);">
                                                <i class="fas fa-award text-white" style="font-size: 0.9rem;"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-1 text-dark" style="font-weight: 600; font-size: 0.95rem;">Excellence in Care</h6>
                                            <small class="text-muted" style="line-height: 1.4;">World-class dental services with latest technology and techniques</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="d-flex align-items-start p-2 rounded-3" style="background: rgba(255,255,255,0.9); backdrop-filter: blur(10px);">
                                        <div class="me-3 mt-1">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: linear-gradient(135deg, #28a745, #1e7e34);">
                                                <i class="fas fa-users text-white" style="font-size: 0.9rem;"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-1 text-dark" style="font-weight: 600; font-size: 0.95rem;">Experienced Team</h6>
                                            <small class="text-muted" style="line-height: 1.4;">Skilled dentists and hygienists dedicated to personalized care</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="d-flex align-items-start p-2 rounded-3" style="background: rgba(255,255,255,0.9); backdrop-filter: blur(10px);">
                                        <div class="me-3 mt-1">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: linear-gradient(135deg, #ffc107, #e0a800);">
                                                <i class="fas fa-clock text-white" style="font-size: 0.9rem;"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-1 text-dark" style="font-weight: 600; font-size: 0.95rem;">Convenient Hours</h6>
                                            <small class="text-muted" style="line-height: 1.4;">Flexible scheduling including evening and weekend appointments</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="d-flex align-items-start p-2 rounded-3" style="background: rgba(255,255,255,0.9); backdrop-filter: blur(10px);">
                                        <div class="me-3 mt-1">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: linear-gradient(135deg, #6f42c1, #5a32a3);">
                                                <i class="fas fa-shield-alt text-white" style="font-size: 0.9rem;"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-1 text-dark" style="font-weight: 600; font-size: 0.95rem;">Safe & Clean</h6>
                                            <small class="text-muted" style="line-height: 1.4;">Highest standards of cleanliness and sterilization for your safety</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Services Overview with Photos -->
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="text-center mb-4 text-primary">
                <i class="fas fa-stethoscope me-2"></i>Our Services
            </h2>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100 text-center overflow-hidden">
                <img src="https://images.unsplash.com/photo-1606811971618-4486d14f3f99?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" 
                     class="card-img-top" 
                     style="height: 200px; object-fit: cover;" 
                     alt="General dentistry">
                <div class="card-body">
                    <i class="fas fa-tooth fa-2x text-primary mb-3"></i>
                    <h5>General Dentistry</h5>
                    <p class="text-muted">Comprehensive dental care including cleanings, fillings, and preventive treatments.</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100 text-center overflow-hidden">
                <img src="https://images.unsplash.com/photo-1609840114035-3c981b782dfe?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" 
                     class="card-img-top" 
                     style="height: 200px; object-fit: cover;" 
                     alt="Cosmetic dentistry">
                <div class="card-body">
                    <i class="fas fa-smile fa-2x text-primary mb-3"></i>
                    <h5>Cosmetic Dentistry</h5>
                    <p class="text-muted">Transform your smile with whitening, veneers, and other cosmetic procedures.</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100 text-center overflow-hidden">
                <img src="https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" 
                     class="card-img-top" 
                     style="height: 200px; object-fit: cover;" 
                     alt="Restorative care">
                <div class="card-body">
                    <i class="fas fa-tools fa-2x text-primary mb-3"></i>
                    <h5>Restorative Care</h5>
                    <p class="text-muted">Crowns, bridges, and implants to restore function and appearance.</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100 text-center overflow-hidden">
                <img src="https://images.unsplash.com/photo-1559757148-5c350d0d3c56?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" 
                     class="card-img-top" 
                     style="height: 200px; object-fit: cover;" 
                     alt="Family dental care">
                <div class="card-body">
                    <i class="fas fa-child fa-2x text-primary mb-3"></i>
                    <h5>Family Care</h5>
                    <p class="text-muted">Dental care for the whole family, from children to seniors.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body text-center p-5">
                    <h3 class="mb-4 text-primary">
                        <i class="fas fa-calendar-plus me-2"></i>Ready to Get Started?
                    </h3>
                    <p class="lead mb-4">Book your appointment today and take the first step towards a healthier, more beautiful smile.</p>
                    
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="{{ route('appointments.index') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-calendar-plus me-2"></i>Book Appointment
                        </a>
                        <a href="{{ route('appointments.my') }}" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-calendar-alt me-2"></i>My Appointments
                        </a>
                        <a href="{{ route('profile') }}" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-user-edit me-2"></i>My Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-map-marker-alt fa-2x text-primary mb-3"></i>
                    <h5>Visit Us</h5>
                    <p class="text-muted">
                        123 Dental Street<br>
                        Health City, HC 12345<br>
                        United States
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-phone fa-2x text-primary mb-3"></i>
                    <h5>Call Us</h5>
                    <p class="text-muted">
                        Phone: (555) 123-4567<br>
                        Emergency: (555) 123-4568<br>
                        Fax: (555) 123-4569
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-envelope fa-2x text-primary mb-3"></i>
                    <h5>Email Us</h5>
                    <p class="text-muted">
                        info@smilemakerdental.com<br>
                        appointments@smilemakerdental.com<br>
                        emergency@smilemakerdental.com
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
