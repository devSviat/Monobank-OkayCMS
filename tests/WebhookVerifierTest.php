<?php

namespace Modules\Sviat\Monobank;

use Okay\Core\Settings;
use Okay\Modules\Sviat\Monobank\Api\MonobankClient;
use Okay\Modules\Sviat\Monobank\Security\WebhookVerifier;
use PHPUnit\Framework\TestCase;

class WebhookVerifierTest extends TestCase
{
    private string $privateKeyPem = '';
    private string $publicKeyPem = '';

    protected function setUp(): void
    {
        $res = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name'       => 'prime256v1',
        ]);
        if ($res === false) {
            self::markTestSkipped('OpenSSL EC key generation unavailable');
        }
        openssl_pkey_export($res, $this->privateKeyPem);
        $this->publicKeyPem = openssl_pkey_get_details($res)['key'];
    }

    private function sign(string $body): string
    {
        openssl_sign($body, $signature, $this->privateKeyPem, OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }

    public function testVerifyWithKeyAcceptsValidSignature(): void
    {
        $verifier = new WebhookVerifier($this->createStubClient(), $this->settings());
        $body     = '{"invoiceId":"abc","status":"success"}';

        self::assertTrue($verifier->verifyWithKey($body, $this->sign($body), $this->publicKeyPem));
    }

    public function testVerifyWithKeyRejectsTamperedBody(): void
    {
        $verifier = new WebhookVerifier($this->createStubClient(), $this->settings());
        $sign     = $this->sign('{"invoiceId":"abc","status":"success"}');

        self::assertFalse($verifier->verifyWithKey('{"invoiceId":"abc","status":"failure"}', $sign, $this->publicKeyPem));
    }

    public function testVerifyWithKeyRejectsGarbageSignature(): void
    {
        $verifier = new WebhookVerifier($this->createStubClient(), $this->settings());

        self::assertFalse($verifier->verifyWithKey('body', base64_encode('not-a-signature'), $this->publicKeyPem));
    }

    public function testVerifyFetchesKeyFromClient(): void
    {
        $verifier = new WebhookVerifier($this->createStubClient($this->publicKeyPem), $this->settings());
        $body     = '{"status":"success"}';

        self::assertTrue($verifier->verify($body, $this->sign($body), 'token-123'));
    }

    public function testVerifyFailsWhenKeyUnavailable(): void
    {
        $verifier = new WebhookVerifier($this->createStubClient(null), $this->settings());

        self::assertFalse($verifier->verify('body', base64_encode('sig'), 'token-123'));
    }

    /** Ключ можна кешувати: Monobank просить не питати його на кожен вебхук. */
    public function testStoredKeyIsUsedWithoutAskingMonobank(): void
    {
        $client = $this->createStubClient($this->publicKeyPem);
        $settings = $this->settings();
        new WebhookVerifier($client, $settings)->verify('{"a":1}', $this->sign('{"a":1}'), 'token-123');
        $client->calls = 0;

        $verifier = new WebhookVerifier($client, $settings);
        self::assertTrue($verifier->verify('{"a":2}', $this->sign('{"a":2}'), 'token-123'));
        self::assertSame(0, $client->calls);
    }

    /** Коли підпис зі збереженим ключем не сходиться, ключ могли змінити: беремо новий один раз. */
    public function testRotatedKeyIsFetchedOnceAndStored(): void
    {
        $old = openssl_pkey_get_details(openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']))['key'];
        $settings = $this->settings();
        new WebhookVerifier($this->createStubClient($old), $settings)->verify('x', $this->sign('x'), 'token-123');

        $client = $this->createStubClient($this->publicKeyPem);
        self::assertTrue(new WebhookVerifier($client, $settings)->verify('{"b":1}', $this->sign('{"b":1}'), 'token-123'));
        self::assertSame(1, $client->calls);

        $client->calls = 0;
        self::assertTrue(new WebhookVerifier($client, $settings)->verify('{"b":2}', $this->sign('{"b":2}'), 'token-123'));
        self::assertSame(0, $client->calls, 'новий ключ збережено');
    }

    public function testKeyOfAnotherTokenIsNotReused(): void
    {
        $settings = $this->settings();
        new WebhookVerifier($this->createStubClient($this->publicKeyPem), $settings)->verify('x', $this->sign('x'), 'token-A');

        self::assertFalse(new WebhookVerifier($this->createStubClient(null), $settings)->verify('x', $this->sign('x'), 'token-B'));
    }

    private function settings(): Settings
    {
        return new class extends Settings {
            private array $values = [];
            public function __construct() {}
            public function get($param) { return $this->values[$param] ?? null; }
            public function set($param, $value) { $this->values[$param] = $value; }
        };
    }

    private function createStubClient(?string $key = null): MonobankClient
    {
        return new class($key) extends MonobankClient {
            public int $calls = 0;
            private ?string $key;
            public function __construct(?string $key) { $this->key = $key; }
            public function getPublicKey(string $token): ?string { $this->calls++; return $this->key; }
        };
    }
}
