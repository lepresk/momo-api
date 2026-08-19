<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Support;

use InvalidArgumentException;
use RuntimeException;

class AirtelPin
{
    private const PEM_HEADER = '-----BEGIN PUBLIC KEY-----';
    private const PEM_FOOTER = '-----END PUBLIC KEY-----';

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
     * @param string $publicKey Airtel's public key, base64-encoded or PEM
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
     * Airtel hands out the public key base64-encoded, sometimes with the PEM
     * armour and sometimes without. Accept either, plus a plain PEM.
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

        $body = chunk_split(preg_replace('/\s/', '', $decoded) ?? $decoded, 64, "\n");

        return self::PEM_HEADER . "\n" . $body . self::PEM_FOOTER;
    }
}
