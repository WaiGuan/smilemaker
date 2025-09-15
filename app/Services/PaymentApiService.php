<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PaymentApiService
{
    protected $baseUrl;
    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.payment_management.base_url', 'http://localhost:8000/api/v1');
        $this->timeout = config('services.payment_management.timeout', 30);
    }

    /**
     * Get payment information for an appointment from Payment Management API
     */
    public function getPaymentByAppointment(int $appointmentId): array
    {
        try {
            // Check cache first
            $cacheKey = "payment_appointment_{$appointmentId}";
            $cachedPayment = Cache::get($cacheKey);
            
            if ($cachedPayment) {
                return [
                    'success' => true,
                    'data' => $cachedPayment,
                    'from_cache' => true
                ];
            }

            // Make HTTP request to Payment Management API
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . config('services.payment_management.api_token')
                ])
                ->get("{$this->baseUrl}/payments/{$appointmentId}");

            if ($response->successful()) {
                $paymentData = $response->json();
                
                // Cache the result for 5 minutes
                Cache::put($cacheKey, $paymentData['data'], 300);
                
                return [
                    'success' => true,
                    'data' => $paymentData['data'],
                    'from_cache' => false
                ];
            } else {
                Log::error('Payment API Error: ' . $response->body(), [
                    'status' => $response->status(),
                    'appointment_id' => $appointmentId
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Failed to retrieve payment information',
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Payment API Service Error: ' . $e->getMessage(), [
                'appointment_id' => $appointmentId,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => 'Payment service unavailable: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify payment eligibility from Payment Management API
     */
    public function verifyPaymentEligibility(int $userId, int $appointmentId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . config('services.payment_management.api_token')
                ])
                ->post("{$this->baseUrl}/payments/verify-eligibility", [
                    'appointment_id' => $appointmentId
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Payment eligibility verification failed',
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Payment Eligibility API Service Error: ' . $e->getMessage(), [
                'user_id' => $userId,
                'appointment_id' => $appointmentId,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => 'Payment eligibility service unavailable: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get enhanced payment information from Payment Management API
     */
    public function getEnhancedPaymentInfo(int $paymentId): array
    {
        try {
            $cacheKey = "enhanced_payment_{$paymentId}";
            $cachedPayment = Cache::get($cacheKey);
            
            if ($cachedPayment) {
                return [
                    'success' => true,
                    'data' => $cachedPayment,
                    'from_cache' => true
                ];
            }

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . config('services.payment_management.api_token')
                ])
                ->get("{$this->baseUrl}/payments/{$paymentId}/enhanced-info");

            if ($response->successful()) {
                $paymentData = $response->json();
                
                // Cache the result for 3 minutes
                Cache::put($cacheKey, $paymentData['data'], 180);
                
                return [
                    'success' => true,
                    'data' => $paymentData['data'],
                    'from_cache' => false
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to retrieve enhanced payment information',
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Enhanced Payment Info API Service Error: ' . $e->getMessage(), [
                'payment_id' => $paymentId,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => 'Enhanced payment info service unavailable: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get revenue report from Payment Management API
     */
    public function getRevenueReport(array $filters = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . config('services.payment_management.api_token')
                ])
                ->get("{$this->baseUrl}/payments/revenue-report", $filters);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to retrieve revenue report',
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Revenue Report API Service Error: ' . $e->getMessage(), [
                'filters' => $filters,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => 'Revenue report service unavailable: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process payment via Payment Management API
     */
    public function processPayment(int $paymentId, array $paymentData): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . config('services.payment_management.api_token')
                ])
                ->post("{$this->baseUrl}/payments/{$paymentId}/process", $paymentData);

            if ($response->successful()) {
                // Clear related caches
                $appointmentId = $paymentData['appointment_id'] ?? '';
                Cache::forget("payment_appointment_{$appointmentId}");
                Cache::forget("enhanced_payment_{$paymentId}");
                
                return [
                    'success' => true,
                    'data' => $response->json()['data']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Payment processing failed',
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Process Payment API Service Error: ' . $e->getMessage(), [
                'payment_id' => $paymentId,
                'payment_data' => $paymentData,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => 'Payment processing service unavailable: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all payments from Payment Management API
     */
    public function getAllPayments(array $filters = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . config('services.payment_management.api_token')
                ])
                ->get("{$this->baseUrl}/payments", $filters);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to retrieve payments',
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Get All Payments API Service Error: ' . $e->getMessage(), [
                'filters' => $filters,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => 'Get payments service unavailable: ' . $e->getMessage()
            ];
        }
    }
}
