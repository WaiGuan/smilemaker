<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Appointment;
use App\Services\PaymentService;
use App\Services\NotificationService;
use App\Http\Resources\PaymentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $paymentService;
    protected $notificationService;

    public function __construct(PaymentService $paymentService, NotificationService $notificationService)
    {
        $this->paymentService = $paymentService;
        $this->notificationService = $notificationService;
    }

    /**
     * Show payment form for an appointment
     */
    public function show(Appointment $appointment)
    {
        // Check if user can view this payment
        $user = Auth::user();
        if (!$user->isAdmin() && $appointment->patient_id !== $user->id) {
            abort(403, 'Unauthorized access to payment.');
        }

        $payment = $appointment->payment;

        if (!$payment) {
            return redirect()->back()
                ->with('error', 'No payment found for this appointment.');
        }

        // Check if Stripe is configured
        $isStripeConfigured = $this->paymentService->isStripeConfigured();

        // If Stripe is configured, create payment intent
        $paymentIntent = null;
        if ($isStripeConfigured && $payment->status === 'pending') {
            $result = $this->paymentService->createPaymentIntent($appointment);
            if ($result['success']) {
                $paymentIntent = $result;
            }
        }

        return view('payments.show', compact('payment', 'appointment', 'isStripeConfigured', 'paymentIntent'));
    }

    /**
     * Process payment (demo mode or Stripe confirmation)
     */
    public function process(Request $request, Payment $payment)
    {
        $user = Auth::user();

        // Check if user can process this payment
        if (!$user->isAdmin() && $payment->appointment->patient_id !== $user->id) {
            abort(403, 'Unauthorized to process this payment.');
        }

        // Check if Stripe is configured
        if ($this->paymentService->isStripeConfigured()) {
            // Handle manual Stripe payment
            if ($request->input('stripe_mode')) {
                $result = $this->processManualStripePayment($request, $payment);

                if ($result['success']) {
                    return redirect()->back()
                        ->with('success', 'Payment processed successfully!');
                } else {
                    return redirect()->back()
                        ->with('error', $result['error']);
                }
            }

            // Handle Stripe payment confirmation
            $paymentIntentId = $request->input('payment_intent_id');

            if ($paymentIntentId) {
                $result = $this->paymentService->confirmPayment($paymentIntentId);

                if ($result['success']) {
                    return redirect()->back()
                        ->with('success', 'Payment processed successfully!');
                } else {
                    return redirect()->back()
                        ->with('error', $result['error']);
                }
            }
        } else {
            // Demo mode - simulate payment
            $result = $this->paymentService->simulatePayment($payment->appointment);

            if ($result['success']) {
                return redirect()->back()
                    ->with('success', 'Demo payment processed successfully!');
            } else {
                return redirect()->back()
                    ->with('error', $result['error']);
            }
        }

        return redirect()->back()
            ->with('error', 'Payment processing failed.');
    }

    /**
     * Process manual Stripe payment (CSP-friendly)
     */
    private function processManualStripePayment(Request $request, Payment $payment)
    {
        try {
            // Validate card data
            $cardNumber = $request->input('card_number');
            $expiry = $request->input('expiry');
            $cvc = $request->input('cvc');
            $cardholderName = $request->input('cardholder_name');
            $email = $request->input('email');

            // Parse expiry date
            $expiryParts = explode('/', $expiry);
            if (count($expiryParts) !== 2) {
                return [
                    'success' => false,
                    'error' => 'Invalid expiry date format. Use MM/YY format.'
                ];
            }

            $expMonth = (int) trim($expiryParts[0]);
            $expYear = (int) ('20' . trim($expiryParts[1]));

            // Create Stripe Payment Intent
            $result = $this->paymentService->createPaymentIntentWithCard(
                $payment->appointment,
                $cardNumber,
                $expMonth,
                $expYear,
                $cvc,
                $cardholderName,
                $email
            );

            // Log the result for debugging
            \Log::info('Stripe payment result:', $result);

            if ($result['success']) {
                // Payment succeeded
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'stripe_payment_intent_id' => $result['payment_intent_id'],
                ]);

                // Send notification
                $this->sendPaymentNotification($payment, 'success');

                return [
                    'success' => true,
                    'message' => 'Payment processed successfully!'
                ];
            } else {
                // Payment failed - update status and return error
                $payment->update(['status' => 'failed']);
                $this->sendPaymentNotification($payment, 'failed');

                return [
                    'success' => false,
                    'error' => $result['error']
                ];
            }

        } catch (\Exception $e) {
            \Log::error('Manual Stripe payment error: ' . $e->getMessage());
            $payment->update(['status' => 'failed']);
            $this->sendPaymentNotification($payment, 'failed');

            return [
                'success' => false,
                'error' => 'Payment processing failed. Please try again.'
            ];
        }
    }

    /**
     * Send payment notification
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
     * Handle Stripe payment confirmation
     */
    public function confirmPayment(Request $request)
    {
        $paymentIntentId = $request->input('payment_intent_id');

        if (!$paymentIntentId) {
            return response()->json(['error' => 'Payment intent ID required'], 400);
        }

        $result = $this->paymentService->confirmPayment($paymentIntentId);

        return response()->json($result);
    }

    /**
     * Show revenue report (admin only)
     */
    public function revenueReport(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            abort(403, 'Unauthorized access to revenue report.');
        }

        $filters = [
            'start_date' => $request->get('start_date', now()->startOfMonth()->format('Y-m-d')),
            'end_date' => $request->get('end_date', now()->format('Y-m-d')),
            'month_filter' => $request->get('month_filter'),
            'service_date' => $request->get('service_date', now()->format('Y-m-d')),
        ];

        $result = $this->paymentService->getRevenueReport($filters);

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['error']);
        }

        $data = $result['data'];
        $filters = $data['filters'];

        return view('payments.revenue-report', compact(
            'data',
            'filters'
        ));
    }

    /**
     * Show all payments (admin only)
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            abort(403, 'Unauthorized access to payments.');
        }

        $result = $this->paymentService->getAllPayments();

        if ($result['success']) {
            $payments = $result['payments'];
        } else {
            $payments = collect();
        }

        return view('payments.index', compact('payments'));
    }

    // ==================== API METHODS ====================

    /**
     * API: Display a listing of payments
     */
    public function apiIndex(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to payments.'
            ], 403);
        }

        $perPage = $request->get('per_page', 15);
        $result = $this->paymentService->getAllPayments($perPage);
        
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error']
            ], 400);
        }
        
        $payments = $result['payments'];

        return response()->json([
            'success' => true,
            'data' => PaymentResource::collection($payments),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ]
        ], 200);
    }

    /**
     * API: Display the specified payment
     */
    public function apiShow(Appointment $appointment)
    {
        $user = Auth::user();
        
        if (!$user->isAdmin() && $appointment->patient_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to payment.'
            ], 403);
        }

        $payment = $appointment->payment;

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'No payment found for this appointment.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new PaymentResource($payment->load('appointment'))
        ], 200);
    }

    /**
     * API: Process payment
     */
    public function apiProcess(Request $request, Payment $payment)
    {
        $user = Auth::user();

        if (!$user->isAdmin() && $payment->appointment->patient_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to process this payment.'
            ], 403);
        }

        if ($this->paymentService->isStripeConfigured()) {
            if ($request->input('stripe_mode')) {
                $result = $this->processManualStripePayment($request, $payment);

                return response()->json($result, $result['success'] ? 200 : 400);
            }

            $paymentIntentId = $request->input('payment_intent_id');

            if ($paymentIntentId) {
                $result = $this->paymentService->confirmPayment($paymentIntentId);
                return response()->json($result, $result['success'] ? 200 : 400);
            }
        } else {
            $result = $this->paymentService->simulatePayment($payment->appointment);
            return response()->json($result, $result['success'] ? 200 : 400);
        }

        return response()->json([
            'success' => false,
            'message' => 'Payment processing failed.'
        ], 400);
    }

    /**
     * API: Confirm payment
     */
    public function apiConfirmPayment(Request $request)
    {
        $paymentIntentId = $request->input('payment_intent_id');

        if (!$paymentIntentId) {
            return response()->json([
                'success' => false,
                'message' => 'Payment intent ID required'
            ], 400);
        }

        $result = $this->paymentService->confirmPayment($paymentIntentId);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * API: Get revenue report
     */
    public function apiRevenueReport(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to revenue report.'
            ], 403);
        }

        $filters = [
            'start_date' => $request->get('start_date', now()->startOfMonth()->format('Y-m-d')),
            'end_date' => $request->get('end_date', now()->format('Y-m-d')),
            'month_filter' => $request->get('month_filter'),
            'service_date' => $request->get('service_date', now()->format('Y-m-d')),
        ];

        $result = $this->paymentService->getRevenueReport($filters);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error']
            ], 400);
        }

        $data = $result['data'];

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }
}
