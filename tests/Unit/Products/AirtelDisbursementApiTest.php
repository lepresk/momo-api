<?php
declare(strict_types=1);

namespace Tests\Unit\Products;

use Lepresk\MomoApi\AirtelApi;
use Lepresk\MomoApi\Exceptions\MomoException;
use Lepresk\MomoApi\Models\AirtelConfig;
use Lepresk\MomoApi\Models\AirtelTransaction;
use Lepresk\MomoApi\Products\AirtelDisbursementApi;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tests\TestCase;

class AirtelDisbursementApiTest extends TestCase
{
    private AirtelConfig $config;

    protected function setUp(): void
    {
        $this->config = AirtelConfig::disbursement('clientId', 'clientSecret', 'encrypted-pin');
    }

    private function makeDisbursement(array $responses): AirtelDisbursementApi
    {
        $client = new MockHttpClient($responses, AirtelApi::STAGING_URL);
        return new AirtelDisbursementApi($client, $this->config);
    }

    private function tokenResponse(): MockResponse
    {
        return new MockResponse(json_encode([
            'access_token' => 'test-token',
            'expires_in' => 3600,
        ]), ['http_code' => 200]);
    }

    public function testTransfer(): void
    {
        $disbursement = $this->makeDisbursement([
            $this->tokenResponse(),
            function ($method, $url, $options): MockResponse {
                $this->assertSame('POST', $method);
                $this->assertStringContainsString('/standard/v1/disbursements/', $url);
                $body = json_decode($options['body'], true);
                $this->assertSame('068511358', $body['payee']['msisdn']);
                $this->assertSame('PAY-001', $body['reference']);
                $this->assertSame('encrypted-pin', $body['pin']);
                return new MockResponse('{}', ['http_code' => 200]);
            },
        ]);

        $externalId = $disbursement->transfer('10000', '068511358', 'PAY-001');

        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $this->assertMatchesRegularExpression($pattern, $externalId);
    }

    public function testTransferThrowsWhenNoPinConfigured(): void
    {
        $config = AirtelConfig::collection('clientId', 'clientSecret');
        $client = new MockHttpClient([], AirtelApi::STAGING_URL);
        $disbursement = new AirtelDisbursementApi($client, $config);

        $this->expectException(\InvalidArgumentException::class);
        $disbursement->transfer('10000', '068511358', 'PAY-001');
    }

    public function testTransferThrowsOn400(): void
    {
        $disbursement = $this->makeDisbursement([
            $this->tokenResponse(),
            new MockResponse('{}', ['http_code' => 400]),
        ]);

        $this->expectException(MomoException::class);
        $disbursement->transfer('10000', '068511358', 'PAY-001');
    }

    public function testGetTransferStatusPending(): void
    {
        $disbursement = $this->makeDisbursement([
            $this->tokenResponse(),
            new MockResponse(json_encode([
                'data' => [
                    'transaction' => [
                        'id' => 'abc-123',
                        'status' => 'TIP',
                    ],
                ],
            ]), ['http_code' => 200]),
        ]);

        $transaction = $disbursement->getTransferStatus('abc-123');

        $this->assertInstanceOf(AirtelTransaction::class, $transaction);
        $this->assertTrue($transaction->isPending());
    }

    public function testGetTransferStatusSuccessful(): void
    {
        $disbursement = $this->makeDisbursement([
            $this->tokenResponse(),
            new MockResponse(json_encode([
                'data' => [
                    'transaction' => [
                        'id' => 'abc-123',
                        'status' => 'TS',
                        'airtel_money_id' => 'AM789',
                    ],
                ],
            ]), ['http_code' => 200]),
        ]);

        $transaction = $disbursement->getTransferStatus('abc-123');

        $this->assertTrue($transaction->isSuccessful());
        $this->assertSame('AM789', $transaction->getAirtelMoneyId());
    }

    public function testGetTransferStatusThrowsWhenTransactionMissing(): void
    {
        $disbursement = $this->makeDisbursement([
            $this->tokenResponse(),
            new MockResponse(json_encode(['data' => []]), ['http_code' => 200]),
        ]);

        $this->expectException(\RuntimeException::class);
        $disbursement->getTransferStatus('abc-123');
    }

    public function testGetBalance(): void
    {
        $disbursement = $this->makeDisbursement([
            $this->tokenResponse(),
            new MockResponse(json_encode([
                'data' => [
                    'balance' => '100000',
                    'currency' => 'XAF',
                ],
            ]), ['http_code' => 200]),
        ]);

        $balance = $disbursement->getBalance();

        $this->assertSame('100000', $balance->getAvailableBalance());
        $this->assertSame('XAF', $balance->getCurrency());
    }
}
