<?php

namespace App\Http\Controllers;

use App\Services\YoPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected YoPaymentService $yoPaymentService;

    public function __construct(YoPaymentService $yoPaymentService)
    {
        $this->yoPaymentService = $yoPaymentService;
    }

    /**
     * Yo! Payments Instant Payment Notification (IPN) — API v3.44 section 6.3.
     * Must always return HTTP 200 when received to stop retries.
     */
    public function yoPayments(Request $request)
    {
        $payload = $request->all();

        Log::info('Yo! Payments IPN received', $payload);

        if (! $this->yoPaymentService->verifyIpnSignature($payload)) {
            return response('Invalid signature', 403);
        }

        $result = $this->yoPaymentService->processSuccessfulIpn($payload);

        if (! $result['ok']) {
            return response($result['message'], $result['http_code']);
        }

        // Optional SMS to payer — API section 6.3.2
        if (! empty($result['sms_narrative']) && config('services.yo_payments.ipn_sms_response', true)) {
            return response('narrative=' . rawurlencode($result['sms_narrative']), 200)
                ->header('Content-Type', 'application/x-www-form-urlencoded');
        }

        return response('OK', 200);
    }

    /**
     * Yo! Payments Transaction Failure Notification — API v3.44 section 6.4.
     */
    public function yoPaymentsFailure(Request $request)
    {
        $payload = $request->all();

        Log::info('Yo! Payments failure notification received', $payload);

        if (! $this->yoPaymentService->verifyFailureNotificationSignature($payload)) {
            return response('Invalid verification signature', 403);
        }

        $result = $this->yoPaymentService->processFailureNotification($payload);

        return response($result['message'], $result['http_code']);
    }
}
