<?php

/// Author: Yuen Yun Jia & Foo Tek Sian

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AppointmentApiService
{
    protected $baseUrl;
    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.appointment_management.base_url', 'http://localhost:8002/api/v1');
        $this->timeout = config('services.appointment_management.timeout', 30);
    }

    /**
     * Get appointment details by appointment ID from Appointment Management API
     */
    public function getAppointmentDetails(int $appointmentId): array
    {
        try {
            // Check cache first
            $cacheKey = "appointment_details_{$appointmentId}";
            $cachedAppointment = Cache::get($cacheKey);
            
            if ($cachedAppointment) {
                return [
                    'success' => true,
                    'data' => $cachedAppointment,
                    'from_cache' => true
                ];
            }

            // Make HTTP request to Appointment Management API
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . config('services.appointment_management.api_token')
                ])
                ->get("{$this->baseUrl}/appointments/{$appointmentId}");

            if ($response->successful()) {
                $appointmentData = $response->json();
                
                // Cache the result for 3 minutes
                Cache::put($cacheKey, $appointmentData['data'], 180);
                
                return [
                    'success' => true,
                    'data' => $appointmentData['data'],
                    'from_cache' => false
                ];
            } else {
                Log::error('Appointment API Error: ' . $response->body(), [
                    'status' => $response->status(),
                    'appointment_id' => $appointmentId
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Failed to retrieve appointment details',
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Appointment API Service Error: ' . $e->getMessage(), [
                'appointment_id' => $appointmentId,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => 'Appointment service unavailable: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update appointment payment status from Appointment Management API
     */
    public function updateAppointmentPaymentStatus(int $appointmentId, string $paymentStatus): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . config('services.appointment_management.api_token')
                ])
                ->put("{$this->baseUrl}/appointments/{$appointmentId}/payment-status", [
                    'payment_status' => $paymentStatus,
                    'updated_by' => 'payment_module',
                    'updated_at' => now()->toISOString()
                ]);

            if ($response->successful()) {
                // Clear cache for this appointment
                $cacheKey = "appointment_details_{$appointmentId}";
                Cache::forget($cacheKey);
                
                return [
                    'success' => true,
                    'data' => $response->json()['data']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to update appointment payment status',
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Update Appointment Payment Status API Service Error: ' . $e->getMessage(), [
                'appointment_id' => $appointmentId,
                'payment_status' => $paymentStatus,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => 'Appointment payment status update service unavailable: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get appointments by patient ID from Appointment Management API
     */
    public function getAppointmentsByPatient(int $patientId, array $filters = []): array
    {
        try {
            $queryParams = array_merge(['patient_id' => $patientId], $filters);
            
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . config('services.appointment_management.api_token')
                ])
                ->get("{$this->baseUrl}/appointments", $queryParams);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to retrieve patient appointments',
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Get Patient Appointments API Service Error: ' . $e->getMessage(), [
                'patient_id' => $patientId,
                'filters' => $filters,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => 'Patient appointments service unavailable: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify appointment exists and is valid for payment
     */
    public function verifyAppointmentForPayment(int $appointmentId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . config('services.appointment_management.api_token')
                ])
                ->post("{$this->baseUrl}/appointments/{$appointmentId}/verify-payment", [
                    'verification_type' => 'payment_eligibility',
                    'requested_by' => 'payment_module'
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Appointment payment verification failed',
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Verify Appointment for Payment API Service Error: ' . $e->getMessage(), [
                'appointment_id' => $appointmentId,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => 'Appointment payment verification service unavailable: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get service details by service ID from Appointment Management API
     */
    public function getServiceDetails(int $serviceId): array
    {
        try {
            $cacheKey = "service_details_{$serviceId}";
            $cachedService = Cache::get($cacheKey);
            
            if ($cachedService) {
                return [
                    'success' => true,
                    'data' => $cachedService,
                    'from_cache' => true
                ];
            }

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . config('services.appointment_management.api_token')
                ])
                ->get("{$this->baseUrl}/services/{$serviceId}");

            if ($response->successful()) {
                $serviceData = $response->json();
                
                // Cache the result for 10 minutes
                Cache::put($cacheKey, $serviceData['data'], 600);
                
                return [
                    'success' => true,
                    'data' => $serviceData['data'],
                    'from_cache' => false
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to retrieve service details',
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Get Service Details API Service Error: ' . $e->getMessage(), [
                'service_id' => $serviceId,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => 'Service details service unavailable: ' . $e->getMessage()
            ];
        }
    }
}
