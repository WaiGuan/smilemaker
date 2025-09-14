<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Appointment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
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

        if ($status === 'success') {
            $message = "Your payment of $" . number_format($payment->amount, 2) . " for {$appointment->service->name} has been processed successfully.";
        } else {
            $message = "Your payment of $" . number_format($payment->amount, 2) . " for {$appointment->service->name} has failed. Please try again.";
        }

        \App\Models\Notification::create([
            'user_id' => $patient->id,
            'message' => $message,
        ]);
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
    public function revenueReport()
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            abort(403, 'Unauthorized access to revenue report.');
        }

        // Get payments grouped by day
        $dailyRevenue = Payment::where('status', 'paid')
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        // Get payments grouped by month
        $monthlyRevenue = Payment::where('status', 'paid')
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(amount) as total')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        // Calculate total revenue
        $totalRevenue = Payment::where('status', 'paid')->sum('amount');

        return view('payments.revenue-report', compact('dailyRevenue', 'monthlyRevenue', 'totalRevenue'));
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

        $payments = Payment::with(['appointment.patient', 'appointment.service'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('payments.index', compact('payments'));
    }
}
