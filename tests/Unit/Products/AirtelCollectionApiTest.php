<?php
declare(strict_types=1);

namespace Tests\Unit\Products;

use Lepresk\MomoApi\AirtelApi;
use Lepresk\MomoApi\Exceptions\MomoException;
use Lepresk\MomoApi\Models\AirtelConfig;
use Lepresk\MomoApi\Models\AirtelTransaction;
use Lepresk\MomoApi\Products\AirtelCollectionApi;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tests\TestCase;

class AirtelCollectionApiTest extends TestCase
{
    private AirtelConfig $config;

    protected function setUp(): void
    {
        $this->config = AirtelConfig::collection('clientId', 'clientSecret');
    }

    private function makeCollection(array $responses): AirtelCollectionApi
    {
        $client = new MockHttpClient($responses, AirtelApi::STAGING_URL);
        return new AirtelCollectionApi($client, $this->config);
    }

    private function tokenResponse(): MockResponse
    {
        return new MockResponse(json_encode([
            'access_token' => 'test-token',
            'expires_in' => 3600,
        ]), ['http_code' => 200]);
    }

    public function testGetAccessToken(): void
    {
        $collection = $this->makeCollection([
            function ($method, $url): MockResponse {
                $this->assertSame('POST', $method);
                $this->assertStringContainsString('/auth/oauth2/token', $url);
                return $this->tokenResponse();
            },
        ]);

        $token = $collection->getAccessToken();
        $this->assertSame('test-token', $token);
    }

    public function testTokenIsCached(): void
    {
        $callCount = 0;
        $collection = $this->makeCollection([
            function ($method, $url) use (&$callCount): MockResponse {
                $callCount++;
                return $this->tokenResponse();
            },
            new MockResponse('{}', ['http_code' => 200]),
            new MockResponse('{}', ['http_code' => 200]),
        ]);

        $collection->getAccessToken();
        $collection->getAccessToken();

        $this->assertSame(1, $callCount, 'Token endpoint should only be called once');
    }

    public function testRequestToPay(): void
    {
        $collection = $this->makeCollection([
            $this->tokenResponse(),
            function ($method, $url, $options): MockResponse {
                $this->assertSame('POST', $method);
                $this->assertStringContainsString('/merchant/v1/payments/', $url);
                $body = json_decode($options['body'], true);
                $this->assertSame('068511358', $body['subscriber']['msisdn']);
                $this->assertSame('ORDER-001', $body['reference']);
                $this->assertIsFloat($body['transaction']['amount']);
                return new MockResponse('{}', ['http_code' => 200]);
            },
        ]);

        $externalId = $collection->requestToPay('5000', '068511358', 'ORDER-001');

        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $this->assertMatchesRegularExpression($pattern, $externalId);
    }

    public function testRequestToPayThrowsOn400(): void
    {
        $collection = $this->makeCollection([
            $this->tokenResponse(),
            new MockResponse('{}', ['http_code' => 400]),
        ]);

        $this->expectException(MomoException::class);
        $collection->requestToPay('5000', '068511358', 'ORDER-001');
    }

    public function testGetPaymentStatusPending(): void
    {
        $externalId = 'abc-123';
        $collection = $this->makeCollection([
            $this->tokenResponse(),
            function ($method, $url): MockResponse {
                $this->assertSame('GET', $method);
                $this->assertStringContainsString('/standard/v1/payments/', $url);
                return new MockResponse(json_encode([
                    'data' => [
                        'transaction' => [
                            'id' => 'abc-123',
                            'status' => 'TIP',
                        ],
                    ],
                ]), ['http_code' => 200]);
            },
        ]);

        $transaction = $collection->getPaymentStatus($externalId);

        $this->assertInstanceOf(AirtelTransaction::class, $transaction);
        $this->assertTrue($transaction->isPending());
        $this->assertFalse($transaction->isSuccessful());
    }

    public function testGetPaymentStatusSuccessful(): void
    {
        $collection = $this->makeCollection([
            $this->tokenResponse(),
            new MockResponse(json_encode([
                'data' => [
                    'transaction' => [
                        'id' => 'abc-123',
                        'status' => 'TS',
                        'airtel_money_id' => 'AM123456',
                    ],
                ],
            ]), ['http_code' => 200]),
        ]);

        $transaction = $collection->getPaymentStatus('abc-123');

        $this->assertTrue($transaction->isSuccessful());
        $this->assertSame('AM123456', $transaction->getAirtelMoneyId());
    }

    public function testGetPaymentStatusThrowsOn400(): void
    {
        $collection = $this->makeCollection([
            $this->tokenResponse(),
            new MockResponse('{}', ['http_code' => 404]),
        ]);

        $this->expectException(MomoException::class);
        $collection->getPaymentStatus('nonexistent');
    }

    public function testGetBalance(): void
    {
        $collection = $this->makeCollection([
            $this->tokenResponse(),
            new MockResponse(json_encode([
                'data' => [
                    'balance' => '50000',
                    'currency' => 'XAF',
                ],
            ]), ['http_code' => 200]),
        ]);

        $balance = $collection->getBalance();

        $this->assertSame('50000', $balance->getAvailableBalance());
        $this->assertSame('XAF', $balance->getCurrency());
    }
}
