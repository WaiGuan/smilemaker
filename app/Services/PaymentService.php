<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Appointment;
use App\Services\NotificationService;
use App\Services\UserApiService;
use App\Services\AppointmentApiService;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected $notificationService;
    protected $userApiService;
    protected $appointmentApiService;

    public function __construct(
        NotificationService $notificationService,
        UserApiService $userApiService,
        AppointmentApiService $appointmentApiService
    ) {
        // Initialize Stripe with secret key
        Stripe::setApiKey(config('services.stripe.secret'));
        $this->notificationService = $notificationService;
        $this->userApiService = $userApiService;
        $this->appointmentApiService = $appointmentApiService;
    }

    /**
     * Verify user and appointment before payment processing
     */
    public function verifyPaymentEligibility(int $userId, int $appointmentId): array
    {
        try {
            // Verify user exists and has payment permissions
            $userResult = $this->userApiService->checkPaymentPermission($userId);
            if (!$userResult['success']) {
                return [
                    'success' => false,
                    'error' => 'User payment permission verification failed: ' . $userResult['error']
                ];
            }

            // Verify appointment exists and is eligible for payment
            $appointmentResult = $this->appointmentApiService->verifyAppointmentForPayment($appointmentId);
            if (!$appointmentResult['success']) {
                return [
                    'success' => false,
                    'error' => 'Appointment payment verification failed: ' . $appointmentResult['error']
                ];
            }

            return [
                'success' => true,
                'message' => 'Payment eligibility verified',
                'user_permissions' => $userResult['data'],
                'appointment_eligibility' => $appointmentResult['data']
            ];

        } catch (\Exception $e) {
            Log::error('Payment Eligibility Verification Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Payment eligibility verification failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get enhanced payment information with external API data
     */
    public function getEnhancedPaymentInfo(int $paymentId): array
    {
        try {
            $payment = Payment::with('appointment')->find($paymentId);
            if (!$payment) {
                return [
                    'success' => false,
                    'error' => 'Payment not found'
                ];
            }

            // Get user details from User Management API
            $userResult = $this->userApiService->getUserDetails($payment->appointment->patient_id);
            $userDetails = $userResult['success'] ? $userResult['data'] : null;

            // Get appointment details from Appointment Management API
            $appointmentResult = $this->appointmentApiService->getAppointmentDetails($payment->appointment_id);
            $appointmentDetails = $appointmentResult['success'] ? $appointmentResult['data'] : null;

            // Get service details from Appointment Management API
            $serviceResult = $this->appointmentApiService->getServiceDetails($payment->appointment->service_id);
            $serviceDetails = $serviceResult['success'] ? $serviceResult['data'] : null;

            return [
                'success' => true,
                'data' => [
                    'payment' => $payment,
                    'user_details' => $userDetails,
                    'appointment_details' => $appointmentDetails,
                    'service_details' => $serviceDetails,
                    'api_status' => [
                        'user_api' => $userResult['success'],
                        'appointment_api' => $appointmentResult['success'],
                        'service_api' => $serviceResult['success']
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Enhanced Payment Info Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to retrieve enhanced payment information: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create a payment intent for an appointment
     */
    public function createPaymentIntent(Appointment $appointment): array
    {
        try {
            $payment = $appointment->payment;

            if (!$payment) {
                throw new \Exception('No payment found for this appointment');
            }

            // Create Stripe Payment Intent
            $paymentIntent = PaymentIntent::create([
                'amount' => $this->convertToCents($payment->amount),
                'currency' => config('services.stripe.currency', 'usd'),
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never'
                ],
                'metadata' => [
                    'appointment_id' => $appointment->id,
                    'payment_id' => $payment->id,
                    'patient_name' => $appointment->patient->name,
                    'service_name' => $appointment->service->name,
                ],
                'description' => "Payment for {$appointment->service->name} - {$appointment->patient->name}",
            ]);

            // Update payment with Stripe payment intent ID
            $payment->update([
                'stripe_payment_intent_id' => $paymentIntent->id,
            ]);

            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Payment service error: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error('Payment Service Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Payment processing error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Confirm payment after successful Stripe payment
     */
    public function confirmPayment(string $paymentIntentId): array
    {
        try {
            // Retrieve the payment intent from Stripe
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            // Find the payment record
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->first();

            if (!$payment) {
                throw new \Exception('Payment record not found for payment intent: ' . $paymentIntentId);
            }

            // Update payment status based on Stripe status
            if ($paymentIntent->status === 'succeeded') {
                $payment->update([
                    'status' => 'paid',
                    'stripe_payment_intent_id' => $paymentIntentId,
                    'paid_at' => now(),
                ]);

                // Update appointment payment status via external API
                $appointmentUpdateResult = $this->appointmentApiService->updateAppointmentPaymentStatus(
                    $payment->appointment_id, 
                    'paid'
                );

                // Send notification to patient
                $this->sendPaymentNotification($payment, 'success');

                return [
                    'success' => true,
                    'message' => 'Payment confirmed successfully',
                    'payment' => $payment,
                    'appointment_updated' => $appointmentUpdateResult['success']
                ];
            } else {
                $payment->update([
                    'status' => 'failed',
                    'stripe_payment_intent_id' => $paymentIntentId,
                ]);

                // Update appointment payment status via external API
                $appointmentUpdateResult = $this->appointmentApiService->updateAppointmentPaymentStatus(
                    $payment->appointment_id, 
                    'failed'
                );

                // Send notification to patient
                $this->sendPaymentNotification($payment, 'failed');

                return [
                    'success' => false,
                    'error' => 'Payment failed: ' . $paymentIntent->status,
                    'payment' => $payment,
                    'appointment_updated' => $appointmentUpdateResult['success']
                ];
            }

        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Payment confirmation error: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error('Payment Confirmation Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Payment confirmation error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get payment intent status
     */
    public function getPaymentStatus(string $paymentIntentId): array
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            return [
                'success' => true,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to retrieve payment status: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Simulate payment for demo purposes (when Stripe is not configured)
     */
    public function simulatePayment(Appointment $appointment): array
    {
        $payment = $appointment->payment;

        if (!$payment) {
            return [
                'success' => false,
                'error' => 'No payment found for this appointment',
            ];
        }

        // Simulate payment processing delay
        sleep(2);

        // Simulate 90% success rate for demo
        $success = rand(1, 10) <= 9;

        if ($success) {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'stripe_payment_intent_id' => 'demo_payment_' . time(),
            ]);

            return [
                'success' => true,
                'message' => 'Demo payment processed successfully',
                'payment' => $payment,
            ];
        } else {
            $payment->update([
                'status' => 'failed',
            ]);

            return [
                'success' => false,
                'error' => 'Demo payment failed (simulated)',
                'payment' => $payment,
            ];
        }
    }

    /**
     * Convert dollars to cents for Stripe
     */
    private function convertToCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Check if Stripe is properly configured
     */
    public function isStripeConfigured(): bool
    {
        return !empty(config('services.stripe.secret')) &&
               !empty(config('services.stripe.key')) &&
               config('services.stripe.secret') !== 'sk_test_your_stripe_secret_key_here';
    }

    /**
     * Create payment intent with card details (server-side)
     */
    public function createPaymentIntentWithCard($appointment, $cardNumber, $expMonth, $expYear, $cvc, $cardholderName, $email): array
    {
        try {
            $payment = $appointment->payment;

            if (!$payment) {
                throw new \Exception('No payment found for this appointment');
            }

            // For testing, use Stripe's test tokens instead of raw card data
            $paymentMethodId = $this->getTestPaymentMethodId($cardNumber);

            if (!$paymentMethodId) {
                // If not a test card, try to create payment method (may fail due to security restrictions)
                try {
                    $paymentMethod = \Stripe\PaymentMethod::create([
                        'type' => 'card',
                        'card' => [
                            'number' => $cardNumber,
                            'exp_month' => $expMonth,
                            'exp_year' => $expYear,
                            'cvc' => $cvc,
                        ],
                        'billing_details' => [
                            'name' => $cardholderName,
                            'email' => $email,
                        ],
                    ]);
                    $paymentMethodId = $paymentMethod->id;
                } catch (\Stripe\Exception\InvalidRequestException $e) {
                    if (strpos($e->getMessage(), 'raw card data') !== false) {
                        return [
                            'success' => false,
                            'error' => 'For security reasons, please use the test card numbers provided on the payment page.'
                        ];
                    }
                    throw $e;
                }
            }

            // Create Payment Intent
            $paymentIntent = PaymentIntent::create([
                'amount' => $this->convertToCents($payment->amount),
                'currency' => config('services.stripe.currency', 'usd'),
                'payment_method' => $paymentMethodId,
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never'
                ],
                'confirm' => true,
                'metadata' => [
                    'appointment_id' => $appointment->id,
                    'payment_id' => $payment->id,
                    'patient_name' => $appointment->patient->name,
                    'service_name' => $appointment->service->name,
                ],
                'description' => "Payment for {$appointment->service->name} - {$appointment->patient->name}",
            ]);

            // Log payment intent status for debugging
            Log::info('Payment Intent Status: ' . $paymentIntent->status, [
                'payment_intent_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency
            ]);

            // Check payment status
            if ($paymentIntent->status === 'succeeded') {
                return [
                    'success' => true,
                    'payment_intent_id' => $paymentIntent->id,
                    'message' => 'Payment processed successfully'
                ];
            } elseif ($paymentIntent->status === 'requires_action') {
                // Handle 3D Secure or other authentication
                return [
                    'success' => false,
                    'error' => 'Payment requires additional authentication. Please try a different card or contact your bank.'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Payment failed: ' . $paymentIntent->status
                ];
            }

        } catch (\Stripe\Exception\CardException $e) {
            // Card was declined
            Log::error('Stripe Card Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getDeclineCode() ?
                    'Your card was declined: ' . $e->getDeclineCode() :
                    'Your card was declined. Please try a different card.'
            ];
        } catch (\Stripe\Exception\RateLimitException $e) {
            Log::error('Stripe Rate Limit Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Too many requests. Please try again in a moment.'
            ];
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            Log::error('Stripe Invalid Request Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Invalid payment information. Please check your card details.'
            ];
        } catch (\Stripe\Exception\AuthenticationException $e) {
            Log::error('Stripe Authentication Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Payment service authentication failed. Please try again later.'
            ];
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            Log::error('Stripe API Connection Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Unable to connect to payment service. Please try again.'
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe API Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Payment service error. Please try again.'
            ];
        } catch (\Exception $e) {
            Log::error('Payment Service Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Payment processing error. Please try again.'
            ];
        }
    }

    /**
     * Get test payment method ID for known test cards
     */
    private function getTestPaymentMethodId(string $cardNumber): ?string
    {
        // Remove spaces and normalize card number
        $normalizedCard = preg_replace('/\s+/', '', $cardNumber);

        // Map test card numbers to their corresponding test payment method tokens
        $testCards = [
            '4242424242424242' => 'pm_card_visa', // Visa test card - SUCCESS
            '4000000000000002' => 'pm_card_visa_chargeDeclined', // General decline - FAIL
            '4000000000009995' => 'pm_card_visa_chargeDeclinedInsufficientFunds', // Insufficient funds - FAIL
            '4000000000009987' => 'pm_card_visa_chargeDeclinedLostCard', // Lost card - FAIL
            '4000000000009979' => 'pm_card_visa_chargeDeclinedStolenCard', // Stolen card - FAIL
            '4000000000000069' => 'pm_card_visa_chargeDeclinedExpiredCard', // Expired card - FAIL
            '4000000000000127' => 'pm_card_visa_chargeDeclinedIncorrectCvc', // Incorrect CVC - FAIL
            '4000000000000119' => 'pm_card_visa_chargeDeclinedProcessingError', // Processing error - FAIL
        ];

        return $testCards[$normalizedCard] ?? null;
    }

    /**
     * Send payment notification to patient
     */
    private function sendPaymentNotification(Payment $payment, string $status): void
    {
        $appointment = $payment->appointment;
        $patient = $appointment->patient;

        $this->notificationService->createPaymentNotification(
            $patient->id,
            $payment->amount,
            $appointment->service->name,
            $status
        );
    }

    /**
     * Get revenue report data
     */
    public function getRevenueReport(array $filters = []): array
    {
        try {
            $startDate = $filters['start_date'] ?? now()->startOfMonth()->format('Y-m-d');
            $endDate = $filters['end_date'] ?? now()->format('Y-m-d');
            $monthFilter = $filters['month_filter'] ?? null;
            $serviceDate = $filters['service_date'] ?? now()->format('Y-m-d');

            // Build base query with date filters
            $baseQuery = Payment::where('payments.status', 'paid')
                ->whereBetween('payments.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

            // Apply month filter if specified
            if ($monthFilter) {
                $baseQuery->whereMonth('payments.created_at', $monthFilter);
            }

            // Get payments grouped by day
            $dailyRevenue = (clone $baseQuery)
                ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get();

            // Get daily revenue by service
            $dailyServiceRevenue = (clone $baseQuery)
                ->join('appointments', 'payments.appointment_id', '=', 'appointments.id')
                ->join('services', 'appointments.service_id', '=', 'services.id')
                ->selectRaw('DATE(payments.created_at) as date, services.name as service_name, SUM(payments.amount) as total')
                ->groupBy('date', 'services.id', 'services.name')
                ->orderBy('date', 'desc')
                ->orderBy('total', 'desc')
                ->get();

            // Get payments grouped by month
            $monthlyRevenue = (clone $baseQuery)
                ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(amount) as total')
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

            // Get revenue by service
            $serviceRevenue = (clone $baseQuery)
                ->join('appointments', 'payments.appointment_id', '=', 'appointments.id')
                ->join('services', 'appointments.service_id', '=', 'services.id')
                ->selectRaw('services.name as service_name, services.id as service_id, SUM(payments.amount) as total, COUNT(payments.id) as count')
                ->groupBy('services.id', 'services.name')
                ->orderBy('total', 'desc')
                ->get();

            // Get monthly revenue by service
            $monthlyServiceRevenueQuery = Payment::where('payments.status', 'paid')
                ->join('appointments', 'payments.appointment_id', '=', 'appointments.id')
                ->join('services', 'appointments.service_id', '=', 'services.id');
            
            // Apply month filter if specified
            if ($monthFilter) {
                $monthlyServiceRevenueQuery->whereMonth('payments.created_at', $monthFilter);
            }
            
            $monthlyServiceRevenue = $monthlyServiceRevenueQuery
                ->selectRaw('services.name as service_name, YEAR(payments.created_at) as year, MONTH(payments.created_at) as month, SUM(payments.amount) as total')
                ->groupBy('services.id', 'services.name', 'year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->orderBy('total', 'desc')
                ->get();

            // Get service revenue for specific date (for pie chart)
            $serviceRevenueByDate = Payment::where('payments.status', 'paid')
                ->whereDate('payments.created_at', $serviceDate)
                ->join('appointments', 'payments.appointment_id', '=', 'appointments.id')
                ->join('services', 'appointments.service_id', '=', 'services.id')
                ->selectRaw('services.name as service_name, services.id as service_id, SUM(payments.amount) as total')
                ->groupBy('services.id', 'services.name')
                ->orderBy('total', 'desc')
                ->get();

            // Calculate total revenue
            $totalRevenue = (clone $baseQuery)->sum('amount');

            return [
                'success' => true,
                'data' => [
                    'daily_revenue' => $dailyRevenue,
                    'daily_service_revenue' => $dailyServiceRevenue,
                    'monthly_revenue' => $monthlyRevenue,
                    'service_revenue' => $serviceRevenue,
                    'monthly_service_revenue' => $monthlyServiceRevenue,
                    'service_revenue_by_date' => $serviceRevenueByDate,
                    'total_revenue' => $totalRevenue,
                    'filters' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'month_filter' => $monthFilter,
                        'service_date' => $serviceDate,
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Revenue Report Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to generate revenue report: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all payments with pagination
     */
    public function getAllPayments(int $perPage = 15): array
    {
        try {
            $payments = Payment::with(['appointment.patient', 'appointment.service'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return [
                'success' => true,
                'payments' => $payments
            ];

        } catch (\Exception $e) {
            Log::error('Get All Payments Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to retrieve payments: ' . $e->getMessage()
            ];
        }
    }
}
