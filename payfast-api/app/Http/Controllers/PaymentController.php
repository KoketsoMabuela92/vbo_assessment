<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function createPayment(Request $request) {
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

    public function handleIPN(Request $request) {
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
