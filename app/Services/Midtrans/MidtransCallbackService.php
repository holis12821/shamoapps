<?php

namespace App\Services\Midtrans;

use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use App\Exceptions\ApiException;

class MidtransCallbackService
{
    public function handle(array $payload): void
    {
        $this->validatePayload($payload);
        $this->verifySignature($payload);

        $transaction = $this->getTransaction($payload['order_id']);
        $payment     = $this->getPayment($transaction);

        $this->ensureIdempotent($payment);

        $this->updatePayment($payment, $payload);
        $this->updateTransaction($transaction, $payload);

        Log::info('Midtrans Callback SUCCESS', [
            'order_number' => $transaction->order_number,
            'status'       => $payload['transaction_status'],
        ]);
    }

    /* =====================================================
     | BASIC PAYLOAD VALIDATION
     |===================================================== */
    private function validatePayload(array $payload): void
    {
        $required = [
            'order_id',
            'status_code',
            'gross_amount',
            'transaction_status',
            'signature_key',
        ];

        foreach ($required as $field) {
            if (empty($payload[$field])) {
                throw new ApiException(
                    "Missing payload field: {$field}",
                    422
                );
            }
        }
    }

    /* =====================================================
     | SIGNATURE VERIFICATION (WAJIB TEPAT)
     |===================================================== */
    private function verifySignature(array $payload): void
    {
        $serverKey = config('midtrans.server_key');

        if (! $serverKey) {
            throw new ApiException(
                'Midtrans server key not configured',
                500
            );
        }

        $rawString =
            $payload['order_id'] .
            $payload['status_code'] .
            $payload['gross_amount'] .
            $serverKey;

        $expected = hash('sha512', $rawString);

        if (! hash_equals($expected, $payload['signature_key'])) {
            Log::warning('Midtrans Invalid Signature', [
                'raw'      => $rawString,
                'expected' => $expected,
                'received' => $payload['signature_key'],
            ]);

            throw new ApiException('Invalid signature', 403);
        }
    }

    /* =====================================================
     | RESOLVE TRANSACTION & PAYMENT
     |===================================================== */
    private function getTransaction(string $orderNumber): Transaction
    {
        $transaction = Transaction::where(
            'order_number',
            $orderNumber
        )->first();

        if (! $transaction) {
            throw new ApiException(
                'Transaction not found',
                404
            );
        }

        return $transaction;
    }

    private function getPayment(Transaction $transaction)
    {
        if (! $transaction->payment) {
            throw new ApiException(
                'Payment not found',
                404
            );
        }

        return $transaction->payment;
    }

    /* =====================================================
     | IDEMPOTENCY (ANTI DOUBLE CALLBACK)
     |===================================================== */
    private function ensureIdempotent($payment): void
    {
        if ($payment->status === 'paid') {
            Log::info(
                'Midtrans Callback IGNORED (Already Paid)',
                ['payment_id' => $payment->id]
            );

            throw new ApiException(
                'Callback already processed',
                200
            );
        }
    }

    /* =====================================================
     | UPDATE PAYMENT
     |===================================================== */
    private function updatePayment($payment, array $payload): void
    {
        $payment->update([
            'midtrans_transaction_id' => $payload['transaction_id'] ?? null,
            'payment_type'            => $payload['payment_type'] ?? null,
            'status'                  => $this->mapPaymentStatus(
                $payload['transaction_status']
            ),
            'fraud_status'            => $payload['fraud_status'] ?? null,
            'transaction_time'        => $payload['transaction_time'] ?? null,
            'settlement_time'         => $payload['settlement_time'] ?? null,
            'payload'                 => $payload,
        ]);
    }

    /* =====================================================
     | UPDATE TRANSACTION
     |===================================================== */
    private function updateTransaction(
        Transaction $transaction,
        array $payload
    ): void {
        match ($payload['transaction_status']) {
            'capture'    => $this->handleCapture($transaction, $payload),
            'settlement' => $transaction->update([
                'status' => Transaction::STATUS_PAID
            ]),
            'pending'    => $transaction->update([
                'status' => Transaction::STATUS_PENDING
            ]),
            'expire',
            'cancel',
            'deny'       => $transaction->update([
                'status' => Transaction::STATUS_FAILED
            ]),
            'refund'     => $transaction->update([
                'status' => Transaction::STATUS_REFUNDED
            ]),
            default      => null,
        };
    }

    private function handleCapture(
        Transaction $transaction,
        array $payload
    ): void {
        if (($payload['payment_type'] ?? '') !== 'credit_card') {
            return;
        }

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