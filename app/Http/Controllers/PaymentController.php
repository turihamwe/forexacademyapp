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
     * Initiate a mobile money payment via Yo! Payments.
     */
    public function initiate(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:full_100,daily_4'],
            'phone_number' => ['nullable', 'string', 'max:20'],
        ]);

        $user = Auth::user();
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

        $msisdn = $validated['phone_number'] ?? $user->phone_number;
        $narrative = $type === Transaction::TYPE_FULL
            ? 'Forex Academy Full Course Payment'
            : 'Forex Academy Daily Module Payment';

        $response = $this->yoPaymentService->depositFunds(
            $msisdn,
            $amount,
            $narrative,
            $externalReference,
            route('webhooks.yo-payments')
        );

        if (! empty($response['TransactionReference'])) {
            $transaction->update([
                'payment_gateway_ref' => $response['TransactionReference'],
            ]);
        }

        return response()->json([
            'message' => 'Payment initiated. Approve the prompt on your phone.',
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
