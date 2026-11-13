<?php

namespace Okay\Modules\Sviat\Monobank\Api;

use Okay\Core\Config;
use Psr\Log\LoggerInterface;

/**
 * Thin HTTP client for the Monobank acquiring API. All requests funnel through
 * a single request() method to avoid duplicating cURL/auth/error handling.
 */
class MonobankClient
{
    private const string BASE_URL = 'https://api.monobank.ua';
    private const int CONNECT_TIMEOUT = 10;
    private const int TIMEOUT = 30;

    private LoggerInterface $logger;
    private string $cmsVersion;

    public function __construct(LoggerInterface $logger, Config $config)
    {
        $this->logger     = $logger;
        $this->cmsVersion = (string) $config->version;
    }

    public function createInvoice(string $token, array $payload): ?array
    {
        return $this->send('POST', '/api/merchant/invoice/create', $token, $payload);
    }

    public function getFiscalCheck(string $token, string $invoiceId): ?array
    {
        $response = $this->send('GET', '/api/merchant/invoice/fiscal-checks?invoiceId=' . urlencode($invoiceId), $token);
        if (!empty($response['checks'][0])) {
            return $response['checks'][0];
        }
        return null;
    }

    public function getPublicKey(string $token): ?string
    {
        $response = $this->send('GET', '/api/merchant/pubkey', $token);
        if (!empty($response['key'])) {
            return base64_decode($response['key']) ?: null;
        }
        return null;
    }

    /**
     * @param array<string,mixed>|null $body
     * @return array<string,mixed>|null Decoded JSON on 2xx, null on error (logged).
     */
    private function send(string $method, string $path, string $token, ?array $body = null): ?array
    {
        $ch = curl_init();
        $options = [
            CURLOPT_URL            => self::BASE_URL . $path,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-Token: ' . $token,
                'X-Cms: Okay-CMS',
                'X-Cms-Version: ' . $this->cmsVersion,
            ],
        ];
        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
        }
        curl_setopt_array($ch, $options);

        $rawResponse = curl_exec($ch);
        $curlError   = curl_error($ch);
        $httpCode    = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        $decoded = is_string($rawResponse) ? json_decode($rawResponse, true) : null;

        if ($httpCode < 200 || $httpCode >= 300 || !is_array($decoded)) {
            $this->logError($method, $path, $httpCode, $curlError, $rawResponse, $decoded);
            return null;
        }

        return $decoded;
    }

    private function logError(string $method, string $path, int $httpCode, string $curlError, $rawResponse, $decoded): void
    {
        $message = 'Unknown Monobank error';
        if (is_array($decoded) && isset($decoded['errText'])) {
            $message = (string) $decoded['errText'];
        } elseif (is_string($rawResponse) && $rawResponse !== '') {
            $message = $rawResponse;
        } elseif ($curlError !== '') {
            $message = $curlError;
        }

        $this->logger->warning('Monobank API error: ' . $message, [
            'method'     => $method,
            'path'       => $path,
            'http_code'  => $httpCode,
            'curl_error' => $curlError,
        ]);
    }
}
