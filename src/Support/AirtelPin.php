<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Support;

use InvalidArgumentException;
use RuntimeException;

class AirtelPin
{
    private const PEM_HEADER = '-----BEGIN PUBLIC KEY-----';
    private const PEM_FOOTER = '-----END PUBLIC KEY-----';
    private const BASE64_TEXT = '/^[A-Za-z0-9+\/=\s]+$/';

    /**
     * Encrypt a disbursement PIN with Airtel's RSA public key.
     *
     * Airtel's transfer endpoint takes an encrypted PIN, never the PIN itself,
     * and mandates PKCS#1 v1.5 padding.
     *
     * ### Sample usage
     *
     * ```php
     * $config = AirtelConfig::disbursement(
     *     clientId: $clientId,
     *     clientSecret: $clientSecret,
     *     encryptedPin: AirtelPin::encrypt('1234', getenv('AIRTEL_PUBLIC_KEY')),
     * );
     * ```
     *
     * @param string $pin the plain PIN
     * @param string $publicKey Airtel's public key: PEM, base64-encoded PEM, or the bare base64 body
     * @return string the encrypted PIN, base64-encoded
     * @throws InvalidArgumentException when no key is given
     * @throws RuntimeException when the key is unusable or encryption fails
     */
    public static function encrypt(string $pin, string $publicKey): string
    {
        if (trim($publicKey) === '') {
            throw new InvalidArgumentException('Airtel public key is required to encrypt the PIN');
        }

        $encrypted = '';
        $ok = @openssl_public_encrypt(
            $pin,
            $encrypted,
            self::toPem($publicKey),
            OPENSSL_PKCS1_PADDING
        );

        if ($ok === false) {
            throw new RuntimeException(
                'PIN encryption failed: ' . (openssl_error_string() ?: 'unusable public key')
            );
        }

        return base64_encode($encrypted);
    }

    /**
     * Airtel hands out the public key in several shapes: a PEM, a base64-encoded
     * PEM, the bare base64 body, or that body base64-encoded again. Accept all four.
     */
    private static function toPem(string $key): string
    {
        $trimmed = trim($key);
        if (str_contains($trimmed, self::PEM_HEADER)) {
            return $trimmed;
        }

        $decoded = trim((string) base64_decode($trimmed, true));
        if (str_contains($decoded, self::PEM_HEADER)) {
            return $decoded;
        }

        // Either the base64 DER body itself, which decodes to binary, or that body
        // base64-encoded once more, which decodes to base64 text
        $source = preg_match(self::BASE64_TEXT, $decoded) === 1 ? $decoded : $trimmed;
        $body = chunk_split(preg_replace('/\s/', '', $source) ?? $source, 64, "\n");

        return self::PEM_HEADER . "\n" . $body . self::PEM_FOOTER;
    }
}
