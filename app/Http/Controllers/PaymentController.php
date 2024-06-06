<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/payments",
     *     tags={"Payments"},
     *     summary="Create a new payment request",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount","currency","customer_email"},
     *             @OA\Property(property="amount", type="number", format="float"),
     *             @OA\Property(property="currency", type="string", maxLength=3),
     *             @OA\Property(property="customer_email", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment request created",
     *         @OA\JsonContent(
     *             @OA\Property(property="redirect_url", type="string")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function createPayment(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'currency' => 'required|string|max:3',
            'customer_email' => 'required|email',
        ]);

        $payment = Payment::create([
            'transaction_id' => Str::uuid(),
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'customer_email' => $validated['customer_email'],
        ]);

        // Generate redirect URL for PayFast
        $payfastData = [
            'merchant_id' => env('PAYFAST_MERCHANT_ID'),
            'merchant_key' => env('PAYFAST_MERCHANT_KEY'),
            'return_url' => env('PAYFAST_RETURN_URL'),
            'cancel_url' => env('PAYFAST_CANCEL_URL'),
            'notify_url' => env('PAYFAST_NOTIFY_URL'),
            'amount' => number_format($payment->amount, 2, '.', ''),
            'item_name' => 'Payment for Order #' . $payment->transaction_id,
            'email_address' => $payment->customer_email,
        ];

        // Create a signature for the data
        $signature = md5(http_build_query($payfastData) . '&passphrase=' . env('PAYFAST_PASSPHRASE'));
        $payfastData['signature'] = $signature;

        $query = http_build_query($payfastData);

        return response()->json([
            'redirect_url' => 'https://www.payfast.co.za/eng/process?' . $query,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/payments/ipn",
     *     tags={"Payments"},
     *     summary="Handle IPN notifications",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"pf_payment_id","amount_gross"},
     *             @OA\Property(property="pf_payment_id", type="string"),
     *             @OA\Property(property="amount_gross", type="number", format="float")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="IPN handled",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function handleIPN(Request $request)
    {
        // Mock validation and payload verification for simplicity
        $mockResponse = [
            'payment_status' => 'COMPLETE',
            'transaction_id' => $request->input('pf_payment_id'),
            'amount' => $request->input('amount_gross'),
        ];

        // Verify the transaction ID and amount
        $payment = Payment::where('transaction_id', $mockResponse['transaction_id'])
            ->where('amount', $mockResponse['amount'])
            ->first();

        if ($payment) {
            $payment->status = 'completed';
            $payment->save();
        }

        return response()->json(['status' => 'success']);
    }
}
