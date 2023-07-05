<?php
declare(strict_types=1);

namespace Tests;

use InvalidArgumentException;
use Lepresk\MomoApi\MomoApi;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class MomoApiTest extends TestCase
{
    public function testEnvironnmentIsSandboxByDefault()
    {
        $this->assertEquals(MomoApi::$environment, MomoApi::ENVIRONMENT_SANDBOX);
    }

    public function testBaseUrlByEnvironnment()
    {
        $baseUrl = MomoApi::getBaseUrl();
        $this->assertEquals(MomoApi::SANDBOX_URL, $baseUrl);

        MomoApi::setEnvironment(MomoApi::ENVIRONMENT_MTN_CONGO);
        $this->assertNotEquals(MomoApi::SANDBOX_URL, MomoApi::getBaseUrl());
    }

    public function testFailGetCollectionWithoutConfig()
    {
        $this->expectException(InvalidArgumentException::class);
        $momo = MomoApi::create('test');
        $momo->collection();
    }

    public function testFailUseSandboxInProduction()
    {
        $this->expectException(InvalidArgumentException::class);
        MomoApi::setEnvironment(MomoApi::ENVIRONMENT_MTN_CONGO);
        $momo = MomoApi::create('test');
        $momo->sandbox();
    }

    public function testUsingMockedClient()
    {
        $expectedRequests = [];

        MomoApi::useClient($this->provideClient($expectedRequests));
        $this->assertInstanceOf(MockHttpClient::class, MomoApi::getClient());
    }
}