<?php

namespace Lepresk\MomoApi;

use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class ApiProduct
{
    protected HttpClientInterface $client;
    protected string $environment;
    protected Config $config;

    /**
     * @param HttpClientInterface $client
     * @param string $environment
     * @param Config $config
     */
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