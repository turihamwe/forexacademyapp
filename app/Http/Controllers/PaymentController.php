<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Transaction;
use App\Services\YoPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected YoPaymentService $yoPaymentService;

    public function __construct(YoPaymentService $yoPaymentService)
    {
        $this->yoPaymentService = $yoPaymentService;
    }

    /**
     * Initiate a pull-method mobile money deposit via Yo! Payments (section 6.1).
     */
    public function initiate(Request $request)
    {
        if (! $this->yoPaymentService->isConfigured()) {
            return response()->json([
                'message' => 'Payment gateway is not configured. Please contact support.',
            ], 503);
        }

        $validated = $request->validate([
            'type' => ['required', 'in:full_100,daily_4'],
            'phone_number' => ['nullable', 'string', 'max:20'],
        ]);

        $user = Auth::user();
        $msisdn = $validated['phone_number'] ?? $user->phone_number;

        if (! $msisdn) {
            return response()->json([
                'message' => 'A phone number is required for mobile money payment.',
            ], 422);
        }

        $type = $validated['type'];
        $amount = $type === Transaction::TYPE_FULL
            ? Setting::coursePriceFull()
            : Setting::coursePriceDaily();

        $externalReference = 'FXA-' . Str::upper(Str::random(12));

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'external_reference' => $externalReference,
            'type' => $type,
            'status' => Transaction::STATUS_PENDING,
        ]);

        $narrative = $type === Transaction::TYPE_FULL
            ? 'Forex Academy Full Course Payment'
            : 'Forex Academy Daily Module Payment';

        $response = $this->yoPaymentService->depositFunds(
            $msisdn,
            $amount,
            $narrative,
            $externalReference,
            route('webhooks.yo-payments'),
            route('webhooks.yo-payments.failure')
        );

        if (! empty($response['TransactionReference'])) {
            $transaction->update([
                'yo_transaction_ref' => $response['TransactionReference'],
                'payment_gateway_ref' => $response['TransactionReference'],
            ]);
        }

        if ($this->yoPaymentService->isFailedGatewayResponse($response)) {
            $transaction->update(['status' => Transaction::STATUS_FAILED]);

            return response()->json([
                'message' => $response['ErrorMessage'] ?? $response['StatusMessage'] ?? 'Payment could not be initiated.',
                'transaction' => $transaction->fresh(),
                'gateway_response' => $response,
            ], 422);
        }

        $message = $this->yoPaymentService->isPendingGatewayResponse($response)
            ? 'Payment request sent. Approve the prompt on your phone to complete.'
            : 'Payment initiated successfully.';

        return response()->json([
            'message' => $message,
            'transaction' => $transaction->fresh(),
            'gateway_response' => $response,
        ]);
    }

    public function pricing()
    {
        return response()->json([
            'course_price_full' => Setting::coursePriceFull(),
            'course_price_daily' => Setting::coursePriceDaily(),
        ]);
    }
}
