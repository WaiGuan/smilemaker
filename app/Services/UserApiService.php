<?php

/// Author: Pooi Wai Guan

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class UserApiService
{
    protected $baseUrl;
    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.user_management.base_url', 'http://localhost:8001/api/v1');
        $this->timeout = config('services.user_management.timeout', 30);
    }

    /**
     * Get user details by user ID from User Management API
     */
    public function getUserDetails(int $userId): array
    {
        try {
            // Check cache first
            $cacheKey = "user_details_{$userId}";
            $cachedUser = Cache::get($cacheKey);
            
            if ($cachedUser) {
                return [
                    'success' => true,
                    'data' => $cachedUser,
                    'from_cache' => true
                ];
            }

            // Make HTTP request to User Management API
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . config('services.user_management.api_token')
                ])
                ->get("{$this->baseUrl}/users/{$userId}");

            if ($response->successful()) {
                $userData = $response->json();
                
                // Cache the result for 5 minutes
                Cache::put($cacheKey, $userData['data'], 300);
                
                return [
                    'success' => true,
                    'data' => $userData['data'],
                    'from_cache' => false
                ];
            } else {
                Log::error('User API Error: ' . $response->body(), [
                    'status' => $response->status(),
                    'user_id' => $userId
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Failed to retrieve user details',
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('User API Service Error: ' . $e->getMessage(), [
                'user_id' => $userId,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => 'User service unavailable: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user role information from User Management API
     */
    public function getUserRole(int $userId): array
    {
        try {
            $cacheKey = "user_role_{$userId}";
            $cachedRole = Cache::get($cacheKey);
            
            if ($cachedRole) {
                return [
                    'success' => true,
                    'data' => $cachedRole,
                    'from_cache' => true
                ];
            }

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . config('services.user_management.api_token')
                ])
                ->get("{$this->baseUrl}/users/{$userId}/role");

            if ($response->successful()) {
                $roleData = $response->json();
                
                // Cache the result for 10 minutes
                Cache::put($cacheKey, $roleData['data'], 600);
                
                return [
                    'success' => true,
                    'data' => $roleData['data'],
                    'from_cache' => false
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to retrieve user role',
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('User Role API Service Error: ' . $e->getMessage(), [
                'user_id' => $userId,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => 'User role service unavailable: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify user exists and is active from User Management API
     */
    public function verifyUser(int $userId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . config('services.user_management.api_token')
                ])
                ->post("{$this->baseUrl}/users/verify", [
                    'user_id' => $userId
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'User verification failed',
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('User Verification API Service Error: ' . $e->getMessage(), [
                'user_id' => $userId,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => 'User verification service unavailable: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get multiple users by IDs from User Management API
     */
    public function getUsersByIds(array $userIds): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . config('services.user_management.api_token')
                ])
                ->post("{$this->baseUrl}/users/batch", [
                    'user_ids' => $userIds
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to retrieve users',
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Batch Users API Service Error: ' . $e->getMessage(), [
                'user_ids' => $userIds,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => 'Batch users service unavailable: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if user has permission for payment operations
     */
    public function checkPaymentPermission(int $userId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . config('services.user_management.api_token')
                ])
                ->get("{$this->baseUrl}/users/{$userId}/permissions/payment");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to check payment permissions',
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Payment Permission API Service Error: ' . $e->getMessage(), [
                'user_id' => $userId,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => 'Payment permission service unavailable: ' . $e->getMessage()
            ];
        }
    }
}
