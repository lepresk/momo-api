<?php
declare(strict_types=1);

namespace Tests\Unit\Products;

use Lepresk\MomoApi\Models\RefundRequest;
use Lepresk\MomoApi\Models\TransferRequest;
use Lepresk\MomoApi\MomoApi;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tests\TestCase;

class DisbursementApiTest extends TestCase
{
    private function provideTokenResponse(): \Closure
    {
        return function ($method, $url): MockResponse {
            $this->assertSame($this->baseUrl() . '/disbursement/token/', $url);
            return new MockResponse(json_encode([
                'access_token' => 'testToken',
                'expires_in' => 3600,
                'token_type' => 'Bearer'
            ]), ['http_code' => 200]);
        };
    }

    public function testTransfer()
    {
        $expectedRequests = [
            $this->provideTokenResponse(),
            function ($method, $url, $options): MockResponse {
                $this->assertSame('POST', $method);
                $this->assertSame($this->baseUrl() . '/disbursement/v1_0/transfer', $url);
                $body = json_decode($options['body'], true);
                $this->assertArrayHasKey('payee', $body);
                $this->assertEquals('242068511358', $body['payee']['partyId']);
                return new MockResponse('{}', ['http_code' => 202]);
            },
        ];

        MomoApi::useClient($this->provideClient($expectedRequests));
        $disbursement = MomoApi::disbursement([
            'environment' => 'sandbox',
            'subscription_key' => 'testSubKey',
            'api_user' => 'apiUser',
            'api_key' => 'apiKey',
            'callback_url' => 'https://example.com/callback'
        ]);

        $request = TransferRequest::make('1000', '242068511358', 'TRANSFER-001');
        $transferId = $disbursement->transfer($request);

        $this->assertValidGuidV4($transferId);
    }

    public function testGetTransferStatus()
    {
        $transferId = '07a461a4-e721-462b-81c6-b9aa2f8abf06';
        $data = [
            "financialTransactionId" => "123456789",
            "externalId" => "TRANSFER-001",
            "amount" => "1000",
            "currency" => "XAF",
            "payee" => [
                "partyIdType" => "MSISDN",
                "partyId" => "242068511358"
            ],
            "payerMessage" => "Transfer message",
            "payeeNote" => "Transfer note",
            "status" => "SUCCESSFUL"
        ];

        $expectedRequests = [
            $this->provideTokenResponse(),
            function ($method, $url) use ($transferId, $data): MockResponse {
                $this->assertSame('GET', $method);
                $this->assertSame($this->baseUrl() . "/disbursement/v1_0/transfer/$transferId", $url);
                return new MockResponse(json_encode($data), ['http_code' => 200]);
            },
        ];

        MomoApi::useClient($this->provideClient($expectedRequests));
        $disbursement = MomoApi::disbursement([
            'environment' => 'sandbox',
            'subscription_key' => 'testSubKey',
            'api_user' => 'apiUser',
            'api_key' => 'apiKey',
            'callback_url' => ''
        ]);

        $transaction = $disbursement->getTransferStatus($transferId);

        $this->assertEquals($data['status'], $transaction->getStatus());
        $this->assertEquals($data['payee']['partyId'], $transaction->getPayee());
        $this->assertTrue($transaction->isSuccessful());
    }

    public function testRefund()
    {
        $expectedRequests = [
            $this->provideTokenResponse(),
            function ($method, $url, $options): MockResponse {
                $this->assertSame('POST', $method);
                $this->assertSame($this->baseUrl() . '/disbursement/v1_0/refund', $url);
                $body = json_decode($options['body'], true);
                $this->assertArrayHasKey('referenceIdToRefund', $body);
                $this->assertEquals('07a461a4-e721-462b-81c6-b9aa2f8abf06', $body['referenceIdToRefund']);
                return new MockResponse('{}', ['http_code' => 202]);
            },
        ];

        MomoApi::useClient($this->provideClient($expectedRequests));
        $disbursement = MomoApi::disbursement([
            'environment' => 'sandbox',
            'subscription_key' => 'testSubKey',
            'api_user' => 'apiUser',
            'api_key' => 'apiKey',
        ]);

        $request = RefundRequest::make('500', '07a461a4-e721-462b-81c6-b9aa2f8abf06', 'REFUND-001');
        $refundId = $disbursement->refund($request);

        $this->assertValidGuidV4($refundId);
    }

    public function testGetRefundStatus()
    {
        $refundId = '07a461a4-e721-462b-81c6-b9aa2f8abf06';
        $data = [
            "financialTransactionId" => "987654321",
            "externalId" => "REFUND-001",
            "amount" => "500",
            "currency" => "XAF",
            "payer" => [
                "partyIdType" => "MSISDN",
                "partyId" => "242068511358"
            ],
            "payerMessage" => "",
            "payeeNote" => "",
            "status" => "SUCCESSFUL"
        ];

        $expectedRequests = [
            $this->provideTokenResponse(),
            function ($method, $url) use ($refundId, $data): MockResponse {
                $this->assertSame('GET', $method);
                $this->assertSame($this->baseUrl() . "/disbursement/v1_0/refund/$refundId", $url);
                return new MockResponse(json_encode($data), ['http_code' => 200]);
            },
        ];

        MomoApi::useClient($this->provideClient($expectedRequests));
        $disbursement = MomoApi::disbursement([
            'environment' => 'sandbox',
            'subscription_key' => 'testSubKey',
            'api_user' => 'apiUser',
            'api_key' => 'apiKey',
        ]);

        $transaction = $disbursement->getRefundStatus($refundId);

        $this->assertEquals($data['status'], $transaction->getStatus());
        $this->assertTrue($transaction->isSuccessful());
    }

    public function testCallbackUrlHeader()
    {
        $callbackUrl = 'https://example.com/webhook';

        $expectedRequests = [
            $this->provideTokenResponse(),
            function ($method, $url, $options) use ($callbackUrl): MockResponse {
                $this->assertContains("X-Callback-Url: $callbackUrl", $options['headers']);
                return new MockResponse('{}', ['http_code' => 202]);
            },
        ];

        MomoApi::useClient($this->provideClient($expectedRequests));
        $disbursement = MomoApi::disbursement([
            'environment' => 'sandbox',
            'subscription_key' => 'testSubKey',
            'api_user' => 'apiUser',
            'api_key' => 'apiKey',
            'callback_url' => $callbackUrl
        ]);

        $request = TransferRequest::make('1000', '242068511358', 'TRANSFER-001');
        $disbursement->transfer($request);
    }
}
