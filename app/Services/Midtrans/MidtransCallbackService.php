<?php

namespace App\Services\Midtrans;

use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use App\Exceptions\ApiException;

class MidtransCallbackService
{
   public function handle(array $payload): void
    {
        $this->verifySignature($payload);

        $transaction = $this->getTransaction($payload['order_id'] ?? null);

        $payment = $this->getPayment($transaction);

        $this->ensureIdempotent($payment);

        $this->updatePayment($payment, $payload);

        $this->updateTransaction($transaction, $payload);

        Log::info('Midtrans Callback Processed', [
            'order_number' => $transaction->order_number,
            'status' => $payload['transaction_status'] ?? null,
        ]);
    }

    /* ============================================================
     | Signature Validation
     |============================================================ */
    private function verifySignature(array $payload): void
    {
        $serverKey = config('midtrans.server_key');

        $orderId     = (string) ($payload['order_id'] ?? '');
        $statusCode  = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signature   = (string) ($payload['signature_key'] ?? '');

        $raw = $orderId . $statusCode . $grossAmount . $serverKey;
        $expected = hash('sha512', $raw);

        if (! hash_equals($expected, $signature)) {
            Log::warning('Midtrans Invalid Signature', [
                'raw' => $raw,
                'expected' => $expected,
                'received' => $signature,
            ]);

            throw new ApiException('Invalid signature', 403);
        }
    }

    /* ============================================================
     | Transaction & Payment Resolver
     |============================================================ */
    private function getTransaction(?string $orderNumber): Transaction
    {
        $transaction = Transaction::where('order_number', $orderNumber)->first();

        if (! $transaction) {
            throw new ApiException('Transaction not found', 404);
        }

        return $transaction;
    }

    private function getPayment(Transaction $transaction)
    {
        $payment = $transaction->payment;

        if (! $payment) {
            throw new ApiException('Payment not found', 404);
        }

        return $payment;
    }

    /* ============================================================
     | Idempotency Guard
     |============================================================ */
    private function ensureIdempotent($payment): void
    {
        if ($payment->status === 'paid') {
            Log::info('Midtrans Callback Ignored (Already Paid)');
            exit; // STOP — Midtrans retry safe
        }
    }

    /* ============================================================
     | Payment Update
     |============================================================ */
    private function updatePayment($payment, array $payload): void
    {
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
    }

    /* ============================================================
     | Transaction Update
     |============================================================ */
    private function updateTransaction(Transaction $transaction, array $payload): void
    {
        match ($payload['transaction_status'] ?? null) {
            'capture' => $this->handleCapture($transaction, $payload),

            'settlement' => $transaction->update([
                'status' => Transaction::STATUS_PAID,
            ]),

            'pending' => $transaction->update([
                'status' => Transaction::STATUS_PENDING,
            ]),

            'expire', 'cancel', 'deny' => $transaction->update([
                'status' => Transaction::STATUS_FAILED,
            ]),

            'refund' => $transaction->update([
                'status' => Transaction::STATUS_REFUNDED,
            ]),

            default => null,
        };
    }

    private function handleCapture(Transaction $transaction, array $payload): void
    {
        if (($payload['payment_type'] ?? '') !== 'credit_card') {
            return;
        }

        if (($payload['fraud_status'] ?? '') === 'challenge') {
            $transaction->update(['status' => Transaction::STATUS_PENDING]);
        } else {
            $transaction->update(['status' => Transaction::STATUS_PAID]);
        }
    }

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
