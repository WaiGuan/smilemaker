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
    public function revenueReport(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            abort(403, 'Unauthorized access to revenue report.');
        }

        // Get filter parameters
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $monthFilter = $request->get('month_filter');
        $serviceDate = $request->get('service_date', now()->format('Y-m-d'));

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

        return view('payments.revenue-report', compact(
            'dailyRevenue', 
            'dailyServiceRevenue',
            'monthlyRevenue', 
            'totalRevenue',
            'serviceRevenue',
            'monthlyServiceRevenue',
            'serviceRevenueByDate',
            'startDate',
            'endDate',
            'monthFilter',
            'serviceDate'
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

        $payments = Payment::with(['appointment.patient', 'appointment.service'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('payments.index', compact('payments'));
    }
}
