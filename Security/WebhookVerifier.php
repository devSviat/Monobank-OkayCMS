<?php

namespace Okay\Modules\Sviat\Monobank\Security;

use Okay\Core\Settings;
use Okay\Modules\Sviat\Monobank\Api\MonobankClient;

/**
 * Verifies Monobank webhook payloads using the merchant's ECDSA public key.
 *
 * Monobank asks to cache the key and fetch a new one only when a signature stops
 * verifying with the stored one, so the key lives in settings, per token.
 */
class WebhookVerifier
{
    private const string KEYS_SETTING = 'sviat__monobank__webhook_keys';

    public function __construct(private MonobankClient $client, private Settings $settings)
    {
    }

    public function verify(string $rawBody, string $base64Sign, string $token): bool
    {
        $tokenId = hash('sha256', $token);
        $keys    = $this->settings->get(self::KEYS_SETTING);
        $keys    = is_array($keys) ? $keys : [];

        $stored = is_string($keys[$tokenId] ?? null) ? $keys[$tokenId] : null;
        if ($stored !== null && $this->verifyWithKey($rawBody, $base64Sign, $stored)) {
            return true;
        }

        $fresh = $this->client->getPublicKey($token);
        if (empty($fresh)) {
            return false;
        }
        if ($fresh !== $stored) {
            $keys[$tokenId] = $fresh;
            $this->settings->set(self::KEYS_SETTING, $keys);
        }

        return $this->verifyWithKey($rawBody, $base64Sign, $fresh);
    }

    public function verifyWithKey(string $rawBody, string $base64Sign, string $publicKeyPem): bool
    {
        $signature = base64_decode($base64Sign, true);
        if ($signature === false || $signature === '') {
            return false;
        }

        return openssl_verify($rawBody, $signature, $publicKeyPem, OPENSSL_ALGO_SHA256) === 1;
    }
}
