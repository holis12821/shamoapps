<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    public function handle(Request $request)
    {
        /**
         * ===============================
         * 1. Get Payload Midtrans
         * ===============================
         */
        $payload = $request->all();

        /**
         * ===============================
         * 2. Verify Midtrans Signature
         * ===============================
         * Official Formula Midtrans:
         * sha512(order_id + status_code + gross_amount + server_key)
         */
        $serverKey = config('midtrans.server_key');

        $orderId     = (string) ($payload['order_id'] ?? '');
        $statusCode  = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signature   = (string) ($payload['signature_key'] ?? '');

        $rawString = $orderId . $statusCode . $grossAmount . $serverKey;

        $expectedSignature = hash('sha512', $rawString);

        if (! hash_equals($expectedSignature, $signature)) {
            Log::warning('Midtrans Invalid Signature', [
                'payload'   => $payload,
                'raw_string'=> $rawString,
                'expected'  => $expectedSignature,
                'received'  => $signature,
            ]);

            return ResponseFormatter::error(
                null,
                'Invalid signature',
                403
            );
        }

        /**
         * ===============================
         * 3. Get Transaction
         * ===============================
         * order_id Midtrans = order_number di sistem kita
         */
        $transaction = Transaction::where('order_number', $orderId)->first();

        if (! $transaction) {
            Log::error('Transaction not found', [
                'order_id' => $orderId
            ]);

            return ResponseFormatter::error(
                null,
                'Transaction not found',
                404
            );
        }

        $payment = $transaction->payment;

        /**
         * ===============================
         * 4. Idempotency Protection
         * ===============================
         * Hindari double callback (Midtrans sering retry)
         */
        if ($payment && in_array($payment->status, ['paid', 'settlement'])) {
            return ResponseFormatter::success(
                null,
                'Callback already processed'
            );
        }

        /**
         * ===============================
         * 5. Update Payment Snapshot
         * ===============================
         */
        $payment->update([
            'midtrans_transaction_id' => $payload['transaction_id'] ?? null,
            'payment_type'            => $payload['payment_type'] ?? null,
            'status'                  => $this->mapPaymentStatus(
                $payload['transaction_status'] ?? 'pending'
            ),
            'fraud_status'            => $payload['fraud_status'] ?? null,
            'transaction_time'        => $payload['transaction_time'] ?? null,
            'settlement_time'         => $payload['settlement_time'] ?? null,
            'payload'                 => $payload,
        ]);

        /**
         * ===============================
         * 6. Update Transaction Status
         * ===============================
         */
        match ($payload['transaction_status'] ?? null) {
            'capture' => $this->handleCapture($transaction, $payload),
            'settlement' => $transaction->update([
                'status' => Transaction::STATUS_PAID
            ]),
            'pending' => $transaction->update([
                'status' => Transaction::STATUS_PENDING
            ]),
            'expire', 'cancel', 'deny' => $transaction->update([
                'status' => Transaction::STATUS_FAILED
            ]),
            'refund' => $transaction->update([
                'status' => Transaction::STATUS_REFUNDED
            ]),
            default => null,
        };

        /**
         * ===============================
         * 7. Response 200
         * ===============================
         * Midtrans will retry if not 200.
         */
        return ResponseFormatter::success(
            null,
            'Callback processed successfully'
        );
    }

    /**
     * ===============================
     * Handle Capture (Credit Card)
     * ===============================
     */
    private function handleCapture(Transaction $transaction, array $payload): void
    {
        if (($payload['payment_type'] ?? '') === 'credit_card') {
            if (($payload['fraud_status'] ?? '') === 'challenge') {
                $transaction->update([
                    'status' => Transaction::STATUS_PENDING
                ]);
            } else {
                $transaction->update([
                    'status' => Transaction::STATUS_PAID
                ]);
            }
        }
    }

    /**
     * ===============================
     * Map Payment Status
     * ===============================
     */
    private function mapPaymentStatus(string $status): string
    {
        return match ($status) {
            'settlement' => 'paid',
            'pending'    => 'pending',
            'expire'     => 'expired',
            'cancel',
            'deny'       => 'failed',
            'refund'     => 'refunded',
            default      => 'pending',
        };
    }
}