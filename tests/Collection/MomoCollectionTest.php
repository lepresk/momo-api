<?php

namespace Tests\Collection;

use Lepresk\MomoApi\Collection\Config;
use Lepresk\MomoApi\Collection\PaymentRequest;
use Lepresk\MomoApi\Exception\MomoException;
use Lepresk\MomoApi\MomoApi;
use Lepresk\MomoApi\Utilities;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tests\TestCase;

class MomoCollectionTest extends TestCase
{
    public function testSubscriptionKeyPassed()
    {
        $this->expectException(MomoException::class);
        $subscriptionKey = 'testSubKey';

        $expectedRequests = [
            function ($method, $url, $options) use ($subscriptionKey): MockResponse {
                $this->assertContains("Ocp-Apim-Subscription-Key: $subscriptionKey", $options['headers']);
                return new MockResponse('{}', ['http_code' => 500]);
            },
        ];

        MomoApi::useClient($this->provideClient($expectedRequests));
        $momo = MomoApi::create($subscriptionKey);
        $momo->setupCollection(new Config("apiUser", "apiKey", "aCalllback"));
        $momo->collection()->getAccessToken();
    }

    public function testGetAccessToken()
    {
        $sampleToken = [
            'access_token' => 'testToken',
            'expires_in' => 3600,
            'token_type' => 'Bearer'
        ];

        $expectedRequests = [
            function ($method, $url, $options) use ($sampleToken): MockResponse {
                $this->assertSame('POST', $method);
                $this->assertSame(MomoApi::getBaseUrl() . '/collection/token/', $url);
                $this->assertArrayHasKey('authorization', $options['normalized_headers']);
                return new MockResponse(json_encode($sampleToken), ['http_code' => 200]);
            },
        ];

        MomoApi::useClient($this->provideClient($expectedRequests));
        $momo = MomoApi::create('testSubKey');
        $momo->setupCollection(new Config("apiUser", "apiKey", "aCalllback"));
        $token = $momo->collection()->getAccessToken();

        $this->assertEquals($sampleToken['access_token'], $token->getAccessToken());
    }

    public function testThrowIfCannotCreateAccessToken()
    {
        $expectedRequests = [
            function ($method, $url, $options): MockResponse {
                $this->assertSame('POST', $method);
                $this->assertSame(MomoApi::getBaseUrl() . '/collection/token/', $url);
                $this->assertArrayHasKey('authorization', $options['normalized_headers']);
                return new MockResponse('{}', ['http_code' => 401]);
            },
        ];

        $this->expectException(MomoException::class);
        MomoApi::useClient($this->provideClient($expectedRequests));
        $momo = MomoApi::create('testSubKey');
        $momo->setupCollection(new Config("apiUser", "apiKey", "aCalllback"));
        $momo->collection()->getAccessToken();
    }

    public function testRequestToPay()
    {
        $expectedRequests = [
            $this->provideTokenResponse(),
            function ($method, $url, $options): MockResponse {
                $this->assertSame('POST', $method);
                $this->assertSame(MomoApi::getBaseUrl() . '/collection/v1_0/requesttopay', $url);
                $this->assertArrayHasKey('authorization', $options['normalized_headers']);
                return new MockResponse('{}', ['http_code' => 202]);
            },
        ];

        MomoApi::useClient($this->provideClient($expectedRequests));
        $momo = MomoApi::create('testSubKey');
        $momo->setupCollection(new Config("apiUser", "apiKey", "aCalllback"));

        $request = new PaymentRequest(1000, 'EUR', 'ORDER-10', '46733123454', '', '');

        $paymentId = $momo->collection()->requestToPay($request);

        $this->assertValidGuidV4($paymentId);
    }

    private function provideTokenResponse(): \Closure
    {
        return function ($method, $url): MockResponse {
            $this->assertSame(MomoApi::getBaseUrl() . '/collection/token/', $url);
            return new MockResponse(json_encode([
                'access_token' => 'testToken',
                'expires_in' => 3600,
                'token_type' => 'Bearer'
            ]), ['http_code' => 200]);
        };
    }

    public function testThrowIfRequestToPayFailed()
    {
        $expectedRequests = [
            $this->provideTokenResponse(),
            function (): MockResponse {
                return new MockResponse('{}', ['http_code' => 500]);
            },
        ];

        MomoApi::useClient($this->provideClient($expectedRequests));
        $momo = MomoApi::create('testSubKey');
        $momo->setupCollection(new Config("apiUser", "apiKey", "aCalllback"));

        $request = new PaymentRequest(1000, 'EUR', 'ORDER-10', '46733123454', '', '');

        $this->expectException(MomoException::class);
        $momo->collection()->requestToPay($request);
    }

    public function testCheckTransactionStats()
    {
        $paymentId = Utilities::guidv4();
        $data = [
            "financialTransactionId" => "476321816",
            "externalId" => "ORDER-10",
            "amount" => "2500",
            "currency" => "EUR",
            "payer" => [
                "partyIdType" => "MSISDN",
                "partyId" => "46733123453"
            ],
            "payerMessage" => "Payment message",
            "payeeNote" => "A note",
            "status" => "SUCCESSFUL"
        ];

        $expectedRequests = [
            $this->provideTokenResponse(),
            function ($method, $url) use($paymentId, $data): MockResponse {
                $this->assertSame('GET', $method);
                $this->assertSame(MomoApi::getBaseUrl() . "/collection/v1_0/requesttopay/$paymentId", $url);
                return new MockResponse(json_encode($data), ['http_code' => 200]);
            },
        ];

        MomoApi::useClient($this->provideClient($expectedRequests));
        $momo = MomoApi::create('testSubKey');
        $momo->setupCollection(new Config("apiUser", "apiKey", "aCalllback"));
        $transaction = $momo->collection()->checkRequestStatus($paymentId);

        $this->assertEquals($data['status'], $transaction->getStatus());
        $this->assertEquals($data['payer']['partyId'], $transaction->getPayer());
        $this->assertTrue($transaction->isSuccessful());
        $this->assertIsFloat($transaction->getAmount());
    }
}