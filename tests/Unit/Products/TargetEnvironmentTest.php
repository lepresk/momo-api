<?php

namespace Tests\Unit\Products;

use Lepresk\MomoApi\MomoApi;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tests\TestCase;

/**
 * MTN requires X-Target-Environment on every request, authentication included.
 */
class TargetEnvironmentTest extends TestCase
{
    private const TOKEN = '{"access_token":"testToken","expires_in":3600,"token_type":"Bearer"}';

    private function config(): array
    {
        return [
            'environment' => 'mtncongo',
            'subscription_key' => 'testSubKey',
            'api_user' => 'apiUser',
            'api_key' => 'apiKey',
            'callback_url' => 'https://example.com/callback',
        ];
    }

    private function expectTokenRequest(string $product): array
    {
        return [
            function ($method, $url, $options) use ($product): MockResponse {
                $this->assertStringEndsWith("/$product/token/", $url);
                $this->assertContains('X-Target-Environment: mtncongo', $options['headers']);
                return new MockResponse(self::TOKEN, ['http_code' => 200]);
            },
        ];
    }

    public function testCollectionTokenRequestSendsTargetEnvironment()
    {
        MomoApi::useClient($this->provideClient($this->expectTokenRequest('collection')));
        MomoApi::collection($this->config())->getAccessToken();
    }

    public function testDisbursementTokenRequestSendsTargetEnvironment()
    {
        MomoApi::useClient($this->provideClient($this->expectTokenRequest('disbursement')));
        MomoApi::disbursement($this->config())->getAccessToken();
    }

    public function testSandboxProvisioningSendsTargetEnvironment()
    {
        $respond = fn (int $status) => function ($method, $url, $options) use ($status): MockResponse {
            $this->assertContains('X-Target-Environment: sandbox', $options['headers']);
            return new MockResponse('{"apiKey":"key"}', ['http_code' => $status]);
        };

        MomoApi::useClient($this->provideClient([$respond(201), $respond(200), $respond(201)]));
        $sandbox = MomoApi::create(MomoApi::ENVIRONMENT_SANDBOX)->sandbox('testSubKey');
        $sandbox->createApiUser('9f1c3c6e-3b9a-4a6e-8f5e-2d6c1b7a0e11', 'https://example.com');
        $sandbox->getApiUser('9f1c3c6e-3b9a-4a6e-8f5e-2d6c1b7a0e11');
        $sandbox->createApiKey('9f1c3c6e-3b9a-4a6e-8f5e-2d6c1b7a0e11');
    }
}
