<?php
declare(strict_types=1);

namespace Tests;

use InvalidArgumentException;
use Lepresk\MomoApi\MomoApi;
use Symfony\Component\HttpClient\MockHttpClient;

class MomoApiTest extends TestCase
{
    public function testBaseUrlByEnvironnment()
    {
        $baseUrl = MomoApi::getBaseUrl(MomoApi::ENVIRONMENT_SANDBOX);
        $this->assertEquals(MomoApi::SANDBOX_URL, $baseUrl);


        $baseUrl = MomoApi::getBaseUrl(MomoApi::ENVIRONMENT_MTN_CONGO);
        $this->assertEquals(MomoApi::PRODUCTION_URL, $baseUrl);
    }

    public function testFailGetCollectionWithoutConfig()
    {
        $this->expectException(InvalidArgumentException::class);
        $momo = MomoApi::create(MomoApi::ENVIRONMENT_SANDBOX);
        $momo->collection();
    }

    public function testFailUseSandboxInProduction()
    {
        $this->expectException(InvalidArgumentException::class);
        $momo = MomoApi::create(MomoApi::ENVIRONMENT_MTN_CONGO);
        $momo->sandbox('subscriptionLey');
    }

    public function testUsingMockedClient()
    {
        $expectedRequests = [];

        MomoApi::useClient($this->provideClient($expectedRequests));
        $this->assertInstanceOf(MockHttpClient::class, MomoApi::getClient());
    }
}