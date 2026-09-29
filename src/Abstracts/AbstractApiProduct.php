<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Abstracts;

use Lepresk\MomoApi\Models\Config;
use Lepresk\MomoApi\MomoApi;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractApiProduct
{
    protected HttpClientInterface $client;
    protected string $environment;
    protected Config $config;

    public function __construct(HttpClientInterface $client, string $environment, Config $config)
    {
        $this->client = $client;
        $this->environment = $environment;
        $this->config = $config;
    }

    public function getSubscriptionKey(): string
    {
        return $this->config->getSubscriptionKey();
    }

    /**
     * The JSON body of a payment request. The sandbox rejects any currency but
     * EUR, so there the currency is replaced: code written for XAF runs
     * unchanged against the sandbox.
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    protected function requestBody(array $body): array
    {
        if ($this->environment === MomoApi::ENVIRONMENT_SANDBOX) {
            $body['currency'] = MomoApi::SANDBOX_CURRENCY;
        }

        return $body;
    }
}
