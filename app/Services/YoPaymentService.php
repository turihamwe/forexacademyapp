<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class YoPaymentService
{
    protected Client $client;
    protected string $username;
    protected string $password;
    protected string $apiUrl;
    protected string $publicKeyPath;
    protected bool $sandbox;

    public function __construct()
    {
        $this->sandbox = (bool) config('services.yo_payments.sandbox', true);
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
            'verify' => false,
        ]);
    }

    /**
     * Request mobile money deposit (ac_deposit_funds equivalent).
     */
    public function depositFunds(
        string $msisdn,
        float $amount,
        string $narrative,
        string $externalReference,
        ?string $instantNotificationUrl = null
    ): array {
        $xml = $this->buildDepositXml(
            $msisdn,
            $amount,
            $narrative,
            $externalReference,
            $instantNotificationUrl
        );

        try {
            $response = $this->client->post($this->apiUrl, [
                'headers' => [
                    'Content-Type' => 'text/xml',
                    'Content-transfer-encoding' => 'text',
                ],
                'body' => $xml,
            ]);

            return $this->parseResponse((string) $response->getBody());
        } catch (GuzzleException $e) {
            Log::error('Yo! Payments API error', [
                'message' => $e->getMessage(),
                'external_reference' => $externalReference,
            ]);

            return [
                'Status' => 'ERROR',
                'StatusMessage' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify Instant Payment Notification signature from Yo! Payments.
     */
    public function verifyIpnSignature(array $payload): bool
    {
        if (empty($payload['signature']) || ! file_exists($this->publicKeyPath)) {
            return config('services.yo_payments.skip_signature_verification', false);
        }

        $data = ($payload['date_time'] ?? '')
            . ($payload['amount'] ?? '')
            . ($payload['narrative'] ?? '')
            . ($payload['network_ref'] ?? '')
            . ($payload['external_ref'] ?? '')
            . ($payload['msisdn'] ?? '');

        $signature = base64_decode($payload['signature']);
        $publicKey = openssl_pkey_get_public(file_get_contents($this->publicKeyPath));

        if ($publicKey === false) {
            return false;
        }

        $verified = openssl_verify($data, $signature, $publicKey);
        openssl_free_key($publicKey);

        return $verified === 1;
    }

    protected function buildDepositXml(
        string $msisdn,
        float $amount,
        string $narrative,
        string $externalReference,
        ?string $instantNotificationUrl
    ): string {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<AutoCreate>';
        $xml .= '<Request>';
        $xml .= '<APIUsername>' . htmlspecialchars($this->username) . '</APIUsername>';
        $xml .= '<APIPassword>' . htmlspecialchars($this->password) . '</APIPassword>';
        $xml .= '<Method>acdepositfunds</Method>';
        $xml .= '<NonBlocking>TRUE</NonBlocking>';
        $xml .= '<Account>' . htmlspecialchars($this->normalizeMsisdn($msisdn)) . '</Account>';
        $xml .= '<Amount>' . htmlspecialchars((string) $amount) . '</Amount>';
        $xml .= '<Narrative>' . htmlspecialchars($narrative) . '</Narrative>';
        $xml .= '<ExternalReference>' . htmlspecialchars($externalReference) . '</ExternalReference>';

        if ($instantNotificationUrl) {
            $xml .= '<InstantNotificationUrl>' . htmlspecialchars($instantNotificationUrl) . '</InstantNotificationUrl>';
        }

        $xml .= '</Request>';
        $xml .= '</AutoCreate>';

        return $xml;
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

            if (! empty($response->TransactionReference)) {
                $result['TransactionReference'] = (string) $response->TransactionReference;
            }

            if (! empty($response->ErrorMessage)) {
                $result['ErrorMessage'] = (string) $response->ErrorMessage;
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
}
