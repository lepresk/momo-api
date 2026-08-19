<?php
declare(strict_types=1);

namespace Tests\Unit\Products;

use Lepresk\MomoApi\AirtelApi;
use Lepresk\MomoApi\Exceptions\MomoException;
use Lepresk\MomoApi\Models\AirtelConfig;
use Lepresk\MomoApi\Products\AirtelCollectionApi;
use Lepresk\MomoApi\Products\AirtelDisbursementApi;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tests\TestCase;

/**
 * Airtel reports business failures — insufficient funds, invalid PIN, unknown
 * transaction — with HTTP 200 and `status.success: false` in the body.
 */
class AirtelEnvelopeTest extends TestCase
{
    private function tokenResponse(): MockResponse
    {
        return new MockResponse(
            json_encode(['access_token' => 'test-token', 'expires_in' => 3600]),
            ['http_code' => 200]
        );
    }

    private function collection(array $responses): AirtelCollectionApi
    {
        return new AirtelCollectionApi(
            new MockHttpClient($responses, AirtelApi::STAGING_URL),
            AirtelConfig::collection('clientId', 'clientSecret')
        );
    }

    private function disbursement(array $responses): AirtelDisbursementApi
    {
        return new AirtelDisbursementApi(
            new MockHttpClient($responses, AirtelApi::STAGING_URL),
            AirtelConfig::disbursement('clientId', 'clientSecret', 'encrypted-pin')
        );
    }

    public function testRequestToPayRaisesWhenTheEnvelopeReportsFailure(): void
    {
        $collection = $this->collection([
            $this->tokenResponse(),
            new MockResponse(json_encode([
                'data' => [],
                'status' => [
                    'response_code' => 'DP00800001006',
                    'code' => '500',
                    'success' => false,
                    'message' => 'Transaction is not permitted to the payee',
                    'result_code' => 'ESB000011',
                ],
            ]), ['http_code' => 200]),
        ]);

        $this->expectException(MomoException::class);
        $this->expectExceptionMessage('Transaction is not permitted to the payee');

        $collection->requestToPay('1000', '068511358', 'ORDER-1');
    }

    public function testTransferRaisesWhenTheEnvelopeReportsFailure(): void
    {
        $disbursement = $this->disbursement([
            $this->tokenResponse(),
            new MockResponse(json_encode([
                'status' => [
                    'success' => false,
                    'message' => 'Invalid PIN',
                    'result_code' => 'ESB000008',
                ],
            ]), ['http_code' => 200]),
        ]);

        $this->expectException(MomoException::class);
        $this->expectExceptionMessage('Invalid PIN');

        $disbursement->transfer('1000', '068511358', 'PAY-1');
    }

    public function testAcceptsARequestWhoseEnvelopeReportsSuccess(): void
    {
        $collection = $this->collection([
            $this->tokenResponse(),
            new MockResponse(
                json_encode(['data' => [], 'status' => ['success' => true, 'message' => 'Success']]),
                ['http_code' => 200]
            ),
        ]);

        $this->assertNotEmpty($collection->requestToPay('1000', '068511358', 'ORDER-1'));
    }

    public function testAcceptsARequestWithNoEnvelopeAtAll(): void
    {
        $collection = $this->collection([
            $this->tokenResponse(),
            new MockResponse(json_encode(['data' => []]), ['http_code' => 200]),
        ]);

        $this->assertNotEmpty($collection->requestToPay('1000', '068511358', 'ORDER-1'));
    }

    public function testStatusLookupRaisesWhenTheEnvelopeReportsFailure(): void
    {
        $collection = $this->collection([
            $this->tokenResponse(),
            new MockResponse(json_encode([
                'data' => [],
                'status' => [
                    'success' => false,
                    'message' => 'Transaction not found',
                    'result_code' => 'ESB000004',
                ],
            ]), ['http_code' => 200]),
        ]);

        $this->expectException(MomoException::class);
        $this->expectExceptionMessage('Transaction not found');

        $collection->getPaymentStatus('ext-1');
    }
}
