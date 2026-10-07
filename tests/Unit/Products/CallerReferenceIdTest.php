<?php
declare(strict_types=1);

namespace Tests\Unit\Products;

use InvalidArgumentException;
use Lepresk\MomoApi\AirtelApi;
use Lepresk\MomoApi\Models\AirtelConfig;
use Lepresk\MomoApi\Models\PaymentRequest;
use Lepresk\MomoApi\Models\RefundRequest;
use Lepresk\MomoApi\Models\TransferRequest;
use Lepresk\MomoApi\MomoApi;
use Lepresk\MomoApi\Products\AirtelCollectionApi;
use Lepresk\MomoApi\Products\AirtelDisbursementApi;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tests\TestCase;

/**
 * A caller-supplied id lets an orchestrator query, rather than resend, a write
 * whose response it never saw.
 */
class CallerReferenceIdTest extends TestCase
{
    private const REFERENCE_ID = '3f1c9a2e-8b4d-4c6f-9a1e-2d7b5c8e0f13';

    private ?string $sentId = null;

    private function mtnClient(): MockHttpClient
    {
        $client = new MockHttpClient([
            new MockResponse(json_encode(['access_token' => 't', 'expires_in' => 3600, 'token_type' => 'Bearer'])),
            function ($method, $url, $options): MockResponse {
                $this->sentId = substr($options['normalized_headers']['x-reference-id'][0], strlen('X-Reference-Id: '));
                return new MockResponse('{}', ['http_code' => 202]);
            },
        ], $this->baseUrl());
        MomoApi::useClient($client);
        return $client;
    }

    private function airtelClient(): MockHttpClient
    {
        return new MockHttpClient([
            new MockResponse(json_encode(['access_token' => 't', 'expires_in' => 3600])),
            function ($method, $url, $options): MockResponse {
                $this->sentId = json_decode($options['body'], true)['transaction']['id'];
                return new MockResponse(json_encode(['status' => ['success' => true]]));
            },
        ], AirtelApi::STAGING_URL);
    }

    private static function mtnConfig(): array
    {
        return ['environment' => 'sandbox', 'subscription_key' => 'k', 'api_user' => 'u', 'api_key' => 's'];
    }

    public static function mtnWrites(): array
    {
        return [
            'requestToPay' => [fn(?string $id) => MomoApi::collection(self::mtnConfig())
                ->requestToPay(PaymentRequest::make('100', '242068511358', 'order-1'), $id)],
            'quickPay' => [fn(?string $id) => MomoApi::collection(self::mtnConfig())
                ->quickPay('100', '242068511358', 'order-1', 'EUR', $id)],
            'deposit' => [fn(?string $id) => MomoApi::disbursement(self::mtnConfig())
                ->deposit(PaymentRequest::make('100', '242068511358', 'dep-1'), $id)],
            'transfer' => [fn(?string $id) => MomoApi::disbursement(self::mtnConfig())
                ->transfer(TransferRequest::make('100', '242068511358', 'xfer-1'), $id)],
            'refund' => [fn(?string $id) => MomoApi::disbursement(self::mtnConfig())
                ->refund(RefundRequest::make('100', 'original-ref', 'refund-1'), $id)],
        ];
    }

    public static function airtelWrites(): array
    {
        return [
            'requestToPay' => [fn(MockHttpClient $c, ?string $id) => (new AirtelCollectionApi(
                $c,
                AirtelConfig::collection('cid', 'secret')
            ))->requestToPay('5000', '068511358', 'ORDER-001', $id)],
            'transfer' => [fn(MockHttpClient $c, ?string $id) => (new AirtelDisbursementApi(
                $c,
                AirtelConfig::disbursement('cid', 'secret', 'encrypted-pin')
            ))->transfer('10000', '068511358', 'PAY-001', $id)],
        ];
    }

    /** @dataProvider mtnWrites */
    public function testMtnSendsAndReturnsTheGivenReferenceId(\Closure $write): void
    {
        $this->mtnClient();

        $this->assertSame(self::REFERENCE_ID, $write(self::REFERENCE_ID));
        $this->assertSame(self::REFERENCE_ID, $this->sentId);
    }

    /** @dataProvider mtnWrites */
    public function testMtnGeneratesAFreshUuidWithoutOne(\Closure $write): void
    {
        $this->mtnClient();

        $referenceId = $write(null);

        $this->assertValidGuidV4($referenceId);
        $this->assertSame($referenceId, $this->sentId);
    }

    /** @dataProvider mtnWrites */
    public function testMtnRejectsANonUuidReferenceIdSendingNothing(\Closure $write): void
    {
        $client = $this->mtnClient();

        try {
            $write('order-1');
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('referenceId must be a UUID', $e->getMessage());
        }
        $this->assertSame(0, $client->getRequestsCount());
    }

    public function testMtnAcceptsADeterministicV5Uuid(): void
    {
        $this->mtnClient();
        $v5 = '886313e1-3b8a-5372-9b90-0c9aee199e5d';

        $this->assertSame($v5, MomoApi::collection(self::mtnConfig())->quickPay('100', '242068511358', 'o', 'EUR', $v5));
        $this->assertSame($v5, $this->sentId);
    }

    /** @dataProvider airtelWrites */
    public function testAirtelSendsAndReturnsTheGivenTransactionId(\Closure $write): void
    {
        $this->assertSame(self::REFERENCE_ID, $write($this->airtelClient(), self::REFERENCE_ID));
        $this->assertSame(self::REFERENCE_ID, $this->sentId);
    }

    /** @dataProvider airtelWrites */
    public function testAirtelGeneratesAFreshUuidWithoutOne(\Closure $write): void
    {
        $externalId = $write($this->airtelClient(), null);

        $this->assertValidGuidV4($externalId);
        $this->assertSame($externalId, $this->sentId);
    }

    /** @dataProvider airtelWrites */
    public function testAirtelRejectsANonUuidTransactionIdSendingNothing(\Closure $write): void
    {
        $client = $this->airtelClient();

        try {
            $write($client, 'BATCH42-0007');
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('transactionId must be a UUID', $e->getMessage());
        }
        $this->assertSame(0, $client->getRequestsCount());
    }
}
