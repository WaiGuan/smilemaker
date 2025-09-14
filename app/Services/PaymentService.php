<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Appointment;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct()
    {
        // Initialize Stripe with secret key
        Stripe::setApiKey(config('services.stripe.secret'));
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

                // Send notification to patient
                $this->sendPaymentNotification($payment, 'success');

                return [
                    'success' => true,
                    'message' => 'Payment confirmed successfully',
                    'payment' => $payment,
                ];
            } else {
                $payment->update([
                    'status' => 'failed',
                    'stripe_payment_intent_id' => $paymentIntentId,
                ]);

                // Send notification to patient
                $this->sendPaymentNotification($payment, 'failed');

                return [
                    'success' => false,
                    'error' => 'Payment failed: ' . $paymentIntent->status,
                    'payment' => $payment,
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

        if ($status === 'success') {
            $message = "Your payment of RM" . number_format($payment->amount, 2) . " for {$appointment->service->name} has been processed successfully.";
        } else {
            $message = "Your payment of RM" . number_format($payment->amount, 2) . " for {$appointment->service->name} has failed. Please try again.";
        }

        \App\Models\Notification::create([
            'user_id' => $patient->id,
            'message' => $message,
        ]);
    }
}
