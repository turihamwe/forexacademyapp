<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\YoPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected YoPaymentService $yoPaymentService;

    public function __construct(YoPaymentService $yoPaymentService)
    {
        $this->yoPaymentService = $yoPaymentService;
    }

    /**
     * Yo! Payments Instant Payment Notification (IPN) listener.
     */
    public function yoPayments(Request $request)
    {
        $payload = $request->all();

        Log::info('Yo! Payments IPN received', $payload);

        if (! $this->yoPaymentService->verifyIpnSignature($payload)) {
            Log::warning('Yo! Payments IPN signature verification failed', $payload);

            return response('Invalid signature', 403);
        }

        $externalRef = $payload['external_ref'] ?? null;

        if (! $externalRef) {
            return response('Missing external reference', 400);
        }

        $transaction = Transaction::where('external_reference', $externalRef)->first();

        if (! $transaction) {
            Log::warning('Yo! Payments IPN: transaction not found', ['external_ref' => $externalRef]);

            return response('Transaction not found', 404);
        }

        if ($transaction->status === Transaction::STATUS_COMPLETED) {
            return response('OK', 200);
        }

        DB::transaction(function () use ($transaction, $payload) {
            $transaction->update([
                'status' => Transaction::STATUS_COMPLETED,
                'payment_gateway_ref' => $payload['network_ref'] ?? $transaction->payment_gateway_ref,
            ]);

            $transaction->user->update([
                'subscription_status' => $transaction->subscriptionStatusForType(),
            ]);
        });

        Log::info('User subscription upgraded via IPN', [
            'user_id' => $transaction->user_id,
            'subscription' => $transaction->subscriptionStatusForType(),
        ]);

        return response('OK', 200);
    }
}
