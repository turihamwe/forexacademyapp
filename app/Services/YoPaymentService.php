<?php

namespace App\Services;

use App\Models\Transaction;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

/**
 * Yo! Payments API v3.44 integration (Pull deposit + IPN + failure notifications).
 *
 * @see storage/yo_api_v344.txt (extracted from official API guide)
 */
class YoPaymentService
{
    public const DEPOSIT_TYPE_PULL = 'PULL';
    public const DEPOSIT_TYPE_PUSH = 'PUSH';

    protected Client $client;
    protected string $username;
    protected string $password;
    protected string $apiUrl;
    protected string $publicKeyPath;
    protected bool $sandbox;

    public function __construct()
    {
        $this->sandbox = filter_var(config('services.yo_payments.sandbox', true), FILTER_VALIDATE_BOOLEAN);
        $this->username = (string) (config('services.yo_payments.username') ?? '');
        $this->password = (string) (config('services.yo_payments.password') ?? '');
        $this->apiUrl = (string) ($this->sandbox
            ? config('services.yo_payments.sandbox_url')
            : config('services.yo_payments.production_url'));
        $this->publicKeyPath = (string) ($this->sandbox
            ? config('services.yo_payments.sandbox_public_key')
            : config('services.yo_payments.production_public_key'));

        $this->client = new Client([
            'timeout' => 120,
            'connect_timeout' => 120,
            'verify' => ! $this->sandbox,
        ]);
    }

    public function isConfigured(): bool
    {
        return $this->username !== '' && $this->password !== '';
    }

    /**
     * Pull-method deposit (acdepositfunds) — API section 6.1.
     */
    public function depositFunds(
        string $msisdn,
        float $amount,
        string $narrative,
        string $externalReference,
        ?string $instantNotificationUrl = null,
        ?string $failureNotificationUrl = null
    ): array {
        if (! $this->isConfigured()) {
            return [
                'Status' => 'ERROR',
                'StatusMessage' => 'Yo! Payments credentials are not configured.',
            ];
        }

        $xml = $this->buildDepositXml(
            $msisdn,
            $amount,
            $narrative,
            $externalReference,
            $instantNotificationUrl,
            $failureNotificationUrl
        );

        return $this->sendXmlRequest($xml, $externalReference);
    }

    /**
     * Check transaction status (actransactioncheckstatus) — API section 7.
     */
    public function checkTransactionStatus(
        ?string $transactionReference = null,
        ?string $privateTransactionReference = null,
        string $depositTransactionType = self::DEPOSIT_TYPE_PULL
    ): array {
        if (! $this->isConfigured()) {
            return [
                'Status' => 'ERROR',
                'StatusMessage' => 'Yo! Payments credentials are not configured.',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<AutoCreate><Request>';
        $xml .= '<APIUsername>' . htmlspecialchars($this->username) . '</APIUsername>';
        $xml .= '<APIPassword>' . htmlspecialchars($this->password) . '</APIPassword>';
        $xml .= '<Method>actransactioncheckstatus</Method>';

        if ($transactionReference) {
            $xml .= '<TransactionReference>' . htmlspecialchars($transactionReference) . '</TransactionReference>';
        }

        if ($privateTransactionReference) {
            $xml .= '<PrivateTransactionReference>' . htmlspecialchars($privateTransactionReference) . '</PrivateTransactionReference>';
        }

        $xml .= '<DepositTransactionType>' . htmlspecialchars($depositTransactionType) . '</DepositTransactionType>';
        $xml .= '</Request></AutoCreate>';

        return $this->sendXmlRequest($xml, $privateTransactionReference ?? $transactionReference ?? 'status-check');
    }

    /**
     * Verify IPN signature — API section 6.3.4 (SHA1).
     */
    public function verifyIpnSignature(array $payload): bool
    {
        $signature = $this->payloadValue($payload, ['signature', 'Signature']);

        if ($signature === null) {
            return $this->allowWithoutSignature('IPN missing signature');
        }

        if (! file_exists($this->publicKeyPath)) {
            return $this->allowWithoutSignature('IPN public key certificate not found');
        }

        $data = ($this->payloadValue($payload, ['date_time']) ?? '')
            . ($this->payloadValue($payload, ['amount']) ?? '')
            . ($this->payloadValue($payload, ['narrative']) ?? '')
            . ($this->payloadValue($payload, ['network_ref']) ?? '')
            . ($this->payloadValue($payload, ['external_ref']) ?? '')
            . ($this->payloadValue($payload, ['msisdn', 'Msisdn']) ?? '');

        return $this->verifyRsaSha1Signature($data, $signature);
    }

    /**
     * Verify failure notification — API section 6.4.3 (SHA1).
     */
    public function verifyFailureNotificationSignature(array $payload): bool
    {
        $signature = $this->payloadValue($payload, ['verification', 'Verification']);

        if ($signature === null) {
            return $this->allowWithoutSignature('Failure notification missing verification');
        }

        if (! file_exists($this->publicKeyPath)) {
            return $this->allowWithoutSignature('Failure notification public key not found');
        }

        $initDate = $this->payloadValue($payload, ['transaction_init_date', 'transaction_date']);

        $data = ($this->payloadValue($payload, ['failed_transaction_reference']) ?? '')
            . ($initDate ?? '');

        return $this->verifyRsaSha1Signature($data, $signature);
    }

    public function isSuccessfulGatewayResponse(array $response): bool
    {
        return ($response['Status'] ?? '') === 'OK'
            && (int) ($response['StatusCode'] ?? -1) === 0
            && ($response['TransactionStatus'] ?? 'SUCCEEDED') === 'SUCCEEDED';
    }

    public function isPendingGatewayResponse(array $response): bool
    {
        return ($response['Status'] ?? '') === 'OK'
            && (int) ($response['StatusCode'] ?? -1) === 1;
    }

    public function isFailedGatewayResponse(array $response): bool
    {
        return ($response['Status'] ?? '') === 'ERROR'
            || in_array($response['TransactionStatus'] ?? '', ['FAILED', 'INDETERMINATE'], true);
    }

    /**
     * Process a successful IPN callback — API section 6.3.
     */
    public function processSuccessfulIpn(array $payload): array
    {
        $externalRef = $this->payloadValue($payload, ['external_ref']);

        if (! $externalRef) {
            return ['ok' => false, 'http_code' => 400, 'message' => 'Missing external_ref'];
        }

        $transaction = Transaction::where('external_reference', $externalRef)->first();

        if (! $transaction) {
            Log::warning('Yo! IPN: transaction not found', ['external_ref' => $externalRef]);

            return ['ok' => false, 'http_code' => 404, 'message' => 'Transaction not found'];
        }

        $networkRef = $this->payloadValue($payload, ['network_ref']);
        $msisdn = $this->payloadValue($payload, ['msisdn', 'Msisdn']);
        $ipnAmount = $this->payloadValue($payload, ['amount']);

        if ($this->isDuplicateIpn($networkRef, $msisdn, $transaction)) {
            return ['ok' => true, 'http_code' => 200, 'message' => 'Duplicate IPN ignored', 'duplicate' => true];
        }

        if ($ipnAmount !== null && ! $this->amountsMatch($transaction->amount, $ipnAmount)) {
            Log::warning('Yo! IPN: amount mismatch', [
                'expected' => $transaction->amount,
                'received' => $ipnAmount,
                'external_ref' => $externalRef,
            ]);

            return ['ok' => false, 'http_code' => 422, 'message' => 'Amount mismatch'];
        }

        if ($transaction->status === Transaction::STATUS_COMPLETED) {
            return ['ok' => true, 'http_code' => 200, 'message' => 'Already completed'];
        }

        DB::transaction(function () use ($transaction, $networkRef, $msisdn) {
            $transaction->update([
                'status' => Transaction::STATUS_COMPLETED,
                'network_ref' => $networkRef,
                'payer_msisdn' => $msisdn,
                'payment_gateway_ref' => $networkRef ?? $transaction->payment_gateway_ref,
            ]);

            $transaction->user->update([
                'subscription_status' => $transaction->subscriptionStatusForType(),
            ]);
        });

        Log::info('Yo! IPN: subscription upgraded', [
            'user_id' => $transaction->user_id,
            'external_ref' => $transaction->external_reference,
            'network_ref' => $networkRef,
        ]);

        return [
            'ok' => true,
            'http_code' => 200,
            'message' => 'OK',
            'transaction' => $transaction->fresh(),
            'sms_narrative' => $this->buildIpnSmsNarrative($transaction),
        ];
    }

    /**
     * Process a failure notification — API section 6.4.
     */
    public function processFailureNotification(array $payload): array
    {
        $failedRef = $this->payloadValue($payload, ['failed_transaction_reference']);

        if (! $failedRef) {
            return ['ok' => false, 'http_code' => 400, 'message' => 'Missing failed_transaction_reference'];
        }

        $transaction = Transaction::where('external_reference', $failedRef)
            ->orWhere('yo_transaction_ref', $failedRef)
            ->first();

        if (! $transaction) {
            Log::warning('Yo! failure notification: transaction not found', ['ref' => $failedRef]);

            return ['ok' => false, 'http_code' => 404, 'message' => 'Transaction not found'];
        }

        if ($transaction->status !== Transaction::STATUS_PENDING) {
            return ['ok' => true, 'http_code' => 200, 'message' => 'Already processed'];
        }

        $transaction->update(['status' => Transaction::STATUS_FAILED]);

        Log::info('Yo! failure notification: transaction marked failed', [
            'external_ref' => $transaction->external_reference,
        ]);

        return ['ok' => true, 'http_code' => 200, 'message' => 'OK'];
    }

    /**
     * Poll Yo! for pending transactions and complete when succeeded.
     */
    public function syncPendingTransaction(Transaction $transaction): array
    {
        $response = $this->checkTransactionStatus(
            $transaction->yo_transaction_ref,
            $transaction->external_reference,
            self::DEPOSIT_TYPE_PULL
        );

        if ($this->isSuccessfulGatewayResponse($response)) {
            DB::transaction(function () use ($transaction) {
                $transaction->update(['status' => Transaction::STATUS_COMPLETED]);
                $transaction->user->update([
                    'subscription_status' => $transaction->subscriptionStatusForType(),
                ]);
            });

            return ['synced' => true, 'status' => 'completed', 'response' => $response];
        }

        if ($this->isFailedGatewayResponse($response)) {
            $transaction->update(['status' => Transaction::STATUS_FAILED]);

            return ['synced' => true, 'status' => 'failed', 'response' => $response];
        }

        return ['synced' => false, 'status' => 'pending', 'response' => $response];
    }

    protected function buildDepositXml(
        string $msisdn,
        float $amount,
        string $narrative,
        string $externalReference,
        ?string $instantNotificationUrl,
        ?string $failureNotificationUrl
    ): string {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<AutoCreate><Request>';
        $xml .= '<APIUsername>' . htmlspecialchars($this->username) . '</APIUsername>';
        $xml .= '<APIPassword>' . htmlspecialchars($this->password) . '</APIPassword>';
        $xml .= '<Method>acdepositfunds</Method>';
        $xml .= '<NonBlocking>TRUE</NonBlocking>';
        $xml .= '<Account>' . htmlspecialchars($this->normalizeMsisdn($msisdn)) . '</Account>';
        $xml .= '<Amount>' . htmlspecialchars($this->formatAmount($amount)) . '</Amount>';
        $xml .= '<Narrative>' . htmlspecialchars($narrative) . '</Narrative>';
        $xml .= '<ExternalReference>' . htmlspecialchars($externalReference) . '</ExternalReference>';

        if ($instantNotificationUrl) {
            $xml .= '<InstantNotificationUrl>' . htmlspecialchars($instantNotificationUrl) . '</InstantNotificationUrl>';
        }

        if ($failureNotificationUrl) {
            $xml .= '<FailureNotificationUrl>' . htmlspecialchars($failureNotificationUrl) . '</FailureNotificationUrl>';
        }

        $xml .= '</Request></AutoCreate>';

        return $xml;
    }

    protected function sendXmlRequest(string $xml, string $logReference): array
    {
        $urls = [$this->apiUrl];

        if (! $this->sandbox) {
            $fallback = config('services.yo_payments.production_url_fallback');
            if ($fallback && $fallback !== $this->apiUrl) {
                $urls[] = $fallback;
            }
        }

        $lastError = null;

        foreach ($urls as $url) {
            try {
                Log::debug('Yo! Payments API request', [
                    'url' => $url,
                    'reference' => $logReference,
                    'sandbox' => $this->sandbox,
                ]);

                $response = $this->client->post($url, [
                    'headers' => [
                        'Content-Type' => 'text/xml',
                        'Content-transfer-encoding' => 'text',
                    ],
                    'body' => $xml,
                ]);

                $parsed = $this->parseResponse((string) $response->getBody());

                Log::info('Yo! Payments API response', [
                    'reference' => $logReference,
                    'url' => $url,
                    'status' => $parsed['Status'] ?? null,
                    'status_code' => $parsed['StatusCode'] ?? null,
                    'transaction_status' => $parsed['TransactionStatus'] ?? null,
                ]);

                return $parsed;
            } catch (GuzzleException $e) {
                $lastError = $e;
                Log::warning('Yo! Payments API attempt failed', [
                    'url' => $url,
                    'message' => $e->getMessage(),
                    'reference' => $logReference,
                ]);
            }
        }

        Log::error('Yo! Payments API transport error', [
            'message' => $lastError ? $lastError->getMessage() : 'Unknown error',
            'reference' => $logReference,
        ]);

        return [
            'Status' => 'ERROR',
            'StatusMessage' => $lastError ? $lastError->getMessage() : 'Could not reach Yo! Payments API.',
        ];
    }

    protected function parseResponse(string $xmlResponse): array
    {
        try {
            $simpleXml = new SimpleXMLElement($xmlResponse);
            $response = $simpleXml->Response;

            $result = [
                'Status' => (string) $response->Status,
                'StatusCode' => (string) $response->StatusCode,
                'StatusMessage' => (string) $response->StatusMessage,
                'TransactionStatus' => (string) ($response->TransactionStatus ?? ''),
            ];

            foreach (['TransactionReference', 'MNOTransactionReferenceId', 'ErrorMessage', 'ErrorMessageCode', 'IssuedReceiptNumber'] as $field) {
                if (! empty($response->{$field})) {
                    $result[$field] = (string) $response->{$field};
                }
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Yo! Payments XML parse error', ['response' => $xmlResponse]);

            return [
                'Status' => 'ERROR',
                'StatusMessage' => 'Invalid response from payment gateway.',
            ];
        }
    }

    protected function verifyRsaSha1Signature(string $data, string $signatureBase64): bool
    {
        $signature = base64_decode($signatureBase64, true);

        if ($signature === false) {
            return false;
        }

        $publicKey = openssl_pkey_get_public(file_get_contents($this->publicKeyPath));

        if ($publicKey === false) {
            return false;
        }

        $verified = openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA1);
        openssl_free_key($publicKey);

        return $verified === 1;
    }

    protected function isDuplicateIpn(?string $networkRef, ?string $msisdn, Transaction $current): bool
    {
        if (! $networkRef || ! $msisdn) {
            return false;
        }

        return Transaction::where('id', '!=', $current->id)
            ->where('network_ref', $networkRef)
            ->where('payer_msisdn', $msisdn)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->exists();
    }

    protected function amountsMatch($expected, $received): bool
    {
        return abs((float) $expected - (float) $received) < 0.01;
    }

    protected function buildIpnSmsNarrative(Transaction $transaction): string
    {
        $name = $transaction->user->name ?? 'Customer';

        return sprintf(
            'Dear %s, we received your payment of UGX %s (ref: %s). Your Forex Academy access is now active. Thank you!',
            $name,
            number_format((float) $transaction->amount, 0),
            $transaction->external_reference
        );
    }

    protected function payloadValue(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload) && $payload[$key] !== null && $payload[$key] !== '') {
                return (string) $payload[$key];
            }
        }

        return null;
    }

    protected function allowWithoutSignature(string $reason): bool
    {
        if (config('services.yo_payments.skip_signature_verification', false)) {
            Log::warning('Yo! Payments: skipping signature verification', ['reason' => $reason]);

            return true;
        }

        Log::error('Yo! Payments: signature verification failed', ['reason' => $reason]);

        return false;
    }

    protected function normalizeMsisdn(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (strpos($phone, '256') !== 0) {
            if (strpos($phone, '0') === 0) {
                $phone = '256' . substr($phone, 1);
            } else {
                $phone = '256' . $phone;
            }
        }

        return $phone;
    }

    protected function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
