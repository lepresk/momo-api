<?php
declare(strict_types=1);

namespace Tests;

use Lepresk\MomoApi\MomoApi;
use ReflectionClass;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @method void assertEquals(mixed $expected, mixed $actual, string $message = '')
 * @method void assertSame(mixed $expected, mixed $actual, string $message = '')
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        MomoApi::setEnvironment(MomoApi::ENVIRONMENT_SANDBOX);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $reflection = new ReflectionClass(MomoApi::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null);
    }

    protected function provideClient(array $responses): HttpClientInterface
    {
        return new MockHttpClient($responses, MomoApi::getBaseUrl());
    }

    protected function assertValidGuidV4($guid)
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $valid = preg_match($pattern, $guid) === 1;
        $this->assertTrue($valid, 'Invalid GUID: ' . $guid);
    }
}