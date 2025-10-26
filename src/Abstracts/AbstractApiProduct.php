<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Abstracts;

use Lepresk\MomoApi\Models\Config;
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
}
