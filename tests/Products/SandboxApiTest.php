<?php

namespace Tests\Products;

use Lepresk\MomoApi\Exceptions\BadRequestExeption;
use Lepresk\MomoApi\Exceptions\ConflictException;
use Lepresk\MomoApi\Exceptions\MomoException;
use Lepresk\MomoApi\Exceptions\RessourceNotFoundException;
use Lepresk\MomoApi\MomoApi;
use Lepresk\MomoApi\Utilities;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tests\TestCase;

class SandboxApiTest extends TestCase
{
    public function testSubscriptionKeyPassed()
    {
        $this->expectException(MomoException::class);
        $subscriptionKey = 'testSubKey';

        $expectedRequests = [
            function ($method, $url, $options) use ($subscriptionKey): MockResponse {
                $this->assertContains("Ocp-Apim-Subscription-Key: $subscriptionKey", $options['headers']);
                return new MockResponse('{}');
            },
        ];

        MomoApi::useClient($this->provideClient($expectedRequests));
        $momo = MomoApi::create(MomoApi::ENVIRONMENT_SANDBOX);
        $momo->sandbox($subscriptionKey)->createApiKey('toto');
    }

    public function testCreateApiUser()
    {
        $callbackHost = 'https://my-domain.com/callback';
        $uuid = Utilities::guidv4();

        $expectedRequests = [
            function ($method, $url, $options) use ($callbackHost, $uuid): MockResponse {
                $this->assertSame('POST', $method);
                $this->assertSame($this->baseUrl() . '/v1_0/apiuser', $url);

                $this->assertSame(json_encode(["providerCallbackHost" => $callbackHost]), $options['body']);
                $this->assertContains("X-Reference-Id: $uuid", $options['headers']);

                return new MockResponse('{}', ['http_code' => 201]);
            },
        ];

        MomoApi::useClient($this->provideClient($expectedRequests));
        $momo = MomoApi::create(MomoApi::ENVIRONMENT_SANDBOX);
        $result = $momo->sandbox('testSubKey')->createApiUser($uuid, $callbackHost);

        $this->assertEquals($uuid, $result);
    }

    public function testThrowConflictIfApiUserExists()
    {
        $callbackHost = 'https://my-domain.com/callback';
        $uuid = Utilities::guidv4();

        $expectedRequests = [
            function () use ($callbackHost, $uuid): MockResponse {
                return new MockResponse('{}', ['http_code' => 409]);
            },
        ];

        $this->expectException(ConflictException::class);

        MomoApi::useClient($this->provideClient($expectedRequests));
        $momo = MomoApi::create(MomoApi::ENVIRONMENT_SANDBOX);
        $momo->sandbox('testSubKey')->createApiUser($uuid, $callbackHost);
    }

    public function testThrowBadRequestIfInvalidApiUser()
    {
        $callbackHost = 'https://my-domain.com/callback';

        $expectedRequests = [
            function (): MockResponse {
                return new MockResponse('{}', ['http_code' => 400]);
            },
        ];

        $this->expectException(BadRequestExeption::class);

        MomoApi::useClient($this->provideClient($expectedRequests));
        $momo = MomoApi::create(MomoApi::ENVIRONMENT_SANDBOX);
        $momo->sandbox('testSubKey')->createApiUser('badUUID', $callbackHost);
    }

    public function testGetApiUser()
    {
        $user = [
            'providerCallbackHost' => 'https://my-domain.com/callback',
            'targetEnvironment' => 'sandbox',
        ];
        $uuid = Utilities::guidv4();

        $expectedRequests = [
            function ($method, $url) use ($user, $uuid): MockResponse {
                $this->assertSame('GET', $method);
                $this->assertSame($this->baseUrl() . '/v1_0/apiuser/' . $uuid, $url);

                return new MockResponse(json_encode($user), ['http_code' => 200]);
            },
        ];

        MomoApi::useClient($this->provideClient($expectedRequests));
        $momo = MomoApi::create(MomoApi::ENVIRONMENT_SANDBOX);
        $result = $momo->sandbox('testSubKey')->getApiUser($uuid);

        $this->assertEquals($user, $result);
    }

    public function test404IfApiUserNotFound()
    {
        $this->expectException(RessourceNotFoundException::class);

        $user = [
            'providerCallbackHost' => 'https://my-domain.com/callback',
            'targetEnvironment' => 'sandbox',
        ];
        $uuid = Utilities::guidv4();

        $expectedRequests = [
            function () use ($user, $uuid): MockResponse {
                return new MockResponse(json_encode($user), ['http_code' => 404]);
            },
        ];

        MomoApi::useClient($this->provideClient($expectedRequests));
        $momo = MomoApi::create(MomoApi::ENVIRONMENT_SANDBOX);
        $momo->sandbox('testSubKey')->getApiUser($uuid);
    }

    public function testCreateApiKey()
    {
        $apiUser = Utilities::guidv4();

        $expectedRequests = [
            function ($method, $url) use ($apiUser): MockResponse {
                $this->assertSame('POST', $method);
                $this->assertSame($this->baseUrl() . "/v1_0/apiuser/$apiUser/apikey", $url);
                return new MockResponse('{"apiKey": "aKey"}', ['http_code' => 201]);
            },
        ];

        MomoApi::useClient($this->provideClient($expectedRequests));
        $momo = MomoApi::create(MomoApi::ENVIRONMENT_SANDBOX);
        $result = $momo->sandbox('testSubKey')->createApiKey($apiUser);

        $this->assertSame($result, 'aKey');
    }

    public function testThrowIfUnableToCreateApiKey()
    {
        $apiUser = Utilities::guidv4();

        $expectedRequests = [
            function () use ($apiUser): MockResponse {
                return new MockResponse('{}', ['http_code' => 400]);
            },
        ];

        $this->expectException(MomoException::class);

        MomoApi::useClient($this->provideClient($expectedRequests));
        $momo = MomoApi::create(MomoApi::ENVIRONMENT_SANDBOX);
        $momo->sandbox('testSubKey')->createApiKey($apiUser);
    }
}