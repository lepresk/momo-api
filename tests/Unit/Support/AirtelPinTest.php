<?php
declare(strict_types=1);

namespace Tests\Unit\Support;

use InvalidArgumentException;
use Lepresk\MomoApi\Support\AirtelPin;
use RuntimeException;
use Tests\TestCase;

class AirtelPinTest extends TestCase
{
    private const MODULUS_BITS = 2048;

    private string $publicKeyPem;
    private string $privateKeyPem;
    private string $base64Pem;

    protected function setUp(): void
    {
        $key = openssl_pkey_new([
            'private_key_bits' => self::MODULUS_BITS,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $privateKeyPem = '';
        openssl_pkey_export($key, $privateKeyPem);

        $this->privateKeyPem = $privateKeyPem;
        $this->publicKeyPem = openssl_pkey_get_details($key)['key'];
        $this->base64Pem = base64_encode($this->publicKeyPem);
    }

    private function decrypt(string $encrypted): string
    {
        $decrypted = '';
        openssl_private_decrypt(
            base64_decode($encrypted),
            $decrypted,
            $this->privateKeyPem,
            OPENSSL_PKCS1_PADDING
        );

        return (string) $decrypted;
    }

    public function testRoundTripsThroughTheMatchingPrivateKey(): void
    {
        $this->assertSame('1234', $this->decrypt(AirtelPin::encrypt('1234', $this->base64Pem)));
    }

    public function testProducesACiphertextOfExactlyOneRsaBlock(): void
    {
        $encrypted = AirtelPin::encrypt('1234', $this->base64Pem);

        $this->assertSame(self::MODULUS_BITS / 8, strlen(base64_decode($encrypted)));
    }

    public function testProducesADifferentCiphertextEachTimeAsPaddingIsRandom(): void
    {
        $first = AirtelPin::encrypt('1234', $this->base64Pem);
        $second = AirtelPin::encrypt('1234', $this->base64Pem);

        $this->assertNotSame($first, $second);
        $this->assertSame($this->decrypt($first), $this->decrypt($second));
    }

    public function testAcceptsAKeyThatIsAlreadyPem(): void
    {
        $this->assertSame('4321', $this->decrypt(AirtelPin::encrypt('4321', $this->publicKeyPem)));
    }

    public function testAcceptsABase64BodyWithNoPemHeader(): void
    {
        $bare = str_replace(
            ['-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----', "\n", "\r"],
            '',
            $this->publicKeyPem
        );

        $this->assertSame('4321', $this->decrypt(AirtelPin::encrypt('4321', base64_encode($bare))));
    }

    public function testRejectsAnEmptyKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('public key is required');

        AirtelPin::encrypt('1234', '');
    }

    public function testReportsWhatFailedWhenTheKeyIsUnusable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PIN encryption failed');

        AirtelPin::encrypt('1234', base64_encode('not-a-key'));
    }
}
